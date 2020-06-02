<?php

declare(strict_types=1);

namespace d3yii2\d3pop3\components;

use d3yii2\d3pop3\models\D3pop3Email;

interface ComponentInterface
{
    /**
     * @param D3pop3Email $getD3pop3Email
     * @return mixed
     */
    public function run(D3pop3Email $getD3pop3Email);
}