<?php
use yii\db\Migration;

class m160712_170102_init extends Migration
{

	/**
	 * Create table
	 */
	public function up()
	{

		$this->execute("
            CREATE TABLE `d3pop3_emails` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `receive_datetime` datetime NOT NULL COMMENT 'Received',
              `subject` text COMMENT 'Subject',
              `body` longtext COMMENT 'Body',
              `from` varchar(256) DEFAULT NULL COMMENT 'From',
              `to` text COMMENT 'To',
              `cc` text COMMENT 'CC',
              `email_container_class` varchar(256) DEFAULT NULL COMMENT 'Email Container',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;


            CREATE TABLE `d3pop3_email_models` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `email_id` int(10) unsigned DEFAULT NULL COMMENT 'Email',
              `model_name` varchar(50) DEFAULT NULL COMMENT 'Model',
              `model_id` bigint(20) DEFAULT NULL COMMENT 'Model ID',
              `status` enum('New','Read','Delete') DEFAULT 'New' COMMENT 'Status',
              PRIMARY KEY (`id`),
              KEY `email_id` (`email_id`),
              CONSTRAINT `d3pop3_email_models_ibfk_1` FOREIGN KEY (`email_id`) REFERENCES `d3pop3_emails` (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;



            
        ");
        
	}

	/**
	 * Drop table
	 */
	public function down()
	{
		$this->dropTable('d3pop3_email_models');
        $this->dropTable('d3pop3_emails');
	}

	/**
	 * Create table in a transaction-safe way.
	 * Uses $this->up to not duplicate code.
	 */
	public function safeUp()
	{
		$this->up();
	}

	/**
	 * Drop table in a transaction-safe way.
	 * Uses $this->down to not duplicate code.
	 */
	public function safeDown()
	{
		$this->down();
	}
}
