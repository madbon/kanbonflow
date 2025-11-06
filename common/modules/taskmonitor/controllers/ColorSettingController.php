<?php

namespace common\modules\taskmonitor\controllers;

use Yii;
use common\modules\taskmonitor\models\TaskColorSetting;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;

/**
 * ColorSettingController implements CRUD actions for TaskColorSetting model.
 */
class ColorSettingController extends Controller
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
     * Lists all TaskColorSetting models.
     * @return mixed
     */
    public function actionIndex()
    {
        $settings = TaskColorSetting::find()
            ->orderBy(['days_before_deadline' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Creates a new TaskColorSetting model.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new TaskColorSetting();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Color setting created successfully.');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing TaskColorSetting model.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = TaskColorSetting::findOne($id);

        if (!$model) {
            Yii::$app->session->setFlash('error', 'Color setting not found.');
            return $this->redirect(['index']);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Color setting updated successfully.');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing TaskColorSetting model.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = TaskColorSetting::findOne($id);
        if (!$model) {
            return ['success' => false, 'message' => 'Color setting not found'];
        }

        if ($model->delete()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Failed to delete color setting'];
    }
}
