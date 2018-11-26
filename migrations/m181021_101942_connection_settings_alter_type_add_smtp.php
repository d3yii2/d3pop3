<?php

use yii\db\Migration;

/**
 * Class m181021_101942_connection_settings_alter_type_add_smtp
 */
class m181021_101942_connection_settings_alter_type_add_smtp extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $sql = "alter table d3pop3_connecting_settings modify type enum('pop3', 'gmail', 'imap', 'smtp') null comment 'Type'";

        $this->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m181021_101942_connection_settings_alter_type_add_smtp cannot be reverted.\n";

        return false;
    }
}