<?php

namespace d3yii2\d3pop3\models;

use yii\base\Model;
use yii\helpers\Json;

class TypeGmailForm extends TypeImapForm
{
    public function init()
    {
        parent::init();
        $this->host = 'imap.gmail.com';
        $this->user = $this->user;
        $this->password = $this->password;
        $this->ssl = true;
        $this->smtpPort = 993;
        $this->directory = 'INBOX';
    }

    public function rules()
    {
        return [
            [['user', 'password'], 'required'],
            [['user', 'password'], 'string'],
        ];
    }
}