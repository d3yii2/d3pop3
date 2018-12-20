<?php

namespace d3yii2\d3pop3\models;

use d3yii2\d3pop3\components\Action;

/**
 * This is the model class for table "d3pop3_connecting_settings".
 */
class D3pop3ConnectingSmtpSettings extends D3pop3ConnectingSettings
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sys_company_id','email'], 'required'],
            [
                'email', 'unique',
                'targetAttribute' => ['email', 'type'],
                'comboNotUnique' => \Yii::t(
                    'd3pop3',
                    'Email: {email} has already been taken for type: {type}',
                    ['email' => $this->email, 'type' => $this->type]) //@FIXME - translate nerāda epasta adresi, bet tikai tipu?
            ],
            [['sys_company_id', 'person_id'], 'integer'],
            [['model', 'type', 'settings', 'notes'], 'string'],
            [['model_search_field', 'search_by_email_field'], 'string', 'max' => 255],
            [['sys_company_id'], 'exist', 'skipOnError' => true, 'targetClass' => \d3yii2\d3pop3\models\D3cCompany::className(), 'targetAttribute' => ['sys_company_id' => 'id']],
            [['person_id'], 'exist', 'skipOnError' => true, 'targetClass' => \d3yii2\d3pop3\models\D3pPerson::className(), 'targetAttribute' => ['person_id' => 'id']],
            ['type', 'in', 'range' => [
                self::TYPE_POP3,
                self::TYPE_GMAIL,
                self::TYPE_IMAP,
                self::TYPE_SMTP,
            ]
            ],
        ];
    }
}