<?php

use yii\db\Migration;

/**
 * Class m190118_093958_actions_add_indx
 */
class m190118_093958_actions_add_indx extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('
                ALTER TABLE `d3pop3_actions`   
          ADD  INDEX `timeIdx` (`time`);
        ');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190118_093958_actions_add_indx cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190118_093958_actions_add_indx cannot be reverted.\n";

        return false;
    }
    */
}
