<?php


namespace dictionaries;

namespace d3yii2\d3pop3\dictionaries;
use d3yii2\d3pop3\models\D3pop3ConnectingSettings;
use yii\helpers\ArrayHelper;

class ConnectingSettingsDict
{
    public static function getFromList(): array
    {
        return ArrayHelper::map(
            D3pop3ConnectingSettings::find()->asArray()->all(),
            'email',
            'email'
        );

    }
}