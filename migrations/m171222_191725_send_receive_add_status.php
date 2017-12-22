<?php
namespace d3yii2\d3pop3\migrations;
use yii\db\Migration;

class m171222_191725_send_receive_add_status extends Migration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE `d3pop3_send_receiv`   
                ADD COLUMN `status` ENUM(\'New\',\'Read\',\'Deleted\') DEFAULT \'New\'   NOT NULL  COMMENT \'Status\' AFTER `setting_id`;
        ');
        //$this->execute('');

    }

    public function safeDown()
    {
        echo "m171222_191725_send_receive_add_status cannot be reverted.\n";

        return false;
    }

}
