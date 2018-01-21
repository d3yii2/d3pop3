<?php

use yii\db\Migration;

class m180121_210400_gmail_type extends Migration
{
    public function safeUp()
    {
        $this->execute("
            ALTER TABLE `d3pop3_connecting_settings` 
            CHANGE COLUMN `type` `type` ENUM('pop3', 'gmail') NULL DEFAULT NULL COMMENT 'Type' ;        
        ");
    }

    public function safeDown()
    {
        echo "m180121_210400_gmail_type cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180121_210400_gmail_type cannot be reverted.\n";

        return false;
    }
    */
}
