<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="task-color-setting-form">
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'modern-form'],
    ]); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'e.g., Critical']) ?>

            <?= $form->field($model, 'days_before_deadline')->textInput(['type' => 'number', 'min' => 0]) ?>
            <small class="form-text text-muted">Enter 0 for overdue tasks, or the number of days before deadline when this color should apply</small>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <?= $form->field($model, 'color')->textInput([
                    'maxlength' => true,
                    'placeholder' => '#FF0000',
                    'id' => 'color-input'
                ]) ?>

                <div class="color-picker-preview">
                    <label>Color Preview</label>
                    <div class="color-preview-box" id="color-preview" style="background-color: <?= Html::encode($model->color ?: '#FFFFFF') ?>"></div>
                    <input type="color" id="color-picker" value="<?= Html::encode($model->color ?: '#FFFFFF') ?>">
                </div>
            </div>

            <?= $form->field($model, 'sort_order')->textInput(['type' => 'number']) ?>

            <?= $form->field($model, 'is_active')->checkbox() ?>
        </div>
    </div>

    <div class="form-actions">
        <?= Html::submitButton('<i class="fa fa-save"></i> Save Setting', ['class' => 'btn btn-success']) ?>
        <?= Html::a('<i class="fa fa-times"></i> Cancel', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$this->registerCss("
.modern-form {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 25px;
}

.form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 10px;
}

.color-picker-preview {
    margin-top: 15px;
}

.color-picker-preview label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #2c3e50;
    font-size: 14px;
}

.color-preview-box {
    width: 100%;
    height: 60px;
    border-radius: 4px;
    border: 2px solid #dee2e6;
    margin-bottom: 10px;
}

#color-picker {
    width: 100%;
    height: 40px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
");

$this->registerJs("
// Sync color picker with input
$('#color-picker').on('input', function() {
    var color = $(this).val();
    $('#color-input').val(color);
    $('#color-preview').css('background-color', color);
});

$('#color-input').on('input', function() {
    var color = $(this).val();
    if (/^#[0-9A-F]{6}$/i.test(color)) {
        $('#color-picker').val(color);
        $('#color-preview').css('background-color', color);
    }
});
");
?>
