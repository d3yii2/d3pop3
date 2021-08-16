<?php

namespace d3yii2\d3pop3\components;

use d3system\helpers\D3FileHelper;
use unyii2\imap\ImapConnection;

class ConnectionOffice365 extends ConnectionBase
{
    /**
     * @var mixed
     */
    private $_user;
    /**
     * @var mixed
     */
    private $_password;

    public function __construct(int $recordId, array $settings)
    {
        parent::__construct($recordId, $settings);
        $this->_user = $settings['user'];
        $this->_password = $settings['password'];
    }

    public function getLabel(): string
    {
        return self::class . ' recordId: ' . $this->recordId . ' ' . $this->_user;
    }

    public function createSwiftMailerTransportConfig(): array
    {
        return [
            'class' => 'Swift_SmtpTransport',
            'host' => 'smtp.office365.com',
            'port' => 587,
            'username' => $this->_user,
            'password' => $this->_password,
            'encryption' => 'tls'
        ];
    }

    public function createImapConnection(): ImapConnection
    {
        $imapConnection = new ImapConnection();
        $imapConnection->imapPath = '{outlook.office365.com:993/imap/ssl/user=' . $this->_user . '}INBOX';
        $imapConnection->imapLogin = $this->_user;
        $imapConnection->imapPassword = $this->_password;
        $imapConnection->serverEncoding = 'utf-8'; // utf-8 default.
        $imapConnection->attachmentsDir = D3FileHelper::getRuntimeDirectoryPath('imaptemp');

        return $imapConnection;
    }

    public function dumpImapConnection(): string
    {
        $imapConnection = $this->createImapConnection();
        return self::class . PHP_EOL
            . $imapConnection->imapPath . ' userName ' . $this->_user . ' (id=' . $this->recordId . ')';
    }
}
