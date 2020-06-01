<?php

namespace d3yii2\d3pop3;

use d3yii2\d3pop3\components\DownloadFromUrlComponent;
use Yii;
use yii\base\Module;

class d3pop3 extends Module
{
    /**
     * @var array
     */
    public $params = [
        DownloadFromUrlComponent::class
    ];

    public $controllerNamespace = 'd3yii2\d3pop3\controllers';

    public $ConfigEmailContainerData = [];
    
    public $EmailContainers = [];

    /** @var string regular expression for attachment validation */
    public $allowedAttachmentFileExtensions = '/(gif|pdf|dat|jpe?g|png|doc|docx|xls|xlsx|htm?l|txt|log|mxl|xml|zip)$/i';

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
