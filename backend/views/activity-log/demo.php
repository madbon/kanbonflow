<?php

use yii\helpers\Html;

/* @var $this yii\web\View */

$this->title = 'Generate Demo Activity Data';
$this->params['breadcrumbs'][] = ['label' => 'Activity Log', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="activity-log-demo">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><?= Html::encode($this->title) ?></h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        This will generate sample activity data for testing the Activity Log functionality.
                        It will create various types of activities across different dates.
                    </p>
                    
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        <strong>Note:</strong> This is for testing purposes only. The generated data will be mixed with your real activity history.
                    </div>
                    
                    <?= Html::beginForm(['generate-demo-data'], 'post', ['class' => 'mt-4']) ?>
                        <div class="form-group">
                            <label>Number of demo activities to generate:</label>
                            <?= Html::input('number', 'count', 50, [
                                'class' => 'form-control',
                                'min' => 1,
                                'max' => 500,
                                'required' => true
                            ]) ?>
                        </div>
                        
                        <div class="form-group">
                            <label>Date range for activities:</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <?= Html::input('date', 'start_date', date('Y-m-d', strtotime('-30 days')), [
                                        'class' => 'form-control',
                                        'required' => true
                                    ]) ?>
                                </div>
                                <div class="col-md-6">
                                    <?= Html::input('date', 'end_date', date('Y-m-d'), [
                                        'class' => 'form-control',
                                        'required' => true
                                    ]) ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group text-center">
                            <?= Html::submitButton('Generate Demo Data', [
                                'class' => 'btn btn-warning btn-lg',
                                'onclick' => 'return confirm("Are you sure you want to generate demo activity data?")',
                            ]) ?>
                            
                            <?= Html::a('Back to Activity Log', ['index'], [
                                'class' => 'btn btn-secondary btn-lg ml-3'
                            ]) ?>
                        </div>
                    <?= Html::endForm() ?>
                </div>
            </div>
        </div>
    </div>
</div>