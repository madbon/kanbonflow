<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\modules\taskmonitor\models\TaskCategory;

?>

<div class="task-category-form">
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'modern-form'],
    ]); ?>

    <div class="row">
        <div class="col-md-8">
            <?= $form->field($model, 'parent_id')->dropDownList(
                TaskCategory::getCategoryDropdownList($model->id),
                ['prompt' => '-- No Parent (Root Category) --']
            ) ?>
            <small class="form-text text-muted">Select a parent category to create a subcategory, or leave empty for a root category</small>

            <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'e.g., Development']) ?>

            <?= $form->field($model, 'description')->textarea(['rows' => 4, 'placeholder' => 'Brief description of this category']) ?>

            <?= $form->field($model, 'icon')->textInput(['maxlength' => true, 'placeholder' => 'e.g., fa-code']) ?>
            <small class="form-text text-muted">Use Font Awesome icon classes. Examples: fa-code, fa-bug, fa-paint-brush</small>

            <div class="form-group">
                <?= Html::activeLabel($model, 'color') ?>
                <div class="color-input-wrapper">
                    <?= Html::activeInput('color', $model, 'color', [
                        'class' => 'form-control form-control-color color-picker',
                        'id' => 'category-color'
                    ]) ?>
                    <div class="color-preview" id="color-preview">
                        <span class="preview-text">Task Card Preview</span>
                    </div>
                </div>
                <small class="form-text text-muted">Choose a color for this category. Tasks in this category will use this color as background.</small>
            </div>
        </div>

        <div class="col-md-4">
            <?= $form->field($model, 'sort_order')->textInput(['type' => 'number']) ?>

            <?= $form->field($model, 'is_active')->checkbox() ?>
        </div>
    </div>

    <div class="form-actions">
        <?= Html::submitButton('<i class="fa fa-save"></i> Save Category', ['class' => 'btn btn-success']) ?>
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

.color-input-wrapper {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-top: 5px;
}

.color-picker {
    width: 60px;
    height: 40px;
    border: 2px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    padding: 0;
}

.color-preview {
    padding: 8px 15px;
    border-radius: 6px;
    border: 1px solid #ddd;
    min-width: 150px;
    text-align: center;
    font-size: 12px;
    font-weight: 500;
    color: #333;
    transition: all 0.2s ease;
}

.preview-text {
    display: block;
}
");

$this->registerJs("
$(document).ready(function() {
    var colorPicker = $('#category-color');
    var preview = $('#color-preview');
    
    function updatePreview() {
        var color = colorPicker.val();
        if (color) {
            // Convert hex to rgba for light background
            var hex = color.replace('#', '');
            var r = parseInt(hex.substr(0, 2), 16);
            var g = parseInt(hex.substr(2, 2), 16);
            var b = parseInt(hex.substr(4, 2), 16);
            var bgColor = 'rgba(' + r + ', ' + g + ', ' + b + ', 0.15)';
            var borderColor = color;
            
            preview.css({
                'background-color': bgColor,
                'border-color': borderColor,
                'border-width': '2px'
            });
        }
    }
    
    // Update preview on page load
    updatePreview();
    
    // Update preview when color changes
    colorPicker.on('input change', updatePreview);
});
");
?>
