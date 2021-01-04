<?php

use yii\db\Migration;

class m210104_140707_action_alter  extends Migration {

    public function safeUp() { 
        $this->execute('
             ALTER TABLE `d3pop3_actions`  
                DROP FOREIGN KEY `d3pop3_actions_ibfk_1`;
        ');
        $this->execute('
            ALTER TABLE `d3pop3_actions`  
              ENGINE=MYISAM;
                    
        ');
    }

    public function safeDown() {
        echo "m210104_140707_action_alter cannot be reverted.\n";
        return false;
    }
}
