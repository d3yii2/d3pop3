<?php

namespace d3yii2\d3pop3\components;

use d3modules\d3invoices\models\D3cCompany;
use d3yii2\d3pop3\models\D3pop3Email;
use phpDocumentor\Reflection\Types\Object_;

/**
 * Interface PostProcessingInterface
 * Object create one time, but run multiple
 * @package d3yii2\d3pop3\components
 */
interface PostProcessingInterface
{

    public function setDebugOn(): void;

    public function getName(): string;

    /**
     * @param $getD3pop3Email
     * @param object $company
     */
    public function run($getD3pop3Email, object $company);

    /**
     * @return string [];
     */
    public function getLogMessages(): array;

}