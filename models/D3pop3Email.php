<?php

namespace d3yii2\d3pop3\models;


use \d3yii2\d3pop3\models\base\D3pop3Email as BaseD3pop3Email;
use Yii;

/**
 * This is the model class for table "d3pop3_emails".
 */
class D3pop3Email extends BaseD3pop3Email
{
    public function delete()
    {
        $isOtherCompanies = false;
        /** @var D3pop3SendReceiv $sendReceive */
        foreach (D3pop3SendReceiv::find()->where(['email_id' => $this->id])->all() as $sendReceive){
            if($sendReceive->company_id !== Yii::$app->SysCmp->getActiveCompanyId()){
                $isOtherCompanies = true;
                continue;
            }
            $sendReceive->delete();
        }

        if(!$isOtherCompanies){
            /** @var D3pop3EmailAddress $address */
            foreach ($this->getD3pop3EmailAddresses()->all() as $address){
                $address->delete();
            }

            /** @var D3pop3EmailModel $models */
            foreach ($this->getD3pop3EmailModels()->all() as $models){
                $models->delete();
            }

            /** @var D3pop3EmailError $error */
            foreach ($this->getD3pop3EmailErrors()->all() as $error){
                $error->delete();
            }
            return parent::delete();
        }

        return 1;

    }
}
