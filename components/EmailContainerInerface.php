<?php

namespace d3yii2\d3pop3\components;

use afinogen89\getmail\message\Message;
use d3yii2\d3pop3\models\D3pop3Email;
use unyii2\imap\IncomingMail;

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

    public function getImapPath();
    public function getUserName();
    public function getPassword();
    public function getId();
    public function getMarkAsRead();
    public function getDeleteAfterDays();

    public function setReceiver(D3pop3Email  $email);

    public function dumConnectionData(): array;
}
