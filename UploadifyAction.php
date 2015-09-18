<?php
/**
 * Created by PhpStorm.
 * User: hoter.zhang
 * Date: 2015/9/18
 * Time: 15:26
 */

namespace hoter\uploadify;


use Yii;
use yii\base\Action;
use yii\base\Exception;
use yii\validators\FileValidator;
use yii\web\UploadedFile;

class UploadifyAction extends Action{

    public $basePath = '@webroot/upload';

    public $baseUrl = '@web/upload';

    public $enableCsrf = true;

    public $format;

    public $validateOptions;

    public $postFieldName = 'Filedata';

    public $uploadfile;

    public $filename;

    public $overwriteIfExist = false;

    public $fileChmod = 0644;

    public $dirChmod = 0755;

    public $beforeValidate;

    public $afterValidate;

    public $beforeSave;

    public $afterSave;

    public $output = ['error' => false];

    public function init()
    {
        $this->initCsrf();

        if (empty($this->basePath)) {
            throw new Exception('basePath not exist');
        }
        $this->basePath = Yii::getAlias($this->basePath);

        if (empty($this->baseUrl)) {
            throw new Exception('baseUrl not exist');
        }
        $this->baseUrl = Yii::getAlias($this->baseUrl);

        if (false === is_callable($this->format) && false === is_array($this->format)) {
            throw new Exception('format is invalid');
        }

        return parent::init();
    }

    public function run()
    {
        try {
            //instance uploadfile
            $this->uploadfile = UploadedFile::getInstanceByName($this->postFieldName);
            if (null === $this->uploadfile) {
                throw new Exception("uploadfile {$this->postFieldName} not exist");
            }

            if (null !== $this->beforeValidate) {
                call_user_func($this->beforeValidate, $this);
            }
            $this->validate();
            if (null !== $this->afterValidate) {
                call_user_func($this->afterValidate, $this);
            }
            if (null !== $this->beforeSave) {
                call_user_func($this->beforeSave, $this);
            }
            $this->save();
            if ($this->afterSave !== null) {
                call_user_func($this->afterSave, $this);
            }
        } catch (Exception $e) {
            $this->output['error'] = true;
            $this->output['msg'] = $e->getMessage();
        }
        Yii::$app->response->format = 'json';
        return $this->output;
    }

    /**
     * @throws Exception
     */
    protected function save()
    {
        $filename = $this->getFilename();
        $basePath = $this->basePath;
        $saveFilename = $basePath . '/' . $filename;
        $dirPath = dirname($saveFilename);
        if (false === is_dir($dirPath) && false === file_exists($dirPath)) {
            if (false === mkdir($dirPath, $this->dirChmod, true)) {
                throw new Exception("Create Directory Fail: {$dirPath}");
            }
        }
        $saveResult = $this->uploadfile->saveAs($saveFilename);
        if (true === $saveResult) {
            if (false === chmod($saveFilename, $this->fileChmod)) {
                throw new Exception("SetChmod Fail: {$this->fileChmod} {$saveFilename}");
            }
        } else {
            throw new Exception("SaveAsFile Fail: {$saveFilename}");
        }
    }

    /**
     * 取得没有碰撞的FileName
     * @return string
     * @throws Exception
     */
    protected function getSaveFileNameWithNotExist()
    {
        $retryCount = 10;
        $currentCount = 0;
        $basePath = $this->basePath;
        $filename = '';
        do {
            ++$currentCount;
            $filename = $this->getSaveFileName();
            $filepath = $basePath . DIRECTORY_SEPARATOR . $filename;
        } while ($currentCount < $retryCount && file_exists($filepath));
        if ($currentCount == $retryCount) {
            throw new Exception(__FUNCTION__ . " try {$currentCount} times");
        }
        return $filename;
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getSaveFileName()
    {
        return call_user_func($this->format, $this);
    }

    /**
     * @throws Exception
     */
    protected function validate()
    {
        if (empty($this->validateOptions)) {
            return;
        }
        $file = $this->uploadfile;
        $error = [];
        $validator = new FileValidator($this->validateOptions);
        if (!$validator->validate($file, $error)) {
            throw new Exception($error);
        }
    }

    protected function initCsrf()
    {
        $session = Yii::$app->getSession();
        $request = Yii::$app->getRequest();
        if (false === $this->enableCsrf) {
            return;
        }
        $request->enableCsrfValidation = true;
        $request->enableCsrfCookie = false; //verify with session
        $sessionName = $session->getName();
        $postSessionId = $request->post($sessionName);
        if ($postSessionId != $session->getId()) {
            $session->destroy();
            $session->setId($postSessionId);
            $session->open();
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getFilename()
    {
        if (null === $this->filename) {
            if ($this->overwriteIfExist) {
                $this->filename = $this->getSaveFileName();
            } else {
                $this->filename = $this->getSaveFileNameWithNotExist();
            }
        }
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getSavePath()
    {
        return rtrim($this->basePath, '\\/') . '/' . $this->filename;
    }

    /**
     * @return string
     */
    public function getWebUrl()
    {
        return rtrim($this->baseUrl, '\\/') . '/' . $this->filename;
    }
}