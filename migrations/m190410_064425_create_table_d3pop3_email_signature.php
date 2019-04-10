<?php

use yii\db\Migration;

/**
* Class m190410_064425_create_table_d3pop3_email_signature*/
class m190410_064425_create_table_d3pop3_email_signature extends Migration
{
    /**
    * {@inheritdoc}
    */
    public function safeUp()
    {
        $this->execute("
            create table if not exists d3pop3_email_signature
            (
                connection_setting_id smallint(5) unsigned not null
                    primary key,
                signature text null,
                constraint d3pop3_email_signature_d3pop3_connecting_settings_id_fk
                    foreign key (connection_setting_id) references d3pop3_connecting_settings (id)
                        on delete cascade
            )
        ");
    }

    public function safeDown()
    {
        echo "m190410_064425_create_table_d3pop3_email_signature cannot be reverted.\n";
        return false;
    }

}