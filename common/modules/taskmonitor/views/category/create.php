<?php

use yii\helpers\Html;

$this->title = 'Create Category';
$this->params['breadcrumbs'][] = ['label' => 'Task Monitor', 'url' => ['/taskmonitor/default/index']];
$this->params['breadcrumbs'][] = ['label' => 'Categories', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="task-category-create">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
