<?php

namespace d3yii2\d3pop3\models;

use yii2d3\d3emails\logic\ConnectionSettings;

class TypeSmtpOffice365Form extends TypeSmtpForm
{
   
    public function init()
    {
        parent::init();
        $this->smtpPort = 587;
        $this->ssl = self::SSL_ENCRYPTION_TLS;
        $this->host = 'smtp.office365.com';
    }
}