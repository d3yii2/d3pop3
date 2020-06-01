<?php

declare(strict_types=1);

namespace d3yii2\d3pop3\controllers;

use d3system\commands\D3CommandController;
use Yii;

class PostProcessingController extends D3CommandController
{
    /**
     * @throws \yii\db\Exception
     */
    public function actionIndex(): void
    {
        foreach (Yii::$app->params['postProcessComponents'] as $component) {
            $getComponent = new $component($this->getConnection());
            $getComponent->run();
        }
    }
}
