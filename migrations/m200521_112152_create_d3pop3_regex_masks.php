<?php

use yii\db\Migration;

/**
 * Class m200521_112152_create_d3pop3_regex_masks*/
class m200521_112152_create_d3pop3_regex_masks extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute(
            '
                CREATE TABLE `d3pop3_regex_masks` 
                ( `id` INT NOT NULL AUTO_INCREMENT , `sys_company_id` SMALLINT UNSIGNED NULL , 
                `type` ENUM(\'auto\',\'manual\') NOT NULL , 
                `name` VARCHAR(255) NOT NULL , 
                `regexp` VARCHAR(255) NOT NULL , 
                `notes` LONGTEXT NULL , 
                PRIMARY KEY (`id`)) ENGINE = InnoDB;
                
                ALTER TABLE `d3pop3_regex_masks` 
                ADD CONSTRAINT `d3pop3_regex_masks_sys_company_id_fk` FOREIGN KEY (`sys_company_id`) 
                REFERENCES `d3c_company`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
        '
        );
    }

    public function safeDown()
    {
        echo "m200521_112152_create_d3pop3_regex_masks cannot be reverted.\n";
        return false;
    }

}