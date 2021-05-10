<?php

namespace d3yii2\d3pop3\components;

use afinogen89\getmail\message\Message;
use d3yii2\d3pop3\models\D3pop3ConnectingSettings;
use d3yii2\d3pop3\models\D3pop3Email;
use d3yii2\d3pop3\models\D3pop3SendReceiv;
use d3yii2\d3pop3\models\TypeSmtpForm;
use unyii2\imap\IncomingMail;
use yii\base\Exception;
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
        
        $d3pop3Module = \Yii::$app->getModule('D3Pop3');
        
        if (!$d3pop3Module || !is_array($d3pop3Module->ConfigEmailContainerData)) {
            throw new Exception('D3Pop3 module not configured. Check the README to add the necessary section in config');
        }
        
        
        $this->data = $d3pop3Module->ConfigEmailContainerData;
    }

    /**
     * @inheritdoc
     */
    public function featchData() {

        if(!$this->loadedData){
            $this->data = D3pop3ConnectingSettings::find()
                ->where(['deleted' => 0])
                ->all();
            $this->loadedData = true;
        }

        if (!$this->data) {
            return false;
        }
        /** @var D3pop3ConnectingSettings $dataRow */
        $dataRow = array_shift($this->data);
        $settings = Json::decode($dataRow->settings);
        $this->currentData['id'] = $dataRow->id;
        $this->currentData['host'] = $settings['host'];
        $this->currentData['user'] = $settings['user'];
        $this->currentData['password'] = $settings['password'];
        $this->currentData['ssl'] = (int)$settings['ssl']?'SSL':'';
        $this->currentData['novalidateCert'] = (int)($settings['novalidateCert']??0);
        $this->currentData['port'] = (int)($settings['port']??993);
        $this->currentData['markAsRead'] = $settings['markAsRead']??true;
        $this->currentData['deleteAfterDays'] = (int)($settings['deleteAfterDays']??10);

        if(isset($settings['directory'])) {
            $this->currentData['directory'] = $settings['directory'];
        } else {
            $this->currentData['directory'] = 'INBOX';
        }

        $this->modelName = $dataRow->model;
        $this->modelSearchField = $dataRow->model_search_field;
        $this->serachByEmailField = $dataRow->search_by_email_field;
        $this->record = $dataRow;
        return true;
    }
    
    /**
     * @inheritdoc
     */
    public function fetchEmailSmtpData($email) {

        if(!$this->loadedData){
            $this->data = D3pop3ConnectingSettings::findOneByEmail($email);
            $this->loadedData = true;
        }

        if (!$this->data) {
            return false;
        }
        /** @var D3pop3ConnectingSettings $dataRow */
        $dataRow = $this->data;
        $settings = Json::decode($dataRow->settings);
        $this->currentData['id'] = $dataRow->id;
        $this->currentData['host'] = $settings['smtpHost']??$settings['host'];
        $this->currentData['user'] = $settings['smtpUser']??$settings['user'];
        $this->currentData['password'] = $settings['smtpPassword']??$settings['password'];
        $this->currentData['ssl'] = $settings['smtpSsl']?? $settings['ssl'];
        $this->currentData['port'] = (int)($settings['smtpPort']?? $settings['port']);

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
        return [
                'host' => $this->currentData['host'],
                'user' => $this->currentData['user'],
                'password' => $this->currentData['password'],
                'ssl' => $this->currentData['ssl'],
                'port' => $this->currentData['port'],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function getEmailSmtpConnectionDetails() {
        return [
                'host' => $this->currentData['host'],
                'user' => $this->currentData['user'],
                'password' => $this->currentData['password'],
                'ssl' => $this->currentData['ssl'],
                'port' => $this->currentData['port'],
        ];
    }
    
    public function getImapPath(): string
    {
        $ssl = $this->currentData['ssl']?'/ssl':'';
        $novalidateCert = $this->currentData['novalidateCert']?'/novalidate-cert':'';
        return '{'
            . $this->currentData['host']
            . ':'
            . $this->currentData['port']
            . '/imap'
            . $ssl
            . $novalidateCert
            . '}'
            . $this->currentData['directory'];
    }

    public function getUserName(){
        return $this->currentData['user'];
    }

    public function getPassword(){
        return $this->currentData['password'];
    }

    public function getId(){
        return $this->currentData['id'];
    }

    public function getMarkAsRead()
    {
        return $this->currentData['markAsRead'];
    }

    public function getDeleteAfterDays()
    {
        return $this->currentData['deleteAfterDays'];
    }

    /**
     * @inheritdoc
     */
    public function getModelForattach(IncomingMail $msg) {

//        $reflection       = new \ReflectionClass($this->modelName);
//        $shortModelName = $reflection->getShortName();

        return [
            [
                'id' => $this->record->sys_company_id,
                'model_name' => $this->modelName,
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
