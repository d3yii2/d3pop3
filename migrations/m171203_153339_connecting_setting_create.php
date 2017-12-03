<?php
namespace d3yii2\d3pop3\migrations;
use yii\db\Migration;

class m171203_153339_connecting_setting_create extends Migration
{
    public function safeUp()
    {
        echo 'aaaa';
        $this->execute("
            CREATE TABLE `d3pop3_connecting_settings`(  
              `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `sys_company_id` SMALLINT UNSIGNED NOT NULL,
              `model` TEXT COMMENT 'Model',
              `model_search_field` VARCHAR(255) COMMENT 'Model search field',
              `search_by_email_field` VARCHAR(255) COMMENT 'Search by email field',
              `type` ENUM('pop3') COMMENT 'Type',
              `settings` TEXT CHARSET ASCII COMMENT 'Settings',
              `notes` TEXT CHARSET utf8,
              PRIMARY KEY (`id`)
            ) ENGINE=INNODB CHARSET=ASCII;
       ");
    }

    public function safeDown()
    {
        $this->execute("
            DROP TABLE `d3pop3_connecting_settings`;        
        ");

    }

}
