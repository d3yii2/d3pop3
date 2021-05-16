<?php

namespace d3yii2\d3pop3\components;

use d3yii2\d3pop3\models\D3pop3Email;
use d3yii2\d3pop3\models\D3pop3SendReceiv;
use unyii2\imap\IncomingMail;
use Yii;

class ConfigEmailContainer implements EmailContainerInerface {

    public $data;
    public $currentData;
    public $modelName;
    public $modelSearchField;
    public $serachByEmailField;

    public function __construct() {
        $this->data = Yii::$app->getModule('D3Pop3')->ConfigEmailContainerData;
    }

    /**
     * @inheritdoc
     */
    public function featchData(): bool
    {
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
    public function getPop3ConnectionDetails(): array
    {
        return
                [
                    'host' => $this->currentData['host'],
                    'user' => $this->currentData['user'],
                    'password' => $this->currentData['password'],
                    'ssl' => $this->currentData['ssl'],
        ];
    }
    
    public function getImapPath(): string
    {
        return '{' . $this->currentData['host'] . ':993/imap/ssl}INBOX';
    }

    public function getUserName(){
        return $this->currentData['user'];
    }

    public function getPassword(){
        return $this->currentData['password'];
    }

    public function setReceiver(D3pop3Email $email): void
    {
        $sendReceiv = new D3pop3SendReceiv();
        $sendReceiv->email_id = $email->id;
        $sendReceiv->direction = D3pop3SendReceiv::DIRECTION_IN;
//        $sendReceiv->company_id = $this->record->sys_company_id;
//        $sendReceiv->setting_id = $this->record->id;
        $sendReceiv->status = D3pop3SendReceiv::STATUS_NEW;
        $sendReceiv->save();
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
    public function getModelForattach(IncomingMail $msg): array
    {
        
        switch ($this->serachByEmailField) {
            case 'from':
                $searchValue = $msg->fromAddress;
                break;
            
            case 'to':
            default:            
                $searchValue = [];
                foreach($msg->to as $email => $name){
                    $searchValue[] = $email;
                }
                foreach($msg->cc as $email => $name){
                    $searchValue[] = $email;
                }
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
        
//        $reflection       = new \ReflectionClass($this->modelName);
//        $shortModelName = $reflection->getShortName();
        foreach ($modelData as $td) {
            $ids[] = [
                'id' => $td['id'],
                'model_name' => $this->modelName,
                    ];
        }
        return $ids;
    }

    public function dumConnectionData(): array
    {
        return $this->currentData;
    }
}
