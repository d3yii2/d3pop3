<?php

namespace d3yii2\d3pop3\components;

use d3system\helpers\D3FileHelper;
use unyii2\imap\ImapConnection;

class ConnectionDefault extends ConnectionBase
{
    public function getLabel(): string
    {
        return self::class . ' recordId: ' . $this->recordId . ' ' . $this->_settings['smtpUser'] ?? $this->_settings['user'];
    }
    public function createSwiftMailerTransportConfig(): array
    {
        $transportConfig = [
            'class' => 'Swift_SmtpTransport',
            'host' => $this->_settings['smtpHost']??$this->_settings['host'],
            'port' => $this->_settings['smtpPort'],
        ];

        if ($this->_settings['smtpUser'] ?? $this->_settings['user'] ?? false) {
            $transportConfig['username'] = $this->_settings['smtpUser'] ?? $this->_settings['user'];
        }

        if ($this->_settings['smtpPassword'] ?? $this->_settings['password']  ?? false) {
            $transportConfig['password'] = $this->_settings['smtpPassword'] ?? $this->_settings['password'];
        }

        if (($this->_settings['smtpSsl'] ?? $this->_settings['ssl'] ?? false) && 'ssl' !== $this->_settings['ssl']) {
            $transportConfig['encryption'] = $this->_settings['smtpSsl']?? $this->_settings['ssl'];

            //@FIXME - should be self signed certificates supported?
            //\Yii::$app->mailer->setStreamOptions(
            //['ssl' => ['allow_self_signed' => true, 'verify_peer' => false]]);
        }

        return $transportConfig;
    }

    public function createImapConnection(): ImapConnection
    {
        $tempDirectory = D3FileHelper::getRuntimeDirectoryPath('imaptemp');
        $imapConnection = new ImapConnection();
        $imapConnection->imapPath = $this->getImapPath();
        $imapConnection->imapLogin = $this->_settings['user'];
        $imapConnection->imapPassword = $this->_settings['password'];
        $imapConnection->serverEncoding = 'utf-8'; // utf-8 default.
        $imapConnection->attachmentsDir = $tempDirectory;

        return $imapConnection;
    }

    private function getImapPath(): string
    {
        $ssl = $this->_settings['imapSsl'] ? '/' . $this->_settings['imapSsl'] : '';
        $novalidateCert = $this->_settings['novalidateCert']?'/novalidate-cert':'';
        return '{'
            . $this->_settings['host']
            . ':'
            . $this->_settings['port']
            . '/imap'
            . $ssl
            . $novalidateCert
            . '}'
            . ($this->_settings['directory'] ?? 'INBOX');
    }

    public function dumpImapConnection(): string
    {
        $imapConnection = $this->createImapConnection();
        return self::class . PHP_EOL
            . $imapConnection->imapPath . ' userName ' . $imapConnection->imapLogin . ' (id=' . $this->recordId . ')';
    }
}
