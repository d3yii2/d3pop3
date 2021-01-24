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
use d3modules\d3invoices\models\D3cCompany;


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
    public function actionIndex(int $emailId = 0, int $debug = 0): void
    {
        if (!$lastProcessedEmailId = SysCronFinalPoint::getFinalPointValue($this->getRoute())) {
            $lastProcessedEmailId = 0;
        }

        $components = [];

        foreach ($this->module->postProcessComponents as $componentDef) {
            /** @var PostProcessingInterface $c */
            $c = Yii::createObject($componentDef);
            if($debug){
                $c->setDebugOn();
            }
            $components[] = $c;
        }
        foreach ($this->getEmailsToProcess($emailId, $lastProcessedEmailId, 200) as $getD3pop3Email) {
            $this->out('EmailId: ' . $getD3pop3Email->id);
            $company = D3cCompany::find()
                ->innerJoin(
                    'd3pop3_send_receiv',
                    'd3c_company.id = d3pop3_send_receiv.company_id'
                )
                ->andWhere([
                    'd3pop3_send_receiv.in' => D3pop3SendReceiv::DIRECTION_IN,
                    'd3pop3_send_receiv.email_id' => $getD3pop3Email->id
                ])
                ->one();
            if(!$company){
                Yii::error('Can not find company for emai.id: ' . $getD3pop3Email->id);
                continue;
            }
            /** @var $component PostProcessingInterface */
            foreach ($components as $component) {
                $this->out('Component: ' . $component->getName());
                $transaction = Yii::$app
                    ->db
                    ->beginTransaction();

                try {
                    $component->run($getD3pop3Email, $company);
                    $this->outList($component->getLogMessages());
                    $transaction->commit();
                } catch (Exception $e) {
                    $this->out($e->getMessage());
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
