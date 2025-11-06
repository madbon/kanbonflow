<?php

namespace common\modules\taskmonitor\controllers;

use Yii;
use common\modules\taskmonitor\models\Task;
use common\modules\taskmonitor\models\TaskComment;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * CommentController handles task comment operations
 */
class CommentController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Only authenticated users
                    ],
                    [
                        'allow' => true,
                        'actions' => ['debug', 'get-comments', 'add'], // Allow testing actions for guests
                        'roles' => ['?'], // Guest users - TEMPORARY FOR TESTING
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'add' => ['POST'],
                    'delete' => ['POST'],
                    'edit' => ['POST'],
                    'get-comments' => ['GET'],
                ],
            ],
        ];
    }

    /**
     * Get comments for a task
     * @param int $taskId
     * @return array
     */
    public function actionGetComments($taskId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Debug current user
        $currentUser = Yii::$app->user->isGuest ? 'guest' : Yii::$app->user->id;
        Yii::info("GetComments request - TaskID: $taskId, Current User: $currentUser", 'comment');

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        $comments = TaskComment::getTopLevelComments($taskId);
        $commentsData = [];

        foreach ($comments as $comment) {
            $commentData = [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'user_name' => $comment->getUserDisplayName(),
                'user_avatar' => $comment->getUserAvatar(),
                'formatted_date' => $comment->getFormattedDate(),
                'relative_time' => $comment->getRelativeTime(),
                'is_internal' => $comment->is_internal,
                'can_edit' => $comment->canEdit(),
                'can_delete' => $comment->canDelete(),
                'replies' => [],
            ];

            // Add replies
            foreach ($comment->replies as $reply) {
                $commentData['replies'][] = [
                    'id' => $reply->id,
                    'comment' => $reply->comment,
                    'user_name' => $reply->getUserDisplayName(),
                    'user_avatar' => $reply->getUserAvatar(),
                    'formatted_date' => $reply->getFormattedDate(),
                    'relative_time' => $reply->getRelativeTime(),
                    'is_internal' => $reply->is_internal,
                    'can_edit' => $reply->canEdit(),
                    'can_delete' => $reply->canDelete(),
                ];
            }

            $commentsData[] = $commentData;
        }

        return [
            'success' => true,
            'comments' => $commentsData,
            'total_count' => TaskComment::getCommentCount($taskId)
        ];
    }

    /**
     * Add a new comment
     * @return array
     */
    public function actionAdd()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $taskId = Yii::$app->request->post('taskId');
        $comment = Yii::$app->request->post('comment');
        $parentId = Yii::$app->request->post('parentId');
        $isInternal = Yii::$app->request->post('isInternal', false);

        // Debug logging
        Yii::info("Add comment request - TaskID: $taskId, Comment: $comment, ParentID: $parentId, IsInternal: $isInternal", 'comment');

        if (!$taskId || !$comment) {
            return ['success' => false, 'message' => 'Task ID and comment are required'];
        }

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        $commentModel = TaskComment::addComment($taskId, $comment, $parentId, $isInternal);
        
        if ($commentModel) {
            Yii::info("Comment added successfully - ID: " . $commentModel->id, 'comment');
            return [
                'success' => true,
                'comment' => [
                    'id' => $commentModel->id,
                    'comment' => $commentModel->comment,
                    'user_name' => $commentModel->getUserDisplayName(),
                    'user_avatar' => $commentModel->getUserAvatar(),
                    'formatted_date' => $commentModel->getFormattedDate(),
                    'relative_time' => $commentModel->getRelativeTime(),
                    'is_internal' => $commentModel->is_internal,
                    'can_edit' => $commentModel->canEdit(),
                    'can_delete' => $commentModel->canDelete(),
                ]
            ];
        }

        Yii::error("Failed to add comment for task $taskId", 'comment');
        return ['success' => false, 'message' => 'Failed to add comment'];
    }

    /**
     * Edit a comment
     * @return array
     */
    public function actionEdit()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $commentId = Yii::$app->request->post('commentId');
        $comment = Yii::$app->request->post('comment');

        if (!$commentId || !$comment) {
            return ['success' => false, 'message' => 'Comment ID and text are required'];
        }

        $commentModel = TaskComment::findOne($commentId);
        if (!$commentModel) {
            return ['success' => false, 'message' => 'Comment not found'];
        }

        if (!$commentModel->canEdit()) {
            return ['success' => false, 'message' => 'You cannot edit this comment'];
        }

        $commentModel->comment = $comment;
        if ($commentModel->save()) {
            return [
                'success' => true,
                'comment' => [
                    'id' => $commentModel->id,
                    'comment' => $commentModel->comment,
                    'user_name' => $commentModel->getUserDisplayName(),
                    'user_avatar' => $commentModel->getUserAvatar(),
                    'formatted_date' => $commentModel->getFormattedDate(),
                    'relative_time' => $commentModel->getRelativeTime(),
                    'is_internal' => $commentModel->is_internal,
                    'can_edit' => $commentModel->canEdit(),
                    'can_delete' => $commentModel->canDelete(),
                ]
            ];
        }

        return ['success' => false, 'message' => 'Failed to update comment'];
    }

    /**
     * Delete a comment
     * @return array
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $commentId = Yii::$app->request->post('commentId');

        if (!$commentId) {
            return ['success' => false, 'message' => 'Comment ID is required'];
        }

        $commentModel = TaskComment::findOne($commentId);
        if (!$commentModel) {
            return ['success' => false, 'message' => 'Comment not found'];
        }

        if (!$commentModel->canDelete()) {
            return ['success' => false, 'message' => 'You cannot delete this comment'];
        }

        if ($commentModel->delete()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Failed to delete comment'];
    }

    /**
     * Debug endpoint to check authentication
     * @return array
     */
    public function actionDebug()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        return [
            'success' => true,
            'user_id' => Yii::$app->user->id,
            'is_guest' => Yii::$app->user->isGuest,
            'csrf_token' => Yii::$app->request->csrfToken,
            'csrf_param' => Yii::$app->request->csrfParam,
        ];
    }
}