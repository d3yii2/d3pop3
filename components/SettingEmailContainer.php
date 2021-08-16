<?php

namespace d3yii2\d3pop3\components;

use d3yii2\d3pop3\models\D3pop3ConnectingSettings;
use d3yii2\d3pop3\models\D3pop3Email;
use d3yii2\d3pop3\models\D3pop3SendReceiv;
use unyii2\imap\ImapConnection;
use unyii2\imap\IncomingMail;
use Yii;
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
    /**
     * @var array
     */
    private $settings;
    /**
     * @var int
     */
    public $recordId;

    public function __construct()
    {
        
        $d3pop3Module = Yii::$app->getModule('D3Pop3');
        
        if (!$d3pop3Module || !is_array($d3pop3Module->ConfigEmailContainerData)) {
            throw new Exception('D3Pop3 module not configured. Check the README to add the necessary section in config');
        }
        
        
        $this->data = $d3pop3Module->ConfigEmailContainerData;
    }

    /**
     * @inheritdoc
     */
    public function featchData(): bool
    {

        if (!$this->loadedData) {
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
        $this->settings = Json::decode($dataRow->settings);
        $this->recordId = $dataRow->id;

        $this->modelName = $dataRow->model;
        $this->modelSearchField = $dataRow->model_search_field;
        $this->serachByEmailField = $dataRow->search_by_email_field;
        $this->record = $dataRow;
        return true;
    }

    public function fetchEmailSmtpData($email): bool
    {

        if (!$this->loadedData) {
            $this->data = D3pop3ConnectingSettings::findOneByEmail($email);
            $this->loadedData = true;
        }

        if (!$this->data) {
            return false;
        }
        /** @var D3pop3ConnectingSettings $dataRow */
        $dataRow = $this->data;
        $this->settings = Json::decode($dataRow->settings);
        $this->recordId = $dataRow->id;

        $this->modelName = $dataRow->model;
        $this->modelSearchField = $dataRow->model_search_field;
        $this->serachByEmailField = $dataRow->search_by_email_field;
        $this->record = $dataRow;
        return true;
    }

    /**
     * @inheritdoc
     * @deprecated
     */
    public function getPop3ConnectionDetails(): array
    {
        return [
                'host' => $this->currentData['host'],
                'user' => $this->currentData['user'],
                'password' => $this->currentData['password'],
                'ssl' => $this->currentData['ssl'],
                'port' => $this->currentData['port'],
        ];
    }
    
    /**
     * @return array
     */
    public function getEmailSmtpConnectionDetails(): array
    {
        $settingClass = $this->getSettingClass();
        return $settingClass->createSwiftMailerTransportConfig();
    }

    public function getEmailImapConnectionDetails(): ImapConnection
    {
        $settingClass = $this->getSettingClass();
        return $settingClass->createImapConnection();
    }


    public function getId(): int
    {
        return $this->recordId;
    }

    public function getMarkAsRead()
    {
        return $this->settings['markAsRead'] ?? false;
    }

    public function getDeleteAfterDays()
    {
        return $this->settings['deleteAfterDays'] ?? 10;
    }

    /**
     * @inheritdoc
     */
    public function getModelForattach(IncomingMail $msg): array
    {

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

    /**
     * @return \d3yii2\d3pop3\components\ConnectionInterface
     */
    public function getSettingClass(): ConnectionInterface
    {
        $settingClassName = $this->settings['class'] ?? ConnectionDefault::class;
        /** @var \d3yii2\d3pop3\components\ConnectionInterface $settingClass */
        return new $settingClassName($this->recordId, $this->settings);
    }

    public function getImapPath()
    {
        // TODO: Implement getImapPath() method.
    }

    public function getUserName()
    {
        // TODO: Implement getUserName() method.
    }

    public function getPassword()
    {
        // TODO: Implement getPassword() method.
    }

    public function dumConnectionData(): array
    {
        // TODO: Implement dumConnectionData() method.
    }
}
