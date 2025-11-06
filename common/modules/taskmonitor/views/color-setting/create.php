<?php

use yii\helpers\Html;

$this->title = 'Create Color Setting';
$this->params['breadcrumbs'][] = ['label' => 'Task Monitor', 'url' => ['/taskmonitor/default/index']];
$this->params['breadcrumbs'][] = ['label' => 'Color Settings', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="task-color-setting-create">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
