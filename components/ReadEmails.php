<?php

namespace d3yii2\d3pop3\components;

use d3yii2\d3pop3\models\D3pop3Actions;
use unyii2\imap\IncomingMailAttachment;
use Yii;
use d3yii2\d3files\models\D3files;
use d3yii2\d3pop3\models\D3pop3Email;
use d3yii2\d3pop3\models\D3pop3EmailModel;
use unyii2\imap\Mailbox;
use unyii2\imap\ImapConnection;
use d3yii2\d3pop3\models\D3pop3EmailError;
use d3yii2\d3pop3\models\D3pop3EmailAddress;
use yii\helpers\FileHelper;
use yii\helpers\VarDumper;

class ReadEmails {

    /**
     * @param EmailContainerInerface $cc
     * @param $containerClass
     * @return bool
     */
    public static function readImap(EmailContainerInerface $cc, $containerClass) {
        
        $error = false;
        
        $tempDirectory = Yii::getAlias('@runtime/temp');
        
        while ($cc->featchData()) {

            $imapConnection = new ImapConnection();
            $imapConnection->imapPath = $cc->getImapPath();
            $imapConnection->imapLogin = $cc->getUserName();
            $imapConnection->imapPassword = $cc->getPassword();
            $imapConnection->serverEncoding = 'utf-8'; // utf-8 default.
            $imapConnection->attachmentsDir =  $tempDirectory;

            echo 'Connect to ' . $cc->getImapPath(). ' userName: ' . $cc->getUserName() .' (id='.$cc->getId().')' .PHP_EOL;

            /**
             * connect to IMAP
             */
            try {

                Action::read($cc->getId());

                $mailbox = new Mailbox($imapConnection);
                $mailbox->readMailParts = false;

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
                    echo $i . ' MessageId:' . $msg->messageId . PHP_EOL;

                    if(D3pop3Email::findOne(['email_id' => $msg->messageId])){
                        echo $i . ' Message already loaded' . PHP_EOL;
                        continue;
                    }

                    /**
                     * load attachments and bodies
                     */
                    $msg = $mailbox->getMailParts($msg);

                    $email = new D3pop3Email();
                    //$msg->date
                    $email->email_id = $msg->messageId;
                    $email->email_datetime = $msg->date;
                    $email->receive_datetime = new \yii\db\Expression('NOW()');
                    $email->subject = $msg->subject;
                    $email->body = $msg->textHtml;
                    $email->body_plain = $msg->textPlain;
                    $email->from = $msg->fromAddress;
                    $email->from_name = $msg->fromName;
                    $email->email_container_class = $containerClass;
                    $email->email_container_id =  $cc->getId();

                    if (!$email->save()) {
                        $errorList =  \yii\helpers\Json::encode($email->getErrors());
                        echo $errorList . PHP_EOL;
                        \Yii::error('Container class: ' . $containerClass . '; Can not save email. Error: ' . $errorList);
                        $error = true;
                        continue;
                    }

                    foreach ($msg->to as $toEmail => $toName){
                        $ea = new D3pop3EmailAddress();
                        $ea->email_id = $email->id;
                        $ea->address_type = D3pop3EmailAddress::ADDRESS_TYPE_TO;
                        $ea->email_address = $toEmail;
                        $ea->name = $toName;
                        $ea->save();
                    }

                    foreach ($msg->cc as $ccEmail => $ccName){
                        $ea = new D3pop3EmailAddress();
                        $ea->email_id = $email->id;
                        $ea->address_type = D3pop3EmailAddress::ADDRESS_TYPE_CC;
                        $ea->email_address = $ccEmail;
                        $ea->name = $ccName;
                        $ea->save();
                    }

                    foreach ($msg->replyTo as $rtEmail => $rtName){
                        $ea = new D3pop3EmailAddress();
                        $ea->email_id = $email->id;
                        $ea->address_type = D3pop3EmailAddress::ADDRESS_TYPE_REPLAY;
                        $ea->email_address = $rtEmail;
                        $ea->name = $rtName;
                        $ea->save();
                    }

                    $cc->setReceiver($email);

                    $attachModelList = $cc->getModelForattach($msg);
                    foreach ($attachModelList as $attachModel) {
                        $emailModel = new D3pop3EmailModel();
                        $emailModel->email_id = $email->id;
                        $emailModel->model_name = $attachModel['model_name'];
                        $emailModel->model_id = $attachModel['id'];
                        $emailModel->save();
                    }
                    $fileTypes = '/(gif|pdf|dat|jpe?g|png|doc|docx|xls|xlsx|htm|txt|log|mxl|xml|zip)$/i';

                    /** @var IncomingMailAttachment $t */
                    foreach ($msg->getAttachments() as $t) {
                        echo $i . ' A:' . $t->name . PHP_EOL;

                        /**
                         *  save to d3file
                         */
                        try {
                            D3files::saveFile($t->name, D3pop3Email::className(), $email->id, $t->filePath, $fileTypes);
                        } catch (\Exception $e) {
                            $errorMessage = Yii::t('d3pop3', 'Can not save attachment.')
                                            . Yii::t('d3pop3', 'Error: ')
                                            . $e->getMessage();
                            echo $errorMessage . PHP_EOL;
                            $error = new D3pop3EmailError();
                            $error->email_id = $email->id;
                            $error->message = $errorMessage;
                            $error->save();
                        }

                        //unlink($t->filePath);
                    }
                    echo PHP_EOL;
                }


            } catch (\Exception $e) {
                $message = 'Container class: ' . $containerClass . '; Can not connect ; Error: ' . $e->getMessage();
                echo $message . PHP_EOL;
                \Yii::error($message);
                \Yii::error( VarDumper::export($e->getTrace()));
                Action::error($cc->getId(),$message);
                continue;
            }

        }
        
        /**
         * remove all attachment files
         */
        $files = FileHelper::findFiles($tempDirectory);
        foreach($files as $f){
            unlink($f);
        }
        return !$error;
    }

}
