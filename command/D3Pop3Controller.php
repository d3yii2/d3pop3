<?php

namespace d3yii2\d3pop3\command;

use yii\console\Controller;
use d3yii2\d3pop3\components\ReadEmails;

class D3Pop3Controller extends Controller {

    /**
     * Read from po3 emails and save to table d3pop3_emails
     */
    public function actionRead($container = false) {
        $error = false;
        if (!$container) {
            $eContainers = \Yii::$app->getModule('D3Pop3')->EmailContainers;
        } else {
            $eContainers = [$container];
        }
        
        foreach ($eContainers as $containerClass) {

            if (!class_exists($containerClass)) {

                \Yii::error('Can not found email container class:' . $containerClass);
                $error = true;
                continue;
            }
                
            $cc = new $containerClass;
            $error = $error || !ReadEmails::readPop3($cc, $containerClass);
            
        }


        if ($error) {
            return self::EXIT_CODE_ERROR;
        }

        return self::EXIT_CODE_NORMAL;
    }

}
