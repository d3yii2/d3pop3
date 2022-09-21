<?php


namespace d3yii2\d3pop3\components;


use d3system\exceptions\D3ActiveRecordException;
use d3yii2\d3pop3\models\D3pop3EmailModel;
use ReflectionClass;

class EmailModelLogic
{


    /**
     * email attach to model
     *
     * @param string $modelClassName
     * @param int $modelId
     * @param int $emailId
     * @param string $status
     * @throws \d3system\exceptions\D3ActiveRecordException
     */
    public static function attachModel(
        string $modelClassName,
        int $modelId,
        int $emailId,
        string $status = D3pop3EmailModel::STATUS_NEW
    ): void
    {
        if (D3pop3EmailModel::findOne([
            'email_id' => $emailId,
            'model_name' => $modelClassName,
            'model_id' => $modelId,
        ])) {
            return;
        }

        $emailModel = new D3pop3EmailModel();
        $emailModel->email_id = $emailId;
        $emailModel->model_name = $modelClassName;
        $emailModel->model_id = $modelId;
        $emailModel->status = $status;
        if (!$emailModel->save()) {
            throw new D3ActiveRecordException($emailModel);
        }
    }

    public static function detachModel($model): bool
    {

        $modelClass = (new ReflectionClass($model))->getName();
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
