<?php

use yii\db\Migration;

/**
 * Class m181007_044809_d3pop3_emails_add_from_user_id
 */
class m181007_044809_d3pop3_emails_add_from_user_id extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE `d3pop3_emails`   
              ADD COLUMN `from_user_id` INT NULL  COMMENT \'From User\' AFTER `from_name`;
        ');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m181007_044809_d3pop3_emails_add_from_user_id cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181007_044809_d3pop3_emails_add_from_user_id cannot be reverted.\n";

        return false;
    }
    */
}
