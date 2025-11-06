<?php

namespace common\modules\taskmonitor\controllers;

use Yii;
use common\modules\taskmonitor\models\TaskCategory;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;

/**
 * CategoryController implements CRUD actions for TaskCategory model.
 */
class CategoryController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all TaskCategory models.
     * @return mixed
     */
    public function actionIndex()
    {
        $categories = TaskCategory::find()
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Creates a new TaskCategory model.
     * @param integer $parent_id Optional parent category ID
     * @return mixed
     */
    public function actionCreate($parent_id = null)
    {
        $model = new TaskCategory();

        // Set parent_id if provided
        if ($parent_id) {
            $model->parent_id = $parent_id;
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Category created successfully.');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing TaskCategory model.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = TaskCategory::findOne($id);

        if (!$model) {
            Yii::$app->session->setFlash('error', 'Category not found.');
            return $this->redirect(['index']);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Category updated successfully.');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing TaskCategory model.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = TaskCategory::findOne($id);
        if (!$model) {
            return ['success' => false, 'message' => 'Category not found'];
        }

        // Check if category has tasks
        if ($model->getActiveTasksCount() > 0) {
            return ['success' => false, 'message' => 'Cannot delete category with active tasks'];
        }

        // Check if category has subcategories
        if ($model->hasChildren()) {
            return ['success' => false, 'message' => 'Cannot delete category with subcategories. Please delete subcategories first.'];
        }

        if ($model->delete()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Failed to delete category'];
    }
}
