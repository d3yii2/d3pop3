<?php

namespace d3yii2\d3pop3\controllers;

use d3system\commands\D3CommandController;
use d3system\models\SysCronFinalPoint;
use Exception;
use Yii;

use function class_exists;
use function is_string;

class PostProcessingController extends D3CommandController
{
    /**
     * @var \yii\db\Connection
     */
    public $getConnection;

    /**
     * PostProcessingController constructor.
     * @param $id
     * @param $module
     * @param array $config
     */
    public function __construct($id, $module, $config = [])
    {
        $this->getConnection = $this->getConnection();

        parent::__construct($id, $module, $config);
    }

    /**
     * default action
     * @return void
     */
    public function actionIndex(): void
    {
        $getD3pop3Emails = $this
            ->getD3pop3EmailsWithSent();

        foreach (Yii::$app->getModule('D3Pop3')->params as $component) {
            foreach ($getD3pop3Emails as $getD3pop3Email) {
                $transaction = $this
                    ->getConnection
                    ->beginTransaction();

                try {
                    if (class_exists($component)) {
                        $getComponent = new $component();
                        $getResponse  = $getComponent->run($getD3pop3Email);

                        if (is_string($getResponse)) {
                            $this->out($getResponse);
                        }
                    }

                    $this->storeFinalPointValue(
                        $this->getRoute(),
                        $getD3pop3Email['id']
                    );

                    $transaction->commit();
                } catch (Exception $e) {
                    Yii::error($e->getMessage());
                    Yii::error($e->getTraceAsString());
                    $transaction->rollBack();
                }
            }
        }
    }

    /**
     * @return array|\yii\db\DataReader
     * @throws \yii\db\Exception
     */
    final public function getD3pop3EmailsWithSent(): ?array
    {
        return $this->getConnection
            ->createCommand(
                "SELECT d3pop3_emails.id, d3pop3_emails.body, d3pop3_send_receiv.company_id FROM `d3pop3_send_receiv`  
                        LEFT JOIN d3pop3_emails
                        ON d3pop3_emails.id = d3pop3_send_receiv.email_id
                        WHERE d3pop3_emails.body IS NOT NULL"
            )
            ->queryAll();
    }

    /**
     * @param $getRoute
     * @param $getEmailId
     */
    final public function storeFinalPointValue($getRoute, $getEmailId): void
    {
        SysCronFinalPoint::saveFinalPointValue($getRoute, $getEmailId);
    }
}
