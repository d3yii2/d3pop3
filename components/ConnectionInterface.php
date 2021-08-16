<?php

namespace d3yii2\d3pop3\components;

use unyii2\imap\ImapConnection;

/**
 * @property int $recordId
 */
interface ConnectionInterface
{

    public function __construct(int $recordId, array $settings);

    public function createSwiftMailerTransportConfig(): array;
    public function createImapConnection(): ImapConnection;
    public function dumpImapConnection(): string;
    public function getLabel(): string;

}