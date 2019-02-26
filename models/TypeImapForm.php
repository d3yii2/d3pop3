<?php

namespace d3yii2\d3pop3\models;

use yii\base\Model;
use yii\helpers\Json;

class TypeImapForm extends Model
{
    /** @var string */
    public $host;

    /** @var string */
    public $user;

    /** @var string */
    public $password;

    /** @var bool */
    public $ssl = false;

    /** @var int */
    public $port;

    /** @var string */
    public $directory;

    /** @var bool mark mail as read after reading */
    public $markAsRead = true;

    /** @var int  */
    public $deleteAfterDays = 10;

    /** @var bool */
    public $novalidateCert = false;

    public function init()
    {
        parent::init();
        $this->port = 993;
        $this->directory = 'INBOX';
    }

    public function rules()
    {
        return [
            [['markAsRead'], 'default', 'value'=> 1],
            [['deleteAfterDays'], 'default', 'value'=> 10],
            [['host', 'user', 'password', 'port', 'directory'], 'required'],
            [['host', 'user', 'password'], 'string'],
            [['port', 'deleteAfterDays'], 'integer'],
            [['ssl', 'markAsRead'], 'boolean', 'trueValue' => '1', 'falseValue' => '0'],

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
            'markAsRead' => \Yii::t('d3pop3', 'Mark As Read'),
            'deleteAfterDays' => \Yii::t('d3pop3', 'Delete messages after days'),
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
}