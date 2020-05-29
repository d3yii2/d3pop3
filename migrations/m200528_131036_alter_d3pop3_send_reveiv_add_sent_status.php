<?php

use yii\db\Migration;

/**
* Class m200528_131036_alter_d3pop3_send_reveiv_add_sent_status*/
class m200528_131036_alter_d3pop3_send_reveiv_add_sent_status extends Migration
{
    /**
    * {@inheritdoc}
    */
    public function safeUp()
    {
        $this->execute(
            "alter table d3pop3_send_receiv
                modify status enum('New', 'Read', 'Deleted', 'Draft', 'Sent') default 'New' not null
                comment 'Status'
        ");
    }

    public function safeDown()
    {
        echo "m200528_131036_alter_d3pop3_send_reveiv_add_sent_status cannot be reverted.\n";
        return false;
    }

}