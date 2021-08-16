<?php

namespace d3yii2\d3pop3\components;

use d3system\helpers\D3FileHelper;
use unyii2\imap\ImapConnection;

abstract class ConnectionBase implements ConnectionInterface
{
    /**
     * @var array
     */
    protected $_settings;

    /** @var int  */
    public $recordId;


    public function __construct(int $recordId, array $settings)
    {
        $this->recordId = $recordId;
        $this->_settings = $settings;
    }

}
