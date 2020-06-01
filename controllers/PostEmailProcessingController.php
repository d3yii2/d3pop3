<?php

declare(strict_types=1);

namespace d3yii2\d3pop3\controllers;

use d3system\commands\D3CommandController;
use d3yii2\d3pop3\components\DownloadFromUrlComponent;
use Exception;
use Yii;
use yii\db\Connection;

use function implode;

class PostEmailProcessingController extends D3CommandController
{
    /**
     * @var DownloadFromUrlComponent
     */
    private $downloadFromUrlComponent;

    public function init(): void
    {
        $this->downloadFromUrlComponent = new DownloadFromUrlComponent($this->getConnection());

        parent::init();
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return parent::getConnection();
    }

    /**
     * @throws \yii\db\Exception
     */
    public function actionIndex(): void
    {
        $getD3pop3Emails = $this
            ->downloadFromUrlComponent
            ->getD3pop3EmailsWithSent();

        $getGlobalDefinedMask = $this->downloadFromUrlComponent
            ->getGlobalMask();

        $transaction = $this->downloadFromUrlComponent
            ->getConnection
            ->beginTransaction();

        foreach ($getD3pop3Emails as $getD3pop3Email) {
            try {
                $getBodyUrls = $this->downloadFromUrlComponent
                    ->downloadFromUrlController
                    ->collectBodyUrls($getD3pop3Email['body']);

                $getBuildBodyUrls      = $this->downloadFromUrlComponent
                    ->downloadFromUrlController
                    ->iterateRawUrls($getBodyUrls);

                $getRebuildBodyRawUrls = implode(PHP_EOL, $getBuildBodyUrls);

                if ($getCompanyDefinedMask = $this->downloadFromUrlComponent
                    ->getCompanyMask($getD3pop3Email['company_id'])) {
                    $getCompanyValidUrls = $this->downloadFromUrlComponent
                        ->downloadFromUrlController
                        ->filterValidUrls($getRebuildBodyRawUrls, $getCompanyDefinedMask->regexp);

                    foreach ($getCompanyValidUrls as $getCompanyValidUrl => $getCompanyValidUrlFileName) {
                        $getResponse = $this->downloadFromUrlComponent
                            ->store(
                                $getCompanyValidUrl,
                                $getCompanyValidUrlFileName,
                                D3pop3Email::class,
                                $getD3pop3Email['id']
                            );

                        if ($getResponse) {
                            $this->out('Finishing processing company emailId: ' . $getD3pop3Email['id']);
                        } else {
                            $this->out('Failed processing company emailId: ' . $getD3pop3Email['id']);
                        }
                    }
                } else {
                    $getGlobalValidUrls = $this->downloadFromUrlComponent
                        ->downloadFromUrlController
                        ->filterValidUrls($getRebuildBodyRawUrls, $getGlobalDefinedMask->regexp);

                    foreach ($getGlobalValidUrls as $getGlobalValidUrl => $getGlobalValidUrlFileName) {
                        $getResponse = $this->downloadFromUrlComponent
                            ->store(
                                $getGlobalValidUrl,
                                $getGlobalValidUrlFileName,
                                D3pop3Email::class,
                                $getD3pop3Email['id']
                            );

                        if ($getResponse) {
                            $this->out('Finishing processing global emailId: ' . $getD3pop3Email['id']);
                        } else {
                            $this->out('Failed processing global emailId: ' . $getD3pop3Email['id']);
                        }
                    }
                }

                $transaction->commit();
            } catch (Exception $e) {
                Yii::error($e->getMessage());
                Yii::error($e->getTraceAsString());
                $transaction->rollBack();
            }

            $this->downloadFromUrlComponent
                ->storeFinalPointValue(
                    $this->getRoute(),
                    $getD3pop3Email['id']
                );
        }
    }
}
