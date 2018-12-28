<?php

use yii\db\Migration;

/**
 * Class m181228_055916_connecting_settings_alter
 */
class m181228_055916_connecting_settings_alter extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE `d3pop3_connecting_settings`   
              ADD COLUMN `deleted` TINYINT UNSIGNED DEFAULT 0  NOT NULL  COMMENT \'Deleted\' AFTER `notes`;
        
        ');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m181228_055916_connecting_settings_alter cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181228_055916_connecting_settings_alter cannot be reverted.\n";

        return false;
    }
    */
}
