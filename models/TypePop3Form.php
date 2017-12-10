<?php

namespace d3yii2\d3pop3\models;

use yii\base\Model;
use yii\helpers\Json;

class TypePop3Form extends Model
{
    /** @var string */
    public $host;

    /** @var string */
    public $user;

    /** @var string */
    public $password;

    /** @var bool */
    public $ssl = false;

    public function rules()
    {
        return [
            [['host', 'user', 'password'], 'required'],
            [['host', 'user', 'password'], 'string'],
            [['ssl'], 'boolean', 'trueValue' => '1', 'falseValue' => '0'],

        ];
    }

    public function attributeLabels()
    {
        return [
            'host' => \Yii::t('d3pop3', 'Host'),
            'user' => \Yii::t('d3pop3', 'User Name'),
            'password' => \Yii::t('d3pop3', 'Password'),
            'ssl' => \Yii::t('d3pop3', 'Use SSL'),
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