<?php

namespace d3yii2\d3pop3\controllers;

use d3system\commands\D3CommandController;
use d3system\models\SysCronFinalPoint;
use Exception;
use Yii;

use function array_count_values;
use function array_key_exists;
use function class_exists;
use function count;
use function dd;
use function dump;
use function is_array;
use function is_string;
use function property_exists;

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
        foreach ($this->getD3pop3EmailsWithSent() as $getD3pop3Email) {
            foreach (Yii::$app->components['postProcessComponents']['class'] as $index => $component) {
                $getConfig = @Yii::$app->components['postProcessComponents']['config'][$index];

                $transaction = $this
                    ->getConnection
                    ->beginTransaction();

                try {
                    if (class_exists($component)) {

                        if (@count($getConfig) === 0) {
                            $getConfig = null;
                        }

                        $getComponent = new $component($this->getConnection, $getConfig);
                        $getResponse  = $getComponent->run($getD3pop3Email);

                        if (is_string($getResponse)) {
                            $this->out($getResponse);
                        } else {
                            $this->out('Unable processing' . $getD3pop3Email['id']);
                        }
                    }

                    $transaction->commit();
                } catch (Exception $e) {
                    Yii::error($e->getMessage());
                    Yii::error($e->getTraceAsString());
                    $transaction->rollBack();
                }

                $this->storeFinalPointValue(
                    $this->getRoute(),
                    $getD3pop3Email['id']
                );
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
