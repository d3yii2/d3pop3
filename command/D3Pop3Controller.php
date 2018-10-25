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
            echo 'Container class:' . $containerClass.PHP_EOL;            
            if (!class_exists($containerClass)) {
                echo 'Can not found email container class:' . $containerClass.PHP_EOL;
                \Yii::error('Can not found email container class:' . $containerClass);
                $error = true;
                continue;
            }
                
            $cc = new $containerClass;
            $error = $error || !ReadEmails::readImap($cc, $containerClass);
            
        }


        if ($error) {
            return self::EXIT_CODE_ERROR;
        }

        return self::EXIT_CODE_NORMAL;
    }
    
    public function actionTest() {
        $mailbox = new Mailbox('{imap.gmail.com:993/imap/ssl}INBOX', 'd3yii2d3pop3@gmail.com', '2uvsKCrDU7MkXQKPxkXs');

        // Read all messaged into an array:
            $mailsIds = $mailbox->searchMailbox('ALL');
            var_dump($mailbox);
    }

}
