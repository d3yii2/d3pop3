<?php

namespace d3yii2\d3pop3\models;

use Yii;
use \d3yii2\d3pop3\models\base\D3pop3EmailAddress as BaseD3pop3EmailAddress;

/**
 * This is the model class for table "d3pop3_email_address".
 */
class D3pop3EmailAddress extends BaseD3pop3EmailAddress
{
    /**
     * prepare address for Yii2 mailer
     * @return array|string
     */
    public function fullAddress()
    {
        if(!$this->name){
            return $this->email_address;
        }

        return [$this->email_address => $this->name];
    }
}
