yii2-uploadify
==============
yii2-uploadify

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist hoter/yii2-uploadify:dev-master
```

or add

```
"hoter/yii2-uploadify": "dev-master"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
//Remove Events Auto Convert

use yii\web\JsExpression;

//外部TAG
echo Html::fileInput('test', NULL, ['id' => 'test']);
echo Uploadify::widget([
    'url' => yii\helpers\Url::to(['s-upload']),
    'id' => 'test',
    'csrf' => true,
    'renderTag' => false,
    'jsOptions' => [
        'width' => 120,
        'height' => 40,
        'onUploadError' => new JsExpression(<<<EOF
function(file, errorCode, errorMsg, errorString) {
    console.log('The file ' + file.name + ' could not be uploaded: ' + errorString + errorCode + errorMsg);
}
EOF
),
        'onUploadSuccess' => new JsExpression(<<<EOF
function(file, data, response) {
    data = JSON.parse(data);
    if (data.error) {
        console.log(data.msg);
    } else {
        console.log(data.fileUrl);
    }
}
EOF
),
    ]
]);```

```php
public function actions() {
    return [
        's-upload' => [
            'class' => UploadAction::className(),
            'basePath' => '@webroot/upload',
            'baseUrl' => '@web/upload',
            'enableCsrf' => true, // default
            'postFieldName' => 'Filedata', // default
            //BEGIN METHOD
            'format' => [$this, 'methodName'],
            //END METHOD
            //BEGIN CLOSURE BY-HASH
            'overwriteIfExist' => true,
            'format' => function (UploadAction $action) {
                $fileext = $action->uploadfile->getExtension();
                $filename = sha1_file($action->uploadfile->tempName);
                return "{$filename}.{$fileext}";
            },
            //END CLOSURE BY-HASH
            //BEGIN CLOSURE BY TIME
            'format' => function (UploadAction $action) {
                $fileext = $action->uploadfile->getExtension();
                $filehash = sha1(uniqid() . time());
                $p1 = substr($filehash, 0, 2);
                $p2 = substr($filehash, 2, 2);
                return "{$p1}/{$p2}/{$filehash}.{$fileext}";
            },
            //END CLOSURE BY TIME
            'validateOptions' => [
                'extensions' => ['jpg', 'png'],
                'maxSize' => 1 * 1024 * 1024, //file size
            ],
            'beforeValidate' => function (UploadAction $action) {
                //throw new Exception('test error');
            },
            'afterValidate' => function (UploadAction $action) {},
            'beforeSave' => function (UploadAction $action) {},
            'afterSave' => function (UploadAction $action) {
                $action->output['fileUrl'] = $action->getWebUrl();
                $action->getFilename(); // "image/yyyymmddtimerand.jpg"
                $action->getWebUrl(); //  "baseUrl + filename, /upload/image/yyyymmddtimerand.jpg"
                $action->getSavePath(); // "/var/www/htdocs/upload/image/yyyymmddtimerand.jpg"
            },
        ],
    ];
}
```