<?php

use yii\db\Migration;

/**
 * Class m180920_105346_actions_create
 */
class m180920_105346_actions_create extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE `d3pop3_actions` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `connecting_setting_id` smallint(5) unsigned NOT NULL COMMENT \'Connecting\',
              `time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT \'Time\',
              `type` enum(\'Created account\',\'Updated account\',\'Read\',\'Error\') NOT NULL COMMENT \'Type\',
              `notes` text DEFAULT NULL COMMENT \'Notes\',
              PRIMARY KEY (`id`),
              KEY `connecting_setting_id` (`connecting_setting_id`),
              CONSTRAINT `d3pop3_actions_ibfk_1` FOREIGN KEY (`connecting_setting_id`) REFERENCES `d3pop3_connecting_settings` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        
        ');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180920_105346_actions_create cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180920_105346_actions_create cannot be reverted.\n";

        return false;
    }
    */
}
