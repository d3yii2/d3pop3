<?php


namespace d3yii2\d3pop3\components;


use d3system\exceptions\D3ActiveRecordException;
use d3yii2\d3pop3\models\D3pop3EmailModel;

class EmailModelLogic
{


    /**
     * email attach to model
     *
     * @param $model
     * @param int $emailId
     * @param string $status
     * @throws D3ActiveRecordException
     * @throws \ReflectionException
     */
    public static function attachModel($model, int $emailId, string $status = D3pop3EmailModel::STATUS_NEW): void
    {
        $modelClass = (new \ReflectionClass($model))->getName();

        if (D3pop3EmailModel::findOne([
            'email_id' => $emailId,
            'model_name' => $modelClass,
            'model_id' => $model->id,
        ])) {
            return;
        }

        $emailModel = new D3pop3EmailModel();
        $emailModel->email_id = $emailId;
        $emailModel->model_name = $modelClass;
        $emailModel->model_id = $model->id;
        $emailModel->status = $status;
        if (!$emailModel->save()) {
            throw new D3ActiveRecordException($emailModel);
        }

    }

    public static function detachModel($model): bool
    {

        $modelClass = (new \ReflectionClass($model))->getName();
        foreach(D3pop3EmailModel::findAll([
            'model_name' => $modelClass,
            'model_id' => $model->id,
        ]) as $mailModel) {
            if(!$mailModel->delete()){
                throw new D3ActiveRecordException($mailModel);
            }
        }

        return true;
    }
}