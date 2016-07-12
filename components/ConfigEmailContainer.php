<?php

namespace d3yii2\d3pop3\components;

use d3yii2\d3pop3\components\EmailContainerInerface;
use app\models\Test;
use afinogen89\getmail\message\Message;

class ConfigEmailContainer implements EmailContainerInerface {

    public $data;
    public $currentData;

    public function __construct() {
        $this->data = \Yii::$app->getModule('D3Pop3')->ConfigEmailContainerData;
    }

    /**
     * @inheritdoc
     */
    public function featchData() {
        if (!$this->data) {
            return false;
        }
        $this->currentData = array_shift($this->data);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getPop3ConnectionDetails() {
        return
                [
                    'host' => $this->currentData['host'],
                    'user' => $this->currentData['user'],
                    'password' => $this->currentData['password'],
                    'ssl' => $this->currentData['ssl'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getModelName() {
        return 'app\models\test';
    }

    /**
     * @inheritdoc
     */
    public function getModelPk(Message $msg) {
        $header = $msg->getHeaders();
        $testData = Test::find()
                ->select('id')
                ->where(['description' => $header->getTo()])
                ->asArray()
                ->all();
        if (!$testData) {
            return false;
        }

        $ids = [];
        foreach ($testData as $td) {
            $ids[] = $td['id'];
        }
        return $ids;
    }

}
