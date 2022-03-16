<?php

namespace d3yii2\d3pop3\components;


use d3yii2\d3activity\components\ActivityRecord;
use d3yii2\d3activity\components\ModelActivityInterface;
use d3yii2\d3pop3\models\D3pop3Email;
use Yii;

class D3pop3D3Activity implements ModelActivityInterface
{
    /**
     * @inheritdoc
     */
    public static function findByIdList(
        array $idList,
        array $filter = [],
        array $additionalFields = []
    ): array
    {
        if (!$idList) {
            return [];
        }
        $list = [];
        $where = [
            'id' => $idList
        ];

        foreach (D3pop3Email::findAll($where) as $email) {
            $record = new ActivityRecord();
            $record->recordId = $email->id;
            $record->label = $email->from . ' --- ' .$email->subject;
            $record->name = Yii::t('d3pop3','Email');
            $record->url = [
                '/d3emails/email/view',
                'id' => $email->id,
                'box' => 'inbox'
            ];

            $list[] = $record;
        }

        return $list;
    }

    public static function findModel(int $id)
    {
        return D3pop3Email::findModel($id);
    }
}