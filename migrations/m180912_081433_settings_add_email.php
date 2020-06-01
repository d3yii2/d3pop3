<?php

use yii\db\Migration;

/**
 * Class m180912_081433_settings_add_email
 */
class m180912_081433_settings_add_email extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE `d3pop3_connecting_settings`   
              ADD COLUMN `email` VARCHAR(255) CHARSET ASCII NULL  COMMENT \'Email\' AFTER `type`;
        ');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180912_081433_settings_add_email cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180912_081433_settings_add_email cannot be reverted.\n";

        return false;
    }
    */
}
