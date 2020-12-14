<?php

namespace d3yii2\d3pop3\command;

use d3yii2\d3pop3\components\Action;
use unyii2\imap\Exception;
use Yii;
use yii\console\Controller;
use d3yii2\d3pop3\components\ReadEmails;
use yii\console\ExitCode;


class D3Pop3Controller extends Controller {

    /**
     * Read from po3 emails and save to table d3pop3_emails
     *
     * @param bool $container
     * @return int
     * @throws Exception
     */
    public function actionRead($container = false) {

        $deletedRows = Action::clearOldRecords(2);
        $this->stdOutLine('Deleted ' . $deletedRows . ' from table d3pop3_actions oldest as 2 hours');
        $error = false;
        if (!$container) {
            $eContainers = Yii::$app->getModule('D3Pop3')->EmailContainers;
        } else {
            $eContainers = [$container];
        }
        foreach ($eContainers as $containerClass) {
            $this->stdOutLine('Container class:' . $containerClass);
            if (!class_exists($containerClass)) {
                $this->stdOutLine('Can not found email container class:' . $containerClass);
                Yii::error('Can not found email container class:' . $containerClass);
                $error = true;
                continue;
            }
                
            $cc = new $containerClass;
            $error = $error || !ReadEmails::readImap($cc, $containerClass);
            
        }


        if ($error) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Command for testing LMT post processing
     * @throws \yii\base\Exception
     */
    public function actionComponentTestFunction()
    {
        $postProcessingLMT = new \d3yii2\d3pop3\components\DownloadForLMTPostProcessing();
        $email = \d3yii2\d3pop3\models\D3pop3Email::findOne(['id' => 2126]);

        $postProcessingLMT->run($email);
    }

    private function stdOutLine($text)
    {
        $this->stdout($text . PHP_EOL);
    }

}
