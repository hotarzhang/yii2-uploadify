<?php
/**
 * Created by PhpStorm.
 * User: hoter.zhang
 * Date: 2015/9/18
 * Time: 14:52
 */

namespace hoter\uploadify;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;
use yii\widgets\InputWidget;

class Uploadify extends InputWidget{

    public $url;

    public $csrf = true;

    public $renderTag = true;

    public $jsOptions = [];

    public $registerJsPos = View::POS_LOAD;

    public function init() {
        if(empty($this->url)) {
            throw new InvalidConfigException("Url must be set");
        }

        if(empty($this->id)) {
            $this->id = $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->getId();
        }

        $this->options['id'] = $this->id;

        if(empty($this->name)) {
            $this->name = $this->hasModel() ? Html::getInputName($this->model, $this->attribute) : $this->id;
        }

        $assets = UploadifyAsset::register($this->view);

        $this->initOptions($assets);
        $this->initCsrfOption();

        parent::init();
    }

    public function run() {
        $this->registerScripts();
        if($this->renderTag === true) {
            echo $this->renderTag();
        }
    }

    protected function initOptions($assets) {
        $baseUrl = $assets->baseUrl;
        $this->jsOptions['uploader'] = $this->url;
        $this->jsOptions['swf'] = $baseUrl. DIRECTORY_SEPARATOR . 'uploadify.swf';
    }

    protected function initCsrfOption() {
        if (false === $this->csrf) {
            return;
        }

        $request = Yii::$app->request;
        $request->enableCsrfValidation = true;
        $csrfParam = $request->csrfParam;
        $csrfValue = $request->getCsrfToken();
        $session = Yii::$app->session;
        if (!$session->isActive) {
            $session->open();
        }

        if($request->enableCsrfValidation) {
            $cookieCsrfValue = Yii::$app->getRequest()->getCookies()->getValue($csrfParam);
            if ( null === $cookieCsrfValue) {
                $cookieCsrfValue = Yii::$app->getResponse()->getCookies()->getValue($csrfParam);
            }
            $session->set($csrfParam, $cookieCsrfValue);
        }
        $sessionIdName = $session->getName();
        $sessionIdValue = $session->getId();
        $this->jsOptions = ArrayHelper::merge($this->jsOptions,[
            'formData' => [
                $sessionIdName => $sessionIdValue,
                $csrfParam => $csrfValue,
            ]
        ]);
    }

    protected function renderTag() {
        return Html::fileInput($this->name, null, $this->options);
    }

    protected function registerScripts() {
        $jsonOptions = Json::encode($this->jsOptions);
        $script = <<<EOF
\$('#{$this->id}').uploadify({$jsonOptions});
EOF;
        $this->view->registerJs($script, $this->registerJsPos);
    }
}