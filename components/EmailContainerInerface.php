<?php

namespace app\components;

use afinogen89\getmail\message\Message;

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
     * get model name, where attach email
     */
    public function getModelName();

    /**
     * get recird primary key value, where attach email
     * @param \app\components\D3pop3Email $emailModel
     * @return array list of primary keys
     */
    public function getModelPk(Message $msg);
}
