<?php

namespace d3yii2\d3pop3;

use Yii;
use yii\base\Module;

class d3pop3 extends Module
{
    public $controllerNamespace = 'd3yii2\d3pop3\controllers';

    public $ConfigEmailContainerData = [];
    
    public $EmailContainers = [];

    /** @var string regular expression for attachment validation */
    public $allowedAttachmentFileExtensions = '/(gif|pdf|dat|jpe?g|png|doc|docx|xls|xlsx|htm?l|txt|log|mxl|xml|zip)$/i';

    /**
     * define post processing objects with
     * interface d3yii2\d3pop3\components\PostProcessingInterface
     * @var array
     */
    public $postProcessComponents = [];

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
