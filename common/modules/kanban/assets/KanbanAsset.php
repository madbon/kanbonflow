<?php

namespace common\modules\kanban\assets;

use yii\web\AssetBundle;

/**
 * Kanban asset bundle
 */
class KanbanAsset extends AssetBundle
{
    public $sourcePath = '@common/modules/kanban/assets';
    
    public function init()
    {
        parent::init();
        // Use kanban.js file to bypass cache
        $timestamp = time();
        $this->js = [
            'js/kanban.js?v=' . $timestamp . '&bust=' . mt_rand(),
        ];
        $this->css = [
            'css/kanban.css?v=' . $timestamp,
        ];
    }
    

    
    public $jsOptions = [
        'position' => \yii\web\View::POS_END
    ];
    
    public $publishOptions = [
        'forceCopy' => true, // Force copy to bypass cache
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap4\BootstrapAsset',
        'yii\bootstrap4\BootstrapPluginAsset',
    ];
}