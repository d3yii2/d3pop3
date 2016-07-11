<?php

namespace d3yii2\d3pop3\command;

use yii\console\Controller;
use d3yii2\d3files\models\D3files;
use afinogen89\getmail\storage\Pop3;
use afinogen89\getmail\message\Attachment;

class D3Pop3Controller extends Controller {

    /**
     * Read from po3 emails and save to table d3pop3_emails
     */
    public function actionRead() {

        $error = false;

        /**
         * @todo load by behavior
         */
        $pop3boxes = \Yii::$app->getModule('D3Pop3')->pop3boxes;
        foreach ($pop3boxes as $pop3box) {

            echo 'host: ' . $pop3box['host'] . 'user: ' . $pop3box['user'] . PHP_EOL;

            $pop3connect = [
                'host' => $pop3box['host'],
                'user' => $pop3box['user'],
                'password' => $pop3box['password'],
                'ssl' => $pop3box['ssl'],
            ];

            /**
             * connect to POP3
             */
            try {
                $storage = new Pop3($pop3connect);
            } catch (\Exception $e) {
                Yii::error($e->getMessage());
                continue;
            }

            $countMessages = $storage->countMessages();
            echo 'Messages count:' . $countMessages . PHP_EOL;
            for ($i = 1; $i <= $countMessages; $i++) {
                $msg = $storage->getMessage($i);

                $header = $msg->getHeaders();
                echo $i . ' Subject:' . $header->getSubject() . PHP_EOL;
                echo $i . ' Date:' . $header->getDate() . PHP_EOL;

                $emailModel = new \d3yii2\d3pop3\models\D3pop3Email();
                $emailModel->receive_datetime = new \yii\db\Expression('NOW()');
                $emailModel->subject = $header->getSubject();
                $emailModel->body = $msg->getMsgBody();
                $emailModel->from = $header->getFrom();
                $emailModel->to = $header->getTo();
                $emailModel->cc = $header->getCC();

                /**
                 * identifice ierakstu
                 */
                if (!class_exists($pop3box['model'])){
                    \Yii::error('Try attach email to undefined model: ' . $pop3box['model']);
                    $error = true;
                    continue;
                }
                try {
                    /*                     * * @var ActiveRecors $recordmodel */
                    $emailField = $pop3box['email_field'];
                    $rModel = new $pop3box['model'];
                    $recordModel = $rModel::find()
                            ->where([$pop3box['model_field'] => $emailModel->$emailField])
                            ->one();
                    $emailModel->model_name = $pop3box['model'];
                    $emailModel->model_id = $recordModel->primaryKey;
                } catch (\Exception $e) {
                    \Yii::error($e->getMessage());
                    $error = true;
                    continue;
                }

                if (!$emailModel->save()) {
                    \Yii::error($emailModel->getErrors());
                    $error = true;
                    continue;
                }

                $fileTypes = '/(gif|pdf|dat|jpe?g|png|doc|docx|xls|xlsx|htm|txt|log)$/i';

                /** @var Attachment $t */
                foreach ($msg->getAttachments() as $t) {
                    echo $i . ' A:' . $t->filename . PHP_EOL;

                    /**
                     *  save to d3file
                     */
                    try {
                        D3files::saveFile($t->filename, 'D3pop3Email', $emailModel->id, $t->getData(), $fileTypes);
                    } catch (\Exception $e) {
                        \Yii::error($e->getMessage());
                        $error = true;
                        continue;
                    }
                }
                echo PHP_EOL;
            }

            echo '------------' . PHP_EOL;

            if ($error) {
                return self::EXIT_CODE_ERROR;
            }

            return self::EXIT_CODE_NORMAL;
        }
    }

}
