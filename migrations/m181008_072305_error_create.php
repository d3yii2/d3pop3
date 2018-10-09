<?php

use yii\db\Migration;

/**
 * Class m181008_072305_error_create
 */
class m181008_072305_error_create extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE `d3pop3_email_error`(
                `id` INT(10) UNSIGNED NOT NULL  AUTO_INCREMENT , 
                `email_id` INT(10) UNSIGNED NOT NULL  COMMENT \'Email\' , 
                `message` TEXT COLLATE utf8_general_ci NOT NULL  COMMENT \'Message\' , 
                PRIMARY KEY (`id`) , 
                KEY `email_id`(`email_id`) , 
                CONSTRAINT `d3pop3_email_error_ibfk_1` 
                FOREIGN KEY (`email_id`) REFERENCES `d3pop3_emails` (`id`) 
            ) ENGINE=INNODB DEFAULT CHARSET=\'utf8\' COLLATE=\'utf8_general_ci\';
        ');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('d3pop3_email_error');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181008_072305_error_create cannot be reverted.\n";

        return false;
    }
    */
}
