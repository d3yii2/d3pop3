<?php

use yii\db\Migration;

/**
 * Class m181010_034133_add_indexes
 */
class m181010_034133_add_indexes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE `d3pop3_emails`   
                ADD INDEX (`email_id`(7));
        ');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m181010_034133_add_indexes cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181010_034133_add_indexes cannot be reverted.\n";

        return false;
    }
    */
}
