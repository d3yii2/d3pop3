<?php
use yii\db\Migration;

class m160815_170102_upgrade extends Migration
{

	/**
	 * Create table
	 */
	public function up()
	{

		$this->execute("
            ALTER TABLE `d3pop3_emails`   
              DROP COLUMN `to`, 
              DROP COLUMN `cc`, 
              ADD COLUMN `email_id` VARCHAR(1000) DEFAULT ''  NOT NULL  COMMENT 'Email Id' AFTER `id`,
              ADD COLUMN `email_datetime` DATETIME NULL AFTER `email_id`,
              ADD COLUMN `body_plain` LONGTEXT NULL  COMMENT 'Body Plain' AFTER `body`,
              ADD COLUMN `from_name` VARCHAR(256) CHARSET utf8 NULL  COMMENT 'From Name' AFTER `from`;
            
            CREATE TABLE `d3pop3_email_address`(  
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `email_id` INT UNSIGNED NOT NULL,
              `address_type` ENUM('To','CC','Replay') COMMENT 'Address type',
              `email_address` VARCHAR(255) COMMENT 'Email',
              `name` VARCHAR(255) COMMENT 'Name',
              PRIMARY KEY (`id`),
              FOREIGN KEY (`email_id`) REFERENCES `d3pop3_emails`(`id`)
            ) ENGINE=INNODB CHARSET=utf8 COLLATE=utf8_bin;
            
            CREATE TABLE `d3pop3_email_error`(  
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `email_id` INT UNSIGNED NOT NULL COMMENT 'Email',
              `message` TEXT NOT NULL COMMENT 'Message',
              PRIMARY KEY (`id`),
              FOREIGN KEY (`email_id`) REFERENCES `blankon20160608`.`d3pop3_emails`(`id`)
            ) ENGINE=INNODB CHARSET=utf8;


        ");
        
	}

	/**
	 * Drop table
	 */
	public function down()
	{
		$this->dropTable('d3pop3_email_address');
        $this->dropTable('d3pop3_email_error');
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
