<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace d3yii2\d3pop3\components;

use d3yii2\d3files\models\D3files;
use d3yii2\d3pop3\models\D3pop3Email;
use d3yii2\d3pop3\models\D3pop3EmailModel;
use unyii2\imap\Mailbox;


class ReadEmails {

    public static function readImap(EmailContainerInerface $cc, $containerClass) {
        $error = false;
        while ($cc->featchData()) {

            $imapConnection = new unyii2\imap\ImapConnection();
            $imapConnection->imapPath = $cc->getImapPath();
            $imapConnection->imapLogin = $cc->getUserName();
            $imapConnection->imapPassword = $cc->getPassword();
            //$imapConnection->serverEncoding = 'encoding'; // utf-8 default.
            $imapConnection->attachmentsDir =  Yii::getAlias('@runtime/temp');            

            /**
             * connect to IMAP
             */
            try {
                $mailbox = new Mailbox($imapConnection);
                var_dump($mailbox);
            } catch (\Exception $e) {
                \Yii::error('Container class: ' . $containerClass . '; Can not connect to: ' . $imapPath . '; Error: ' . $e->getMessage());
                return false;
            }

            $mailsIds = $mailbox->searchMailbox('ALL');
            if(!$mailsIds) {
                echo 'Mailbox is empty' . PHP_EOL;
                continue;
            }            
            
            echo 'Messages count:' . count($mailsIds) . PHP_EOL;
            foreach ($mailsIds as $i => $mailId) {
                
                $msg = $mailbox->getMail($mailId);
                
                echo $i . ' Subject:' . $msg->subject . PHP_EOL;
                echo $i . ' Date:' . $msg->date . PHP_EOL;

                $email = new D3pop3Email();
                //$msg->date
                $email->receive_datetime = new \yii\db\Expression('NOW()');
                $email->subject = $msg->subject;
                $email->body = $msg->textPlain;
                $email->from = $msg->fromAddress;
                reset($msg->to);
                $email->to = key($msg->to);
                //$email->cc = $header->getCC();
                $email->email_container_class = $containerClass;

                if (!$email->save()) {
                    \Yii::error('Container class: ' . $containerClass . '; Can not save email. Attribute: ' . json_encode($email->attributes) . '; Error: ' . json_encode($email->getErrors()));
                    $error = true;
                    continue;
                }
                
                $attachModelList = $cc->getModelForattach($msg);
                foreach ($attachModelList as $attachModel) {
                    $emailModel = new D3pop3EmailModel();
                    $emailModel->email_id = $email->id;
                    $emailModel->model_name = $attachModel['model_name'];
                    $emailModel->model_id = $attachModel['id'];
                    $emailModel->save();
                }
                $fileTypes = '/(gif|pdf|dat|jpe?g|png|doc|docx|xls|xlsx|htm|txt|log)$/i';

                /** @var Attachment $t */
                foreach ($msg->getAttachments() as $t) {
                    var_dump($t);
                    continue;
                    echo $i . ' A:' . $t->name . PHP_EOL;

                    /**
                     *  save to d3file
                     */
                    try {
                        D3files::saveFile($t->name, 'D3pop3Email', $email->id, $t->filepath, $fileTypes);
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
