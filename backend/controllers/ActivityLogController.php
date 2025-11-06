<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use common\modules\taskmonitor\models\TaskHistory;
use common\modules\taskmonitor\models\Task;
use yii\helpers\ArrayHelper;

/**
 * ActivityLogController handles transaction/activity logs
 */
class ActivityLogController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Display activity log with filtering options
     * @return mixed
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        
        // Get filter parameters
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $actionType = $request->get('action_type');
        $taskId = $request->get('task_id');
        $userId = $request->get('user_id');
        $viewType = $request->get('view_type', 'timeline'); // default to timeline view
        
        // Build query
        $query = TaskHistory::find()
            ->with(['task', 'user'])
            ->orderBy(['created_at' => SORT_DESC]);
        
        // Apply date filters (convert date strings to timestamps)
        if ($dateFrom) {
            $fromTimestamp = strtotime($dateFrom . ' 00:00:00');
            $query->andWhere(['>=', 'created_at', $fromTimestamp]);
            // Debug: log the conversion
            \Yii::info("Date filter FROM: $dateFrom -> $fromTimestamp (" . date('Y-m-d H:i:s', $fromTimestamp) . ")", 'activity-log');
        }
        if ($dateTo) {
            $toTimestamp = strtotime($dateTo . ' 23:59:59');
            $query->andWhere(['<=', 'created_at', $toTimestamp]);
            // Debug: log the conversion  
            \Yii::info("Date filter TO: $dateTo -> $toTimestamp (" . date('Y-m-d H:i:s', $toTimestamp) . ")", 'activity-log');
        }
        
        // Apply other filters
        if ($actionType) {
            $query->andWhere(['action_type' => $actionType]);
        }
        if ($taskId) {
            $query->andWhere(['task_id' => $taskId]);
        }
        if ($userId) {
            $query->andWhere(['user_id' => $userId]);
        }
        
        // Create data provider with pagination
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ]
            ],
        ]);
        
        // Get filter options
        $actionTypes = TaskHistory::getActionTypes();
        $tasks = ArrayHelper::map(
            Task::find()->select(['id', 'title'])->all(),
            'id',
            'title'
        );
        
        // Get statistics
        $statistics = $this->getStatistics($dateFrom, $dateTo);
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'actionTypes' => $actionTypes,
            'tasks' => $tasks,
            'statistics' => $statistics,
            'viewType' => $viewType,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'action_type' => $actionType,
                'task_id' => $taskId,
                'user_id' => $userId,
                'view_type' => $viewType,
            ],
        ]);
    }
    
    /**
     * Export activity log to CSV
     * @return mixed
     */
    public function actionExport()
    {
        $request = Yii::$app->request;
        
        // Get filter parameters
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $actionType = $request->get('action_type');
        $taskId = $request->get('task_id');
        $userId = $request->get('user_id');
        
        // Build query
        $query = TaskHistory::find()
            ->with(['task', 'user'])
            ->orderBy(['created_at' => SORT_DESC]);
        
        // Apply same filters as index (convert date strings to timestamps)
        if ($dateFrom) {
            $fromTimestamp = strtotime($dateFrom . ' 00:00:00');
            $query->andWhere(['>=', 'created_at', $fromTimestamp]);
        }
        if ($dateTo) {
            $toTimestamp = strtotime($dateTo . ' 23:59:59');
            $query->andWhere(['<=', 'created_at', $toTimestamp]);
        }
        if ($actionType) {
            $query->andWhere(['action_type' => $actionType]);
        }
        if ($taskId) {
            $query->andWhere(['task_id' => $taskId]);
        }
        if ($userId) {
            $query->andWhere(['user_id' => $userId]);
        }
        
        $activities = $query->all();
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="activity_log_' . date('Y-m-d_H-i-s') . '.csv"');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write CSV header
        fputcsv($output, [
            'Date & Time',
            'Action',
            'Task',
            'User',
            'Details',
            'Changes'
        ]);
        
        // Write data rows
        foreach ($activities as $activity) {
            fputcsv($output, [
                Yii::$app->formatter->asDatetime($activity->created_at),
                $activity->getActionTypeLabel(),
                $activity->task ? $activity->task->title : 'N/A',
                $activity->user ? $activity->user->username : 'System',
                $activity->details,
                $activity->old_values ? json_encode($activity->old_values) : '',
            ]);
        }
        
        fclose($output);
        Yii::$app->end();
    }
    
    /**
     * Get activity statistics for the given date range
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    private function getStatistics($dateFrom = null, $dateTo = null)
    {
        $query = TaskHistory::find();
        
        // Apply date filters (convert date strings to timestamps)
        if ($dateFrom) {
            $fromTimestamp = strtotime($dateFrom . ' 00:00:00');
            $query->andWhere(['>=', 'created_at', $fromTimestamp]);
        }
        if ($dateTo) {
            $toTimestamp = strtotime($dateTo . ' 23:59:59');
            $query->andWhere(['<=', 'created_at', $toTimestamp]);
        }
        
        $totalActivities = $query->count();
        
        // Get activities by type
        $activitiesByType = [];
        $actionTypes = TaskHistory::getActionTypes();
        
        foreach ($actionTypes as $type => $label) {
            $typeQuery = clone $query;
            $count = $typeQuery->andWhere(['action_type' => $type])->count();
            $activitiesByType[$type] = [
                'label' => $label,
                'count' => $count,
                'percentage' => $totalActivities > 0 ? round(($count / $totalActivities) * 100, 1) : 0
            ];
        }
        
        // Get most active tasks
        $mostActiveTasks = TaskHistory::find()
            ->select(['task_id', 'COUNT(*) as activity_count'])
            ->with('task')
            ->groupBy('task_id')
            ->orderBy(['activity_count' => SORT_DESC])
            ->limit(5);
            
        if ($dateFrom) {
            $fromTimestamp = strtotime($dateFrom . ' 00:00:00');
            $mostActiveTasks->andWhere(['>=', 'created_at', $fromTimestamp]);
        }
        if ($dateTo) {
            $toTimestamp = strtotime($dateTo . ' 23:59:59');
            $mostActiveTasks->andWhere(['<=', 'created_at', $toTimestamp]);
        }
        
        $mostActiveTasks = $mostActiveTasks->all();
        
        // Get daily activity for last 7 days (or filtered range)
        $dailyActivity = [];
        $startDate = $dateFrom ? new \DateTime($dateFrom) : new \DateTime('-7 days');
        $endDate = $dateTo ? new \DateTime($dateTo) : new \DateTime();
        
        $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->modify('+1 day'));
        
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $dayStart = strtotime($dateStr . ' 00:00:00');
            $dayEnd = strtotime($dateStr . ' 23:59:59');
            $count = TaskHistory::find()
                ->andWhere(['>=', 'created_at', $dayStart])
                ->andWhere(['<=', 'created_at', $dayEnd])
                ->count();
                
            $dailyActivity[] = [
                'date' => $dateStr,
                'date_formatted' => $date->format('M j'),
                'count' => $count
            ];
        }
        
        return [
            'total_activities' => $totalActivities,
            'activities_by_type' => $activitiesByType,
            'most_active_tasks' => $mostActiveTasks,
            'daily_activity' => $dailyActivity,
        ];
    }
    
    /**
     * Demo data generator for testing
     * @return mixed
     */
    public function actionDemo()
    {
        return $this->render('demo');
    }
    
    /**
     * Generate demo activity data
     * @return mixed
     */
    public function actionGenerateDemoData()
    {
        $request = Yii::$app->request;
        
        if (!$request->isPost) {
            return $this->redirect(['demo']);
        }
        
        $count = (int) $request->post('count', 50);
        $startDate = $request->post('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->post('end_date', date('Y-m-d'));
        
        // Get existing tasks
        $tasks = Task::find()->all();
        if (empty($tasks)) {
            Yii::$app->session->setFlash('error', 'No tasks found. Please create some tasks first.');
            return $this->redirect(['index']);
        }
        
        $actionTypes = array_keys(TaskHistory::getActionTypes());
        $descriptions = [
            'created' => 'Task was created',
            'updated' => 'Task details were updated',
            'status_changed' => 'Task status was changed',
            'position_changed' => 'Task position was changed',
            'priority_changed' => 'Task priority was updated',
            'category_changed' => 'Task category was changed',
            'deadline_changed' => 'Task deadline was modified',
            'deleted' => 'Task was deleted',
            'completed' => 'Task was marked as completed',
            'assigned' => 'Task was assigned to user',
            'unassigned' => 'Task was unassigned',
            'restored' => 'Task was restored',
        ];
        
        $generated = 0;
        
        for ($i = 0; $i < $count; $i++) {
            // Random date between start and end
            $randomTimestamp = mt_rand(strtotime($startDate), strtotime($endDate . ' 23:59:59'));
            $randomDate = date('Y-m-d H:i:s', $randomTimestamp);
            
            // Random task and action
            $task = $tasks[array_rand($tasks)];
            $actionType = $actionTypes[array_rand($actionTypes)];
            
            $history = new TaskHistory();
            $history->task_id = $task->id;
            $history->user_id = Yii::$app->user->id;
            $history->action_type = $actionType;
            $history->description = isset($descriptions[$actionType]) ? $descriptions[$actionType] : 'Demo activity';
            $history->ip_address = Yii::$app->request->getUserIP();
            $history->user_agent = Yii::$app->request->getUserAgent();
            $history->created_at = strtotime($randomDate);
            
            // Add some sample old/new values
            if (in_array($actionType, ['updated', 'status_changed', 'priority_changed'])) {
                $history->field_name = $actionType === 'status_changed' ? 'status' : 
                                     ($actionType === 'priority_changed' ? 'priority' : 'title');
                $history->old_value = 'old_value_' . $i;
                $history->new_value = 'new_value_' . $i;
                $history->old_values = json_encode([$history->field_name => $history->old_value]);
            }
            
            if ($history->save()) {
                $generated++;
            }
        }
        
        Yii::$app->session->setFlash('success', "Successfully generated {$generated} demo activity records for testing the new Export to List feature.");
        return $this->redirect(['index']);
    }
    
    /**
     * Export activities as a simple table view
     * @return mixed
     */
    public function actionExportTable()
    {
        $request = Yii::$app->request;
        
        // Get filter parameters
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $actionType = $request->get('action_type');
        $taskId = $request->get('task_id');
        $userId = $request->get('user_id');
        
        // Build query
        $query = TaskHistory::find()
            ->with(['task', 'task.category', 'user'])
            ->orderBy(['created_at' => SORT_DESC]);
        
        // Apply date filters (convert date strings to timestamps)
        if ($dateFrom) {
            $fromTimestamp = strtotime($dateFrom . ' 00:00:00');
            $query->andWhere(['>=', 'created_at', $fromTimestamp]);
        }
        if ($dateTo) {
            $toTimestamp = strtotime($dateTo . ' 23:59:59');
            $query->andWhere(['<=', 'created_at', $toTimestamp]);
        }
        
        // Apply other filters
        if ($actionType) {
            $query->andWhere(['action_type' => $actionType]);
        }
        if ($taskId) {
            $query->andWhere(['task_id' => $taskId]);
        }
        if ($userId) {
            $query->andWhere(['user_id' => $userId]);
        }
        
        // Get all activities (limit to prevent memory issues)
        $activities = $query->limit(500)->all();
        
        // Set the layout to blank for clean table display
        $this->layout = false;
        
        return $this->render('export-table', [
            'activities' => $activities,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'action_type' => $actionType,
                'task_id' => $taskId,
                'user_id' => $userId,
            ],
        ]);
    }
}