<?php

namespace d3yii2\d3pop3;

use Yii;

class d3pop3 extends \yii\base\Module
{

    public $controllerNamespace = 'd3yii2\d3pop3\controllers';

    public $ConfigEmailContainerData = [];
    
    public $EmailContainers = [];

    public function init()
    {
        parent::init();
        self::registerTranslations();
    }
    
    public static function registerTranslations()
    {
        $i18n = Yii::$app->i18n;
        $i18n->translations['d3pop3'] = [
            'class'            => 'yii\i18n\PhpMessageSource',
            'sourceLanguage'   => 'en-US',
            'basePath'         => __DIR__ . '\messages',
            'forceTranslation' => true
        ];
    }
}
