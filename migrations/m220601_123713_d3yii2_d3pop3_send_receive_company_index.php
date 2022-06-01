<?php

use yii\db\Migration;

class m220601_123713_d3yii2_d3pop3_send_receive_company_index  extends Migration {

    public function safeUp() { 
        $this->execute('
            ALTER TABLE `d3pop3_send_receiv`
              DROP INDEX `company_id`,
              ADD KEY `company_id` (
                `company_id`,
                `direction`,
                `status`,
                `email_id`
              );
            
                    
        ');
    }

    public function safeDown() {
        echo "m220601_123713_d3yii2_d3pop3_send_receive_company_index cannot be reverted.\n";
        return false;
    }
}
