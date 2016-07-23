<?php

namespace d3yii2\d3pop3\components;

use afinogen89\getmail\message\Message;
use PhpImap\IncomingMail;

interface EmailContainerInerface {

    /**
     * set next pop3 box
     */
    public function featchData();

    /**
     * get current pop3 connection details
     */
    public function getPop3ConnectionDetails();

    /**
     * get recird primary key value, where attach email
     * @param \app\components\D3pop3Email $emailModel
     * @return array list of primary key and model name
     */
    public function getModelForattach(IncomingMail $msg);
}
