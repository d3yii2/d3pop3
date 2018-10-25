<?php

use yii\db\Migration;

/**
 * Class m180912_083622_emails_add_EmailContainerId
 */
class m180912_083622_emails_add_EmailContainerId extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE `d3pop3_emails`   
              ADD COLUMN `email_container_id` INT UNSIGNED NULL  COMMENT \'Email container id\' AFTER `email_container_class`;
        ');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180912_083622_emails_add_EmailContainerId cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180912_083622_emails_add_EmailContainerId cannot be reverted.\n";

        return false;
    }
    */
}
