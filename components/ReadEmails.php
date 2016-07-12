<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace d3yii2\d3pop3\components;

use afinogen89\getmail\storage\Pop3;
use d3yii2\d3files\models\D3files;
use d3yii2\d3pop3\models\D3pop3Email;
use d3yii2\d3pop3\models\D3pop3EmailModel;
use afinogen89\getmail\message\Attachment;

class ReadEmails {

    public static function readPop3(EmailContainerInerface $cc, $containerClass) {
        $error = false;
        while ($cc->featchData()) {
            $pop3 = $cc->getPop3ConnectionDetails();

            /**
             * connect to POP3
             */
            try {
                $storage = new Pop3($pop3);
            } catch (\Exception $e) {
                \Yii::error('Container class: ' . $containerClass . '; Can not connect to: ' . json_encode($pop3) . '; Error: ' . $e->getMessage());
                return false;
            }

            $countMessages = $storage->countMessages();
            echo 'Messages count:' . $countMessages . PHP_EOL;
            for ($i = 1; $i <= $countMessages; $i++) {
                $msg = $storage->getMessage($i);

                $modelPkList = $cc->getModelPk($msg);

                $header = $msg->getHeaders();
                echo $i . ' Subject:' . $header->getSubject() . PHP_EOL;
                echo $i . ' Date:' . $header->getDate() . PHP_EOL;

                $email = new D3pop3Email();
                $email->receive_datetime = new \yii\db\Expression('NOW()');
                $email->subject = $header->getSubject();
                $email->body = $msg->getMsgBody();
                $email->from = $header->getFrom();
                $email->to = $header->getTo();
                $email->cc = $header->getCC();
                $email->email_container_class = $containerClass;

                if (!$email->save()) {
                    \Yii::error('Container class: ' . $containerClass . '; Can not save email. Attribute: ' . json_encode($email->attributes) . '; Error: ' . json_encode($email->getErrors()));
                    $error = true;
                    continue;
                }
                foreach ($modelPkList as $modelPk) {
                    $emailModel = new D3pop3EmailModel();
                    $emailModel->model_name = $cc->getModelName();
                    $emailModel->model_id = $modelPk;
                    $emailModel->save();
                }
                $fileTypes = '/(gif|pdf|dat|jpe?g|png|doc|docx|xls|xlsx|htm|txt|log)$/i';

                /** @var Attachment $t */
                foreach ($msg->getAttachments() as $t) {
                    echo $i . ' A:' . $t->filename . PHP_EOL;

                    /**
                     *  save to d3file
                     */
                    try {
                        D3files::saveFile($t->filename, 'D3pop3Email', $email->id, $t->getData(), $fileTypes);
                    } catch (\Exception $e) {
                        \Yii::error('Container class: ' . $containerClass . '; Can not save attachment. Attribute: ' . json_encode($email->attributes) . '; Error: ' . $e->getMessage());
                        $error = true;
                        continue;
                    }
                }
                echo PHP_EOL;
            }
        }
        return !$error;
    }

}
