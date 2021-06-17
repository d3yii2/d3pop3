<?php

use yii\db\Migration;

/**
* Class m210617_220106_connection_settings_type_office365*/
class m210617_220106_connection_settings_type_office365 extends Migration
{
    /**
    * {@inheritdoc}
    */
    public function safeUp()
    {
        $this->execute("alter table d3pop3_connecting_settings modify type enum('pop3', 'gmail', 'imap', 'smtp', 'office365') null comment 'Type'");
    }

    public function safeDown()
    {
        echo "m210617_220106_connection_settings_type_office365 cannot be reverted.\n";
        return false;
    }

}