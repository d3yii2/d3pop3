<?php

namespace d3yii2\d3pop3\models;

use Yii;
use yii\base\Model;
use yii\helpers\Json;

class TypeSmtpForm extends Model
{
    const SSL_ENCRYPTION_NONE = 'none';
    const SSL_ENCRYPTION_SSL  = 'ssl';
    const SSL_ENCRYPTION_TLS  = 'tls';
    
    /** @var string */
    public $host;

    /** @var string */
    public $user;

    /** @var string */
    public $password;

    /** @var bool */
    public $ssl;

    /** @var int */
    public $port;
    
    public function init()
    {
        parent::init();
        $this->port = 587;
        $this->ssl = self::SSL_ENCRYPTION_NONE;
    }

    public function rules()
    {
        return [
            [['host', 'port', 'ssl'], 'required'],
            [['host', 'user', 'password'], 'string'],
            [['port'], 'integer'],
            ['ssl', 'in', 'range' => [self::SSL_ENCRYPTION_NONE, self::SSL_ENCRYPTION_SSL, self::SSL_ENCRYPTION_TLS]],

        ];
    }

    public function attributeLabels()
    {
        return [
            'host' => \Yii::t('d3pop3', 'Host'),
            'user' => \Yii::t('d3pop3', 'User Name'),
            'password' => \Yii::t('d3pop3', 'Password'),
            'ssl' => \Yii::t('d3pop3', 'Use SSL'),
            'port' => \Yii::t('d3pop3', 'Port'),
        ];
    }

    public function exportToJson()
    {
        return Json::encode($this->attributes);
    }

    public function loadFromJson($data)
    {
        if($data) {
            $this->attributes = Json::decode($data);
        }
    }

    /**
     * column ssl value labels
     * @return array
     */
    public static function optsType()
    {
        return [
            self::SSL_ENCRYPTION_NONE => Yii::t('d3pop3', 'None'),
            self::SSL_ENCRYPTION_SSL => Yii::t('d3pop3', 'SSL'),
            self::SSL_ENCRYPTION_TLS => Yii::t('d3pop3', 'TLS'),
        ];
    }
}