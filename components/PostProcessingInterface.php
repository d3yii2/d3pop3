<?php

namespace d3yii2\d3pop3\components;

use d3yii2\d3pop3\models\D3pop3Email;

/**
 * Interface PostProcessingInterface
 * Object create one time, but run multiple
 * @package d3yii2\d3pop3\components
 */
interface PostProcessingInterface
{
    public function getName(): string;

    /**
     * @param D3pop3Email $getD3pop3Email
     */
    public function run(D3pop3Email $getD3pop3Email);

    /**
     * @return string [];
     */
    public function getLogMessages(): array;

}