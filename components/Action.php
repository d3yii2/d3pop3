<?php

namespace d3yii2\d3pop3\components;


use d3yii2\d3pop3\models\D3pop3Actions;

class Action
{
    public static function  read(int $id)
    {
        $actions = new D3pop3Actions();
        $actions->connecting_setting_id = $id;
        $actions->type = D3pop3Actions::TYPE_READ;
        $actions->time = date('Y-m-d H:i:s');
        $actions->save();
    }

    public static function  error(int $id, string $notes)
    {
        $actions = new D3pop3Actions();
        $actions->connecting_setting_id = $id;
        $actions->type = D3pop3Actions::TYPE_ERROR;
        $actions->time = date('Y-m-d H:i:s');
        $actions->notes = $notes;
        $actions->save();
    }

    public static function  createdAccount(int $id)
    {
        $actions = new D3pop3Actions();
        $actions->connecting_setting_id = $id;
        $actions->type = D3pop3Actions::TYPE_CREATED_ACCOUNT;
        $actions->time = date('Y-m-d H:i:s');
        $actions->save();
    }

    public static function  updatedAccount(int $id)
    {
        $actions = new D3pop3Actions();
        $actions->connecting_setting_id = $id;
        $actions->type = D3pop3Actions::TYPE_UPDATED_ACCOUNT;
        $actions->time = date('Y-m-d H:i:s');
        $actions->save();
    }

}