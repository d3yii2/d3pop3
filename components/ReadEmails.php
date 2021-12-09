<?php

namespace d3yii2\d3pop3\components;

use d3system\helpers\D3FileHelper;
use d3yii2\d3pop3\models\D3pop3ConnectingSettings;
use DateTime;
use Exception;
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
     * @throws \unyii2\imap\Exception
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     */
    public static function readImap(EmailContainerInerface $cc, $containerClass)
    {

        $error = false;

        $tempDirectory = D3FileHelper::getRuntimeDirectoryPath('imaptemp');

        while ($cc->featchData()) {
            if (Yii::$app->has('SysCmp') && $sysCompanyId = $cc->getSysCompanyId()) {
                Yii::$app->SysCmp->setActiveId($sysCompanyId);
            }
            try {
                $imapConnection = new ImapConnection();
                $imapConnection->imapPath = $cc->getImapPath();
                $imapConnection->imapLogin = $cc->getUserName();
                $imapConnection->imapPassword = $cc->getPassword();
                $imapConnection->serverEncoding = 'utf-8'; // utf-8 default.
                $imapConnection->attachmentsDir = $tempDirectory;
            } catch (Exception $e) {
                $message = 'Container class: ' . $containerClass . PHP_EOL .
                    'connectionDetails: ' . VarDumper::dumpAsString($cc->dumConnectionData()) . PHP_EOL .
                    'Error: ' . $e->getMessage() . PHP_EOL .
                     $e->getTraceAsString()
                ;
                echo $message . PHP_EOL;
                Yii::error($message);
                Action::error($cc->getId(), $message);
                continue;
            }
            $connectionMessage = 'Connect to ' . $cc->getImapPath() . ' userName: ' . $cc->getUserName() . ' (id=' . $cc->getId() . ')';
            echo $connectionMessage . PHP_EOL;

            /**
             * connect to IMAP
             */
            try {

                Action::read($cc->getId());

                $mailbox = new Mailbox($imapConnection);
                $mailbox->readMailParts = false;

                if($cc->getMarkAsRead()){
                    $mailsIds = $mailbox->searchMailboxUnseen();
                }else {
                    $mailsIds = $mailbox->searchMailbox();
                }
                if (!$mailsIds) {
                    echo 'Mailbox is empty' . PHP_EOL;
                    continue;
                }

                echo 'Messages count:' . count($mailsIds) . PHP_EOL;
            } catch (Exception $e) {
                $message = 'Container class: ' . $containerClass . PHP_EOL .
                    'connectionDetails: ' . $connectionMessage . PHP_EOL .
                    'Error: ' . $e->getMessage();
                echo $message . PHP_EOL;
                Yii::error($message);
                Action::error($cc->getId(), $message);
                continue;
            }
            $expungeMails = false;
            foreach ($mailsIds as $i => $mailId) {

                $msg = $mailbox->getMail($mailId);
                echo $i . ' Subject:' . $msg->subject . PHP_EOL;
                echo $i . ' Date:' . $msg->date . PHP_EOL;
                echo $i . ' MessageId:' . $msg->messageId . PHP_EOL;

                if (D3pop3Email::findOne(['email_id' => $msg->messageId])) {
                    echo $i . ' Message already loaded' . PHP_EOL;
                    $nowDate = new DateTime();
                    $msgDate = new DateTime($msg->date);
                    if($nowDate->diff($msgDate)->format('%a') > $cc->getDeleteAfterDays()){
                        echo $i . ' Delete message (expire days = '.$cc->getDeleteAfterDays().') ' . PHP_EOL;
                        $mailbox->deleteMail($mailId);
                        $expungeMails = true;
                    }
                    continue;
                }

                $transaction = Yii::$app->db->beginTransaction();
                try {
                    /**
                     * load attachments and bodies
                     */
                    $msg = $mailbox->getMailParts($msg);

                    $d3mail = new D3Mail();
                    $d3mail->setEmailId($msg->messageId)
                        ->setSubject($msg->subject)
                        ->setEmailDatetime($msg->date)
                        ->setBodyPlain($msg->textPlain)
                        ->setBodyHtml($msg->textHtml)
                        ->setFromName($msg->fromName)
                        ->setFromEmail($msg->fromAddress)
                        ->setEmailContainerClass($containerClass)
                        ->setEmailContainerId($cc->getId())
                        ->setEmailId($msg->messageId);

                    if (($containerClass === SettingEmailContainer::class) && $setting = D3pop3ConnectingSettings::find()
                            ->andWhere([
                                'id' => $cc->getId(),
                                'deleted' => 0
                            ])
                            ->one()) {
                            $d3mail->addSendReceiveToInCompany($setting->sys_company_id);
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

                    /** @var IncomingMailAttachment $t */
                    foreach ($msg->getAttachments() as $t) {
                        echo $i . ' A:' . $t->name . PHP_EOL;
                        $d3mail->addAttachment($t->name, $t->filePath);
                    }
                    $d3mail->save();
                    if($cc->getMarkAsRead()) {
                        $mailbox->markMailAsRead($cc->getId());
                        $expungeMails = true;
                    }

                    $transaction->commit();

                    echo PHP_EOL;
                } catch (Exception $e) {
                    $transaction->rollBack();
                    $message = 'Container class: ' . $containerClass . PHP_EOL .
                        'connectionDetails: ' . $connectionMessage . PHP_EOL .
                        'Error: ' . $e->getMessage()
                    ;
                    echo $message . PHP_EOL;
                    Yii::error($message . PHP_EOL . $e->getTraceAsString());
                    Action::error($cc->getId(), $message);
                    continue;
                }
            }

            if($expungeMails){
                $mailbox->expungeDeletedMails();
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
