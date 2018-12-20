<?php

namespace d3yii2\d3pop3\models;

class TypePop3Form extends TypeImapForm
{
    public $mailbox;

    public function init()
    {}

    public function rules()
    {
        return [
            [['mailbox', 'password'], 'required'],
            //[['host'], 'required'],
            [['mailbox', 'host', 'password'], 'string'],
            [['mailbox'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'mailbox' => \Yii::t('d3pop3', 'Mailbox Name'),
        ];
    }
}
