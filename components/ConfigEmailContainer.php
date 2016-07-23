<?php

namespace d3yii2\d3pop3\components;

use d3yii2\d3pop3\components\EmailContainerInerface;
use app\models\Test;
use afinogen89\getmail\message\Message;
use PhpImap\IncomingMail;

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
    
    public function getImapPath(){
        return '{' . $this->currentData['host'] . ':993/imap/ssl}INBOX';
    }

    public function getUserName(){
        return $this->currentData['user'];
    }

    public function getPassword(){
        return $this->currentData['password'];
    }

    /**
     * @inheritdoc
     */
    public function getModelForattach(IncomingMail $msg) {
        
        reset($msg->to);
        $to = $searchValue = key($msg->to);

        $from = $msg->fromAddress;
        
        switch ($this->serachByEmailField) {
            case 'to':
                $searchValue = $to;
                break;
            case 'from':
                $searchValue = $from;
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
