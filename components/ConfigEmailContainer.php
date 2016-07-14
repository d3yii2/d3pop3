<?php

namespace d3yii2\d3pop3\components;

use d3yii2\d3pop3\components\EmailContainerInerface;
use app\models\Test;
use afinogen89\getmail\message\Message;

class ConfigEmailContainer implements EmailContainerInerface {

    public $data;
    public $currentData;
    public $modelName;
    public $modelSearchField;
    public $serachByEmailField;

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
        $this->modelName = $this->currentData['model'];
        $this->modelSearchField = $this->currentData['model_search_field'];
        $this->serachByEmailField = $this->currentData['search_by_email_field'];
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
    public function getModelForattach(Message $msg) {
        $header = $msg->getHeaders();
        
        $searchValue = $header->getTo();
        switch ($this->serachByEmailField) {
            case 'to':
                $searchValue = $header->getTo();
                break;
            case 'from':
                $searchValue = $header->getFrom();
                break;

            default:
                break;
        }
        
        $model = new $this->modelName;
        
        $modelData = $model::find()
                ->select('id')
                ->where([$this->modelSearchField => $searchValue])
                ->asArray()
                ->all();
        if (!$modelData) {
            return [];
        }

        $ids = [];
        
        $reflection       = new \ReflectionClass($this->modelName);
        $shortModelName = $reflection->getShortName();
        foreach ($modelData as $td) {
            $ids[] = [
                'id' => $td['id'],
                'model_name' => $shortModelName,
                    ];
        }
        return $ids;
    }

}
