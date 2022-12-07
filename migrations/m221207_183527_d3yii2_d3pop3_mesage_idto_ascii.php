<?php

use yii\db\Migration;

class m221207_183527_d3yii2_d3pop3_mesage_idto_ascii  extends Migration {

    public function safeUp() { 
        $this->execute('
            ALTER TABLE `d3pop3_emails`
              CHANGE `email_id` `email_id` VARCHAR (1000) CHARSET ASCII DEFAULT \'\' NOT NULL COMMENT \'Email Id\';
            
                    
        ');
    }

    public function safeDown() {
        echo "m221207_183527_d3yii2_d3pop3_mesage_idto_ascii cannot be reverted.\n";
        return false;
    }
}
