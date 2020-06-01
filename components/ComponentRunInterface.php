<?php

declare(strict_types=1);

namespace d3yii2\d3pop3\components;

interface ComponentRunInterface
{
    /**
     * @param array $getD3pop3Email
     * @return mixed
     */
    public function run(array $getD3pop3Email);
}