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
             * @todo process errors an registre it
             */
            try{
                $storage = new Pop3($pop3connect);
            } catch (\Exception $e) {
                echo 'error: ' . $e->getMessage() . PHP_EOL;
                echo '------------' . PHP_EOL;
                continue;
            }
            
            $countMessages = $storage->countMessages();
            echo 'Messages count:' . $countMessages . PHP_EOL;
            for ($i = 1; $i <= $countMessages; $i++) {
                $msg = $storage->getMessage($i);
                /**
                 * @todo save to table
                 */
                $header= $msg->getHeaders();
                echo $i . ' Subject:' . $header->getSubject() . PHP_EOL;
                echo $i . ' Date:' . $header->getDate() . PHP_EOL;

                $model = new \d3yii2\d3pop3\models\D3pop3Email();
                $model->model_name = $pop3box['model'];
                $model->model_id = $pop3box['record_id'];
                $model->receive_datetime = new \yii\db\Expression('NOW()');
                $model->subject = $header->getSubject();
                $model->body = $msg->getMsgBody();
                $model->from = $header->getFrom();
                $model->to = $header->getTo();
                $model->cc = $header->getCC();
                $model->save();

                /** @var Attachment $t */
                foreach ($msg->getAttachments() as $t) {
                    echo $i . ' A:'. $t->filename . PHP_EOL;
                    /**
                     *  save to d3file
                     */
                    D3files::saveFile($t->filename, $pop3box['model'], $pop3box['record_id'], $t->getData());
                }
                echo PHP_EOL;
            }
            
            echo '------------' . PHP_EOL;
        }
    }

}
