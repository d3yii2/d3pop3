<?php

namespace d3yii2\d3pop3\components;

use d3yii2\d3pop3\models\D3pop3ConnectingSettings;
use unyii2\imap\IncomingMailAttachment;
use Yii;
use d3yii2\d3pop3\models\D3pop3Email;
use unyii2\imap\Mailbox;
use unyii2\imap\ImapConnection;
use yii\helpers\FileHelper;
use yii\helpers\VarDumper;

class ReadEmails
{

    /**
     * @param EmailContainerInerface $cc
     * @param $containerClass
     * @return bool
     */
    public static function readImap(EmailContainerInerface $cc, $containerClass)
    {

        $error = false;

        $tempDirectory = Yii::getAlias('@runtime/temp');

        while ($cc->featchData()) {

            $imapConnection = new ImapConnection();
            $imapConnection->imapPath = $cc->getImapPath();
            $imapConnection->imapLogin = $cc->getUserName();
            $imapConnection->imapPassword = $cc->getPassword();
            $imapConnection->serverEncoding = 'utf-8'; // utf-8 default.
            $imapConnection->attachmentsDir = $tempDirectory;

            echo 'Connect to ' . $cc->getImapPath() . ' userName: ' . $cc->getUserName() . ' (id=' . $cc->getId() . ')' . PHP_EOL;

            /**
             * connect to IMAP
             */
            try {

                Action::read($cc->getId());

                $mailbox = new Mailbox($imapConnection);
                $mailbox->readMailParts = false;

                $mailsIds = $mailbox->searchMailbox('ALL');
                if (!$mailsIds) {
                    echo 'Mailbox is empty' . PHP_EOL;
                    continue;
                }

                echo 'Messages count:' . count($mailsIds) . PHP_EOL;
            } catch (\Exception $e) {
                $message = 'Container class: ' . $containerClass . '; ; Error: ' . $e->getMessage();
                echo $message . PHP_EOL;
                \Yii::error($message);
                Action::error($cc->getId(), $message);
                continue;
            }
            foreach ($mailsIds as $i => $mailId) {

                $msg = $mailbox->getMail($mailId);
                echo $i . ' Subject:' . $msg->subject . PHP_EOL;
                echo $i . ' Date:' . $msg->date . PHP_EOL;
                echo $i . ' MessageId:' . $msg->messageId . PHP_EOL;

                if (D3pop3Email::findOne(['email_id' => $msg->messageId])) {
                    echo $i . ' Message already loaded' . PHP_EOL;
                    continue;
                }

                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    /**
                     * load attachments and bodies
                     */
                    $msg = $mailbox->getMailParts($msg);

                    $d3mail = new D3Mail();
                    $d3mail->setEmailId($msg->messageId)
                        ->setSubject($msg->subject)
                        ->setBodyPlain($msg->textPlain)
                        ->setBodyHtml($msg->textHtml)
                        ->setFromName($msg->fromName)
                        ->setFromEmail($msg->fromAddress)
                        ->setEmailContainerClass($containerClass)
                        ->setEmailContainerId($cc->getId())
                        ->setEmailId($msg->messageId);

                    if ($containerClass === SettingEmailContainer::class) {
                        if ($setting = D3pop3ConnectingSettings::find()->andWhere(['id' => $cc->getId()])->one()) {
                            $d3mail->addSendReceiveToInCompany($setting->sys_company_id);
                        }
                    }

                    foreach ($msg->to as $toEmail => $toName) {
                        $d3mail->addAddressTo($toEmail, $toName);
                    }

                    foreach ($msg->cc as $ccEmail => $ccName) {
                        $d3mail->addAddressTo($ccEmail, $ccName);
                    }

                    foreach ($msg->replyTo as $rtEmail => $rtName) {
                        $d3mail->addAddressTo($rtEmail, $rtName);
                    }

                    $fileTypes = '/(gif|pdf|dat|jpe?g|png|doc|docx|xls|xlsx|htm|txt|log|mxl|xml|zip|csv)$/i';

                    /** @var IncomingMailAttachment $t */
                    foreach ($msg->getAttachments() as $t) {
                        echo $i . ' A:' . $t->name . PHP_EOL;
                        $d3mail->addAttachment($t->name, $t->filePath, $fileTypes);
                    }
                    $d3mail->save();
                    $transaction->commit();
                    echo PHP_EOL;
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    $message = 'Container class: ' . $containerClass . '; Error: ' . $e->getMessage();
                    echo $message . PHP_EOL;
                    \Yii::error($message);
                    \Yii::error(VarDumper::export($e->getTrace()));
                    Action::error($cc->getId(), $message);
                    continue;
                }
            }


        }

        /**
         * remove all attachment files
         */
        $files = FileHelper::findFiles($tempDirectory);
        foreach ($files as $f) {
            unlink($f);
        }
        return !$error;
    }

}
