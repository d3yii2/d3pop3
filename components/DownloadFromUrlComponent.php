<?php

declare(strict_types=1);

namespace d3yii2\d3pop3\components;

use d3yii2\d3pop3\models\D3pop3Email;
use d3yii2\d3pop3\models\D3pop3RegexMasks;
use Exception;
use Yii;
use yii\db\Connection;
use yii\db\Expression;
use yii2d3\d3emails\controllers\DownloadFromUrlController;

use function dump;

class DownloadFromUrlComponent
{
    /**
     * @var \yii\db\ActiveQuery
     */
    public $modelD3pop3RegexMasks;

    /**
     * @var DownloadFromUrlController
     */
    public $downloadFromUrlController;

    /**
     * @var Connection
     */
    public $getConnection;

    /**
     * DownloadFromUrlComponent constructor.
     */
    final public function __construct(Connection $getConnection)
    {
        $this->modelD3pop3RegexMasks     = D3pop3RegexMasks::find();
        $this->downloadFromUrlController = new DownloadFromUrlController();
        $this->getConnection             = $getConnection;
    }

    /**
     * @return array|\yii\db\DataReader
     * @throws \yii\db\Exception
     */
    public function getD3pop3EmailsWithSent(): ?array
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
     * @param $getValidUrls
     */
    protected function iterateUrls($getValidUrls): void
    {
        foreach ($getValidUrls as $getValidUrl) {
            $downloadFromUrlController
                ->store(
                    $getValidUrl,
                    D3pop3Email::class,
                    $this->modelD3pop3Email->id
                );
        }
    }

    /**
     * @param array $getModelD3pop3Email
     */
    final public function saveCompanyMask(array $getModelD3pop3Email)
    {
        $transaction       = $this->getConnection->beginTransaction();
        $findRegexMaskBase = $this->modelD3pop3RegexMasks;

        $i = 0;
        try {
            $getCompanyDefinedMask = $findRegexMaskBase
                ->where(
                    [
                        'type'           => 'auto',
                        'sys_company_id' => $getModelD3pop3Email['company_id']
                    ]
                )
                ->one();

            $getCompanyValidUrls = $this->downloadFromUrlController
                ->filterValidUrls(
                    $getModelD3pop3Email['body'],
                    $getCompanyDefinedMask->regexp
                );

//            dump($getCompanyValidUrls);
//            $this->iterateUrls($getCompanyValidUrls);
            return $i++;
        } catch (Exception $e) {
            Yii::error($e->getMessage());
            Yii::error($e->getTraceAsString());
            $transaction->rollBack();
        }
        //        SysCronFinalPoint::saveFinalPointValue($this->getRoute(), $file_Id);
    }


    /**
     * @param array $getModelD3pop3Email
     * @return int
     */
    final public function saveGlobalMask(array $getModelD3pop3Email)
    {
        $transaction       = $this->getConnection->beginTransaction();
        $findRegexMaskBase = $this->modelD3pop3RegexMasks;

        $getGlobalDefinedMask = $findRegexMaskBase
            ->where(
                [
                    'is',
                    'sys_company_id',
                    new Expression('null')
                ]
            )
            ->where(
                [
                    'type' => 'auto',
                ]
            )
            ->one();

        $status = null;
        try {

            $getGlobalValidUrls = $this->downloadFromUrlController
                ->filterValidUrls(
                    $getModelD3pop3Email['body'],
                    $getGlobalDefinedMask->regexp
                );

//            dump($getGlobalValidUrls);

            $status = $getGlobalValidUrls;
//            SysCronFinalPoint::saveFinalPointValue($this->getRoute(), $file_Id);
        } catch (Exception $e) {
            Yii::error($e->getMessage());
            Yii::error($e->getTraceAsString());
            $transaction->rollBack();

            $status = 'bad';
        }

//        dump($status);

        return $status;
    }

}