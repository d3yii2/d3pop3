<?php

namespace d3yii2\d3pop3\components;

use d3modules\d3invoices\models\D3cCompany;
use d3yii2\d3pop3\models\D3pop3Email;

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
     */
    public function run($getD3pop3Email, D3cCompany $company);

    /**
     * @return string [];
     */
    public function getLogMessages(): array;

}