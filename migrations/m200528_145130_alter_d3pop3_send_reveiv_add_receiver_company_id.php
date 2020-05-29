<?php

use yii\db\Migration;

/**
* Class m200528_145130_alter_d3pop3_send_reveiv_add_receiver_company_id*/
class m200528_145130_alter_d3pop3_send_reveiv_add_receiver_company_id extends Migration
{
    /**
    * {@inheritdoc}
    */
    public function safeUp()
    {

        $this->execute("
            alter table d3pop3_send_receiv
            add to_company_id smallint unsigned null
            comment '/*Receiver Company*/'
        ");

        $this->execute("
            alter table d3pop3_send_receiv
            add constraint d3pop3_send_receiv_ibfk_5
            foreign key (to_company_id) references d3c_company (id);
        ");
    }

    public function safeDown()
    {
        return true;
    }

}