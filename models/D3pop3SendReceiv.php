<?php

namespace d3yii2\d3pop3\models;

use Yii;
use \d3yii2\d3pop3\models\base\D3pop3SendReceiv as BaseD3pop3SendReceiv;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "d3pop3_send_receiv".
 */
class D3pop3SendReceiv extends BaseD3pop3SendReceiv
{

public function behaviors()
    {
        return ArrayHelper::merge(
            parent::behaviors(),
            [
                # custom behaviors
            ]
        );
    }

    public function rules()
    {
        return ArrayHelper::merge(
             parent::rules(),
             [
                  # custom validation rules
             ]
        );
    }
}
