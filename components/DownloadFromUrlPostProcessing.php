<?php

declare(strict_types=1);

namespace d3yii2\d3pop3\components;

use d3system\helpers\D3FileHelper;
use d3yii2\d3files\models\D3files;
use d3yii2\d3pop3\models\D3pop3Email;
use d3yii2\d3pop3\models\D3pop3RegexMasks;
use d3yii2\d3pop3\models\D3pop3SendReceiv;
use Yii;
use yii\base\Exception;
use yii\db\ActiveQuery;
use yii\helpers\BaseHtml;
use yii\helpers\FileHelper;


class DownloadFromUrlPostProcessing implements PostProcessingInterface
{
    /**
     * @var ActiveQuery
     */
    public $modelD3pop3RegexMasks;
    /**
     * @var string[]
     */
    private $messages;

    /**
     * @param D3pop3Email $getD3pop3Email
     * @throws Exception
     */
    final public function run(D3pop3Email $getD3pop3Email): void
    {

        $this->messages = [];
        $this->messages[] = ' emailId: ' . $getD3pop3Email->id;
        if(!$getD3pop3Email->body){
            $this->messages[] = ' empty body - ignore';
            return;
        }
        $getGlobalDefinedMaskList = $this->getGlobalMask();
        $processedUrlList = [];
        foreach (array_merge($getGlobalDefinedMaskList, $this->getCompanyMask($getD3pop3Email)) as $mask) {
            if (!preg_match_all($mask, $getD3pop3Email->body, $match)) {
                continue;
            }
            foreach ($match['url'] as $id => $url) {
                if (in_array($url, $processedUrlList, true)) {
                    continue;
                }
                $processedUrlList[] = $url;
                $url = BaseHtml::decode($url);
                $this->messages[] = 'Load ' . $url;
                if (!self::validateUrl($url)) {
                    $this->messages[] = 'Invalid URL';
                    Yii::error('Invalid URL: ' . $url);
                    continue;
                }
                if (!$this->store(
                    $url,
                    D3pop3Email::class,
                    (int)$getD3pop3Email->id
                )) {
                    continue;
                }
            }
        }

    }

    public function getLogMessages(): array
    {
        return $this->messages;
    }

    public function getName(): string
    {
        return 'URL to attachment';
    }

    /**
     * @return array
     */
    final public function getGlobalMask(): array
    {
        return D3pop3RegexMasks::find()
            ->select('regexp')
            ->where([
                'sys_company_id' => null,
                'type' => 'auto',
            ])
            ->column();
    }

    /**
     * @param D3pop3Email $getD3pop3Email
     * @return array
     */
    final public function getCompanyMask(D3pop3Email $getD3pop3Email): array
    {
        foreach ($getD3pop3Email->d3pop3SendReceivs as $sendReceive) {
            if ($sendReceive->direction === D3pop3SendReceiv::DIRECTION_IN) {
                return $this->modelD3pop3RegexMasks
                    ->select('regexp')
                    ->where([
                        'type' => 'manual',
                        'sys_company_id' => $sendReceive->company_id
                    ])
                    ->column();

            }
        }
        return [];
    }

    /**
     * @param $url string
     * @param $modelName string
     * @param $modelId int
     * @return bool
     * @throws Exception
     */
    final public function store($url, $modelName, int $modelId): bool
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->messages[] = 'Request Error:' . curl_error($ch);
            return false;
        }
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpcode !== 200) {
            $this->messages[] = 'Invalid response code ' . $httpcode;
            return false;
        }
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $reDispo = '/^Content-Disposition: .*?filename=(?<f>[^\s]+|\x22[^\x22]+\x22)\x3B?.*$/m';

        if (preg_match($reDispo, $header, $mDispo)) {
            $fileName = trim($mDispo['f'], ' ";');
        } else {
            $this->messages[] = 'Can not get file name';
            return false;
        }
        $content = substr($response, $header_size);

        $tempFile = D3FileHelper::getTempFile();
        file_put_contents($tempFile, $content);
        $fileTypes = '/(gif|pdf|dat|jpe?g|png|doc|docx|xls|xlsx|htm|txt|log|mxl|xml|zip)$/i';

        D3files::saveFile($fileName, $modelName, $modelId, $tempFile, $fileTypes);
        if (file_exists($tempFile)) {
            FileHelper::unlink($tempFile);
        }
        return true;
    }


    /**
     * URL validation created from UrlValidator
     * @param $value
     * @return bool
     */
    public static function validateUrl($value): bool
    {

        if (!is_string($value)) {
            return false;
        }

        // make sure the length is limited to avoid DOS attacks
        if (strlen($value) > 2000) {
            return false;
        }

        $pattern = '/^(http|https):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(?::\d{1,5})?(?:$|[?\/#])/i';
        $value = preg_replace_callback('/:\/\/([^\/]+)/', static function ($matches) {
            return '://' . idn_to_ascii($matches[1], IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
        }, $value);

        return (bool)preg_match($pattern, $value);

    }
}
