<?php

use yii\db\Migration;

class m220426_211933_d3yii2_d3pop3_emails_encoding_utf8mb4  extends Migration {

    public function safeUp() { 
        $this->execute('
            ALTER TABLE `d3pop3_emails`
              CHANGE `subject` `subject` TEXT CHARSET utf8mb4 NULL COMMENT \'Subject\',
              CHANGE `body` `body` LONGTEXT CHARSET utf8mb4 NULL COMMENT \'Body\',
              CHANGE `body_plain` `body_plain` LONGTEXT CHARSET utf8mb4 NULL COMMENT \'Body Plain\';
            
                    
        ');
    }

    public function safeDown() {
        echo "m220426_211933_d3yii2_d3pop3_emails_encoding_utf8mb4 cannot be reverted.\n";
        return false;
    }
}
