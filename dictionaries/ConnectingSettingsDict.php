<?php

namespace dictionaries;

namespace d3yii2\d3pop3\dictionaries;

use d3yii2\d3pop3\models\D3pop3ConnectingSettings;

/**
 * Class ConnectingSettingsDict
 * @package d3yii2\d3pop3\dictionaries
 */
class ConnectingSettingsDict
{
    public static function getFromList(): array
    {
        $settings = D3pop3ConnectingSettings::find()
            ->with('sysCompany')
            ->andWhere(['deleted' => 0])
            ->all();

        $data = [];
        foreach ($settings as $i => $setting) {
            /** @var D3pop3ConnectingSettings $setting */
            $name = ( $setting->sysCompany->name ?? '' ) . '<' . $setting->email . '>';
            $data[$setting->email] = $name;
        }

        return $data;
    }
}
