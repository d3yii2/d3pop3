<?php


namespace d3yii2\d3pop3\components;


use d3yii2\d3pop3\models\D3pop3EmailModel;
use yii\helpers\VarDumper;

class EmailModelLogic
{

    /**
     * email attach to model
     * @param $model
     * @param int $emailId
     * @param string $status
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
            \Yii::error(VarDumper::export($emailModel->getErrors()), 'serverError');
            \Yii::error(VarDumper::export($emailModel->getAttributes()), 'serverError');
        }

    }
}