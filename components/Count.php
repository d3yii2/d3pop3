<?php


namespace d3yii2\d3pop3\components;


use d3yii2\d3pop3\models\D3pop3SendReceiv;
use yii2d3\d3emails\models\D3pop3Email;

class Count
{
    public static function companyEmailsNewCount() {
        return D3pop3Email::find()
            ->select('count(*) new_count')
            ->innerJoin('d3pop3_send_receiv AS sr','d3pop3_emails.id = sr.email_id')
            ->where([
                'sr.company_id' => \Yii::$app->SysCmp->getActiveCompanyId(),
                'sr.status' => D3pop3SendReceiv::STATUS_NEW
            ])
            ->asArray()
            ->scalar();

    }
}