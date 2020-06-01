<?php

declare(strict_types=1);

namespace d3yii2\d3pop3\components;

use d3system\models\SysCronFinalPoint;
use d3yii2\d3files\models\D3filesModel;
use d3yii2\d3files\models\D3filesModelName;
use d3yii2\d3pop3\models\D3pop3RegexMasks;
use Exception;
use Yii;
use yii\db\Connection;
use yii\db\Expression;
use yii\helpers\FileHelper;
use yii2d3\d3emails\controllers\DownloadFromUrlController;

use function file_get_contents;
use function file_put_contents;
use function implode;
use function pathinfo;
use function rename;
use function strrpos;
use function substr;
use function unlink;

use const DIRECTORY_SEPARATOR;

class DownloadFromUrlComponent implements ComponentRunInterface
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
     * @throws \yii\db\Exception
     */
    public function run(): void
    {
        $getD3pop3Emails = $this
            ->getD3pop3EmailsWithSent();

        $getGlobalDefinedMask = $this
            ->getGlobalMask();

        $transaction = $this
            ->getConnection
            ->beginTransaction();

        foreach ($getD3pop3Emails as $getD3pop3Email) {
            try {
                $getBodyUrls = $this
                    ->downloadFromUrlController
                    ->collectBodyUrls($getD3pop3Email['body']);

                $getBuildBodyUrls = $this
                    ->downloadFromUrlController
                    ->iterateRawUrls($getBodyUrls);

                $getRebuildBodyRawUrls = implode(PHP_EOL, $getBuildBodyUrls);

                if ($getCompanyDefinedMask = $this
                    ->getCompanyMask($getD3pop3Email['company_id'])) {
                    $getCompanyValidUrls = $this
                        ->downloadFromUrlController
                        ->filterValidUrls($getRebuildBodyRawUrls, $getCompanyDefinedMask->regexp);

                    foreach ($getCompanyValidUrls as $getCompanyValidUrl => $getCompanyValidUrlFileName) {
                        $getResponse = $this
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
                    $getGlobalValidUrls = $this
                        ->downloadFromUrlController
                        ->filterValidUrls($getRebuildBodyRawUrls, $getGlobalDefinedMask->regexp);

                    foreach ($getGlobalValidUrls as $getGlobalValidUrl => $getGlobalValidUrlFileName) {
                        $getResponse = $this
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

            $this
                ->storeFinalPointValue(
                    $this->getRoute(),
                    $getD3pop3Email['id']
                );
        }
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
     * @param $getRoute
     * @param $getEmailId
     */
    public function storeFinalPointValue($getRoute, $getEmailId): void
    {
        SysCronFinalPoint::saveFinalPointValue($getRoute, $getEmailId);
    }

    /**
     * @return array|\yii\db\ActiveRecord|null
     */
    public function getGlobalMask()
    {
        return $this->modelD3pop3RegexMasks
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
    }

    /**
     * @param $getCompanyId
     * @return array|\yii\db\ActiveRecord|null
     */
    public function getCompanyMask($getCompanyId)
    {
        return $this->modelD3pop3RegexMasks
            ->where(
                [
                    'type'           => 'manual',
                    'sys_company_id' => $getCompanyId
                ]
            )
            ->one();
    }

    /**
     * @return array
     */
    public function getValidUrls($getMaskRegex, $getDefinedMask): array
    {
        return $this->downloadFromUrlController
            ->filterValidUrls(
                $getMaskRegex,
                $getDefinedMask
            );
    }


    /**
     * @param $getValidUrl
     * @param $getValidUrlFileName
     * @param $modelName
     * @param $modelId
     * @return bool
     * @throws \yii\base\Exception
     */
    final public function store($getValidUrl, $getValidUrlFileName, $modelName, $modelId)
    {
        $getFileName = $getValidUrlFileName;

        $getUploadPath = $this->getUploadDirPath($modelName);
        FileHelper::createDirectory($getUploadPath);

        $getFullPathWithFileName = $getUploadPath . '/' . $getFileName;

        file_put_contents($getFullPathWithFileName, file_get_contents($getValidUrl));

        $model               = new \d3yii2\d3files\models\D3files();
        $model->file_name    = $getFileName;
        $model->add_datetime = new Expression('NOW()');
        $model->user_id      = 0;

        if ($model->save()) {
            $modelMN       = new D3filesModelName();
            $model_name_id = $modelMN->getByName($modelName, true);

            $modelM                = new D3filesModel();
            $modelM->d3files_id    = $model->id;
            $modelM->is_file       = 1;
            $modelM->model_name_id = $model_name_id;
            $modelM->model_id      = $modelId;
            $modelM->save();

            $getFullPathWithFileNameRenamed = $getUploadPath . '/' . $model->id . '.' . pathinfo(
                    $getFileName
                )['extension'];

            rename($getFullPathWithFileName, $getFullPathWithFileNameRenamed);

            return true;
        } else {
            unlink($getFullPathWithFileName);

            return false;
        }
    }

    /**
     * @return string
     */
    public function getUploadDirPath($modelName): string
    {
        $pos            = strrpos($modelName, '\\');
        $modelShortName = false === $pos ? $modelName : substr($modelName, $pos + 1);

        return Yii::$app->getModule('d3files')->uploadDir . DIRECTORY_SEPARATOR . $modelShortName;
    }
}
