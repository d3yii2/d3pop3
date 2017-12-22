<?php

namespace d3yii2\d3pop3\components;

use d3yii2\d3pop3\components\EmailContainerInerface;
use app\models\Test;
use afinogen89\getmail\message\Message;
use d3yii2\d3pop3\models\D3pop3ConnectingSettings;
use d3yii2\d3pop3\models\D3pop3Email;
use d3yii2\d3pop3\models\D3pop3SendReceiv;
use unyii2\imap\IncomingMail;
use yii\helpers\Json;

class SettingEmailContainer implements EmailContainerInerface {

    public $data;
    public $currentData;
    public $modelName;
    public $modelSearchField;
    public $serachByEmailField;
    private $loadedData = false;
    private $record = false;

    public function __construct() {
        $this->data = \Yii::$app->getModule('D3Pop3')->ConfigEmailContainerData;
    }

    /**
     * @inheritdoc
     */
    public function featchData() {

        if(!$this->loadedData){
            $this->data = D3pop3ConnectingSettings::find()->all();
            $this->loadedData = true;
        }

        if (!$this->data) {
            return false;
        }
        /** @var D3pop3ConnectingSettings $dataRow */
        $dataRow = array_shift($this->data);
        $settings = Json::decode($dataRow->settings);
        $this->currentData['host'] = $settings['host'];
        $this->currentData['user'] = $settings['user'];
        $this->currentData['password'] = $settings['password'];
        $this->currentData['ssl'] = (int)$settings['ssl']?'SSL':'';
        $this->modelName = $dataRow->model;
        $this->modelSearchField = $dataRow->model_search_field;
        $this->serachByEmailField = $dataRow->search_by_email_field;
        $this->record = $dataRow;
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

        $reflection       = new \ReflectionClass($this->modelName);
        $shortModelName = $reflection->getShortName();

        return [
            [
                'id' => $this->record->sys_company_id,
                'model_name' => $shortModelName,
            ]
        ];
    }

    public function setReceiver(D3pop3Email $email)
    {
        $sendReceiv = new D3pop3SendReceiv();
        $sendReceiv->email_id = $email->id;
        $sendReceiv->direction = D3pop3SendReceiv::DIRECTION_IN;
        $sendReceiv->company_id = $this->record->sys_company_id;
        $sendReceiv->setting_id = $this->record->id;
        $sendReceiv->status = D3pop3SendReceiv::STATUS_NEW;
        $sendReceiv->save();
    }

}
