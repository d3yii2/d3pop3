<?php
//namespace d3yii2\d3pop3\migrations;
use yii\db\Migration;

class m171203_153339_connecting_setting_create extends Migration
{
    public function safeUp()
    {

        $this->execute("
            CREATE TABLE `d3pop3_connecting_settings`(
                `id` smallint(5) unsigned NOT NULL  auto_increment , 
                `sys_company_id` smallint(5) unsigned NOT NULL  , 
                `person_id` smallint(5) unsigned NULL  COMMENT 'Person' , 
                `model` text COLLATE ascii_general_ci NULL  COMMENT 'Model' , 
                `model_search_field` varchar(255) COLLATE ascii_general_ci NULL  COMMENT 'Model search field' , 
                `search_by_email_field` varchar(255) COLLATE ascii_general_ci NULL  COMMENT 'Search by email field' , 
                `type` enum('pop3') COLLATE ascii_general_ci NULL  COMMENT 'Type' , 
                `settings` text COLLATE ascii_general_ci NULL  COMMENT 'Settings' , 
                `notes` text COLLATE utf8_general_ci NULL  , 
                PRIMARY KEY (`id`) , 
                KEY `sys_company_id`(`sys_company_id`) , 
                KEY `person_id`(`person_id`) , 
                CONSTRAINT `d3pop3_connecting_settings_ibfk_1` 
                FOREIGN KEY (`sys_company_id`) REFERENCES `d3c_company` (`id`) , 
                CONSTRAINT `d3pop3_connecting_settings_ibfk_2` 
                FOREIGN KEY (`person_id`) REFERENCES `d3p_person` (`id`) 
            ) ENGINE=InnoDB DEFAULT CHARSET='ascii' COLLATE='ascii_general_ci';

            CREATE TABLE `d3pop3_send_receiv`(
                `id` int(10) unsigned NOT NULL  auto_increment , 
                `email_id` int(10) unsigned NOT NULL  COMMENT 'Email' , 
                `direction` enum('in','out') COLLATE latin1_swedish_ci NOT NULL  DEFAULT 'in' COMMENT 'Direction' , 
                `company_id` smallint(5) unsigned NULL  COMMENT 'Company' , 
                `person_id` smallint(5) unsigned NULL  COMMENT 'Person' , 
                `setting_id` smallint(5) unsigned NULL  COMMENT 'Setting' , 
                PRIMARY KEY (`id`) , 
                KEY `email_id`(`email_id`) , 
                KEY `setting_id`(`setting_id`) , 
                KEY `person_id`(`person_id`) , 
                KEY `company_id`(`company_id`) , 
                CONSTRAINT `d3pop3_send_receiv_ibfk_1` 
                FOREIGN KEY (`email_id`) REFERENCES `d3pop3_emails` (`id`) , 
                CONSTRAINT `d3pop3_send_receiv_ibfk_2` 
                FOREIGN KEY (`setting_id`) REFERENCES `d3pop3_connecting_settings` (`id`) , 
                CONSTRAINT `d3pop3_send_receiv_ibfk_3` 
                FOREIGN KEY (`person_id`) REFERENCES `d3p_person` (`id`) , 
                CONSTRAINT `d3pop3_send_receiv_ibfk_4` 
                FOREIGN KEY (`company_id`) REFERENCES `d3c_company` (`id`) 
            ) ENGINE=InnoDB DEFAULT CHARSET='latin1' COLLATE='latin1_swedish_ci';            
       ");
    }

    public function safeDown()
    {
        $this->execute("
            DROP TABLE `d3pop3_send_receiv`;        
            DROP TABLE `d3pop3_connecting_settings`;        
        ");

    }

}
