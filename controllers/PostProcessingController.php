<?php

namespace d3yii2\d3pop3\controllers;

use d3system\commands\D3CommandController;
use d3system\models\SysCronFinalPoint;
use d3yii2\d3pop3\models\D3pop3Email;
use Exception;
use Yii;

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
        foreach ($this->getEmailsToProcess() as $getD3pop3Email) {
            foreach (Yii::$app->components['postProcessComponents']['class'] as $component) {
                $transaction = $this
                    ->getConnection
                    ->beginTransaction();

                try {
                    $getComponent = new $component($this->getConnection);
                    $getResponse  = $getComponent->run($getD3pop3Email);

                    if (is_string($getResponse)) {
                        $this->out($getResponse);
                    } else {
                        $this->out('Unable processing' . $getD3pop3Email->id);
                    }

                    $transaction->commit();
                } catch (Exception $e) {
                    Yii::error($e->getMessage());
                    Yii::error($e->getTraceAsString());
                    $transaction->rollBack();
                }

                SysCronFinalPoint::saveFinalPointValue($this->getRoute(), (string)$getD3pop3Email->id);
            }
        }
    }

    /**
     * @param int $getLimit
     * @return array|null
     */
    final public function getEmailsToProcess(int $getLimit = 100): ?array
    {
        $lastProcesedEmailId = SysCronFinalPoint::find()
            ->select('value')
            ->where(
                [
                    'route' => $this->getRoute(),
                ]
            )
            ->max('value');

        return D3pop3Email::find()
            ->where(
                [
                    '>',
                    'id',
                    $lastProcesedEmailId ?? 0
                ]
            )
            ->limit($getLimit)
            ->all();
    }
}
