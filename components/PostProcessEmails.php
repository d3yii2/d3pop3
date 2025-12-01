<?php

namespace d3yii2\d3pop3\components;

use d3yii2\d3pop3\models\D3pop3Email;
use Yii;
use yii2d3\d3persons\models\D3cCompany;

class PostProcessEmails implements PostProcessingInterface
{

    /**
     * @var string[]
     */
    private array $messages = [];

    /**
     * @var bool
     */
    private bool $debug = false;

    public function setDebugOn(): void
    {
        $this->debug = true;
    }

    public function getName(): string
    {
        return 'logs d3pop read emails into d3activity';
    }


    /**
     * @param D3pop3Email $getD3pop3Email
     * @param  object|D3cCompany $company
     */
    public function run($getD3pop3Email, object $company): void
    {

        $activityRegistar = Yii::$app->activityRegistar;
        $activityRegistar->sysCompanyId = $company->id;
        $activityRegistar->registerModel(
            $getD3pop3Email,
            'd3yii2/d3pop3/D3PoP3/read', // ??
            [
                'subject' => $getD3pop3Email->subject,
                'from' => $getD3pop3Email->from
            ]
        );
    }

    public function getLogMessages(): array
    {
        return $this->messages;
    }
}
