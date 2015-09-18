<?php
/**
 * Created by PhpStorm.
 * User: hoter.zhang
 * Date: 2015/9/18
 * Time: 14:47
 */

namespace hoter\uploadify;


use yii\web\AssetBundle;

class UploadifyAsset extends AssetBundle{

    public $css = [
        'uploadify.css'
    ];

    public $js = [
        'jquery.uploadify.js'
    ];

    public $depends = [
        'yii\web\JqueryAsset',
    ];

    public function init(){
        $this->sourcePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets';
        $this->basePath = '@webroot/assets';
    }
}