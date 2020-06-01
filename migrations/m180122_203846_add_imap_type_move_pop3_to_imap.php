<?php

use yii\db\Migration;

class m180122_203846_add_imap_type_move_pop3_to_imap extends Migration
{
    public function safeUp()
    {
        $this->execute("
            ALTER TABLE `d3pop3_connecting_settings` 
            CHANGE COLUMN `type` `type` ENUM('pop3', 'gmail', 'imap') NULL DEFAULT NULL COMMENT 'Type';

            UPDATE `d3pop3_connecting_settings`
            SET `type` = 'imap'
            WHERE `type` = 'pop3';
        ");
    }

    public function safeDown()
    {
        echo "m180122_203846_add_imap_type_move_pop3_to_imap cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180122_203846_add_imap_type_move_pop3_to_imap cannot be reverted.\n";

        return false;
    }
    */
}
