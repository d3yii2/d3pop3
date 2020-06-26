<?php

namespace d3yii2\d3pop3\controllers;

use d3system\commands\D3CommandController;
use d3system\exceptions\D3ActiveRecordException;
use d3system\models\SysCronFinalPoint;
use d3yii2\d3pop3\components\PostProcessingInterface;
use d3yii2\d3pop3\d3pop3;
use d3yii2\d3pop3\models\D3pop3Email;
use d3yii2\d3pop3\models\D3pop3SendReceiv;
use Exception;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Class PostProcessingController
 * @package d3yii2\d3pop3\controllers
 * @property d3pop3 $module
 */
class PostProcessingController extends D3CommandController
{

    /**
     * default action
     * @param int $emailId
     * @return void
     * @throws D3ActiveRecordException
     * @throws InvalidConfigException
     */
    public function actionIndex(int $emailId = 0): void
    {
        if (!$lastProcessedEmailId = SysCronFinalPoint::getFinalPointValue($this->getRoute())) {
            $lastProcessedEmailId = 0;
        }

        $components = [];

        foreach ($this->module->postProcessComponents as $componentDef) {
            $components[] = Yii::createObject($componentDef);
        }
        foreach ($this->getEmailsToProcess($emailId, $lastProcessedEmailId, 200) as $getD3pop3Email) {
            /** @var $component PostProcessingInterface */
            foreach ($components as $component) {
                $this->out('Component: ' . $component->getName());
                $transaction = Yii::$app
                    ->db
                    ->beginTransaction();

                try {
                    $component->run($getD3pop3Email);
                    $this->outList($component->getLogMessages());
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
     * @param int $emailId
     * @param int $lastProcessedEmailId
     * @param int $limit
     * @return D3pop3Email[]|null
     */
    final public function getEmailsToProcess(int $emailId, int $lastProcessedEmailId, int $limit): ?array
    {
        $activeQuery = D3pop3Email::find();

        if($emailId){
            $activeQuery->where(['id' => $emailId]);
        }else {
            $activeQuery
                ->innerJoin(
                    'd3pop3_send_receiv',
                    'd3pop3_emails.id = d3pop3_send_receiv.email_id'
                )
                ->where(
                    [
                        '>',
                        'd3pop3_emails.id',
                        $lastProcessedEmailId
                    ]
                )
                ->andWhere([
                    '`d3pop3_send_receiv`.`direction`' => D3pop3SendReceiv::DIRECTION_IN
                ]);
        }
        return $activeQuery
            ->orderBy(['d3pop3_emails.id'=>SORT_ASC])
            ->limit($limit)
            ->all();
    }
}
