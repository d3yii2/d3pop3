<?php

use yii\db\Migration;

/**
* Class m190315_113645_alter_d3pop3_send_receiv_add_draft*/
class m190315_113645_alter_d3pop3_send_receiv_add_draft extends Migration
{
    /**
    * {@inheritdoc}
    */
    public function safeUp()
    {
        $this->execute("
          ALTER TABLE `d3pop3_send_receiv` CHANGE `status` `status` ENUM('New','Read','Deleted','Draft') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'New' COMMENT 'Status'"
        );
    }

    public function safeDown()
    {
        echo "m190315_113645_alter_d3pop3_send_receiv_add_draft cannot be reverted.\n";
        return false;
    }

}