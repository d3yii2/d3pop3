<?php


namespace d3yii2\d3pop3\widgets;


use d3yii2\d3pop3\models\D3pop3EmailAddress;
use yii\base\Widget;
use yii\helpers\Html;

class D3Pop3ModelMails extends Widget
{
    public $model;
    public $title;
    public $titleDescription;
    public $titleHtmlOptions = [];
    public $collapsed = false;
    public $tableOptions = [];

    public function run(): string
    {
        $collapsedHtml = '';
        if($this->collapsed) {
            $collapsedHtml = ' style="display: none;"';
        }

        return
            '<div class="panel">
                '.$this->createTitle().'
                <div class="panel-body"'.$collapsedHtml.'>
                    <div class="table-responsive">' .
            Html::tag('table',$this->createTable(),$this->tableOptions) .
            '</div>
                </div>
             </div>
        ';
    }

    public function createTitle()
    {
        if (!$this->title) {
            return '';
        }

        $description = '';
        if($this->titleDescription) {
            $description = '<p>' . $this->titleDescription . '</p>';
        }
        $titleHtmlOptions = $this->titleHtmlOptions;
        Html::addCssClass($titleHtmlOptions,'panel-title');

        $collapseIcon = 'fa-angle-up';
        if($this->collapsed) {
            $collapseIcon = 'fa-angle-down';
        }
        return '<div class="panel-heading panel-heading-table-simple">
                    <div class="pull-left">
                        '.Html::tag('h3',$this->title,$titleHtmlOptions).'
                        '.$description.'    
                    </div>
                    <div class="pull-right">
                        <button class="btn btn-sm" data-action="collapse" data-toggle="tooltip" data-placement="top" data-title="Collapse" data-original-title="" title="">
                            <i class="fa '.$collapseIcon.'"></i>
                        </button>
                    </div>                    
                    <div class="clearfix"></div>
                </div>';

    }

    public function createTable()
    {
         $sql = '
            SELECT 
              e.`email_datetime`,
              e.`subject`,
              GROUP_CONCAT(
                CONCAT(
                  aTo.`name`,
                  \' \',
                  aTo.`email_address`
                )
              ) `to` ,
              GROUP_CONCAT(companyTo.`name` SEPARATOR \'; \') `directToCompany`
            FROM
              `d3pop3_email_models` em 
              INNER JOIN `d3pop3_emails` e 
                ON em.`email_id` = e.id
              LEFT OUTER JOIN `d3pop3_send_receiv` srTo
                ON e.id = srTo.`email_id` 
                AND srTo.`direction` = \'in\'
              LEFT OUTER JOIN `d3c_company` companyTo
                ON companyTo.id = srTo.`company_id`                  
              LEFT OUTER JOIN `d3pop3_email_address` aTo 
                ON e.id = aTo.`email_id` 
                AND aTo.`address_type` = :to 
            WHERE em.model_name = :modelName 
              AND em.model_id = :id 
            GROUP BY e.id          
            ORDER BY e.`email_datetime`
         ';

         $params = [
             ':to' => D3pop3EmailAddress::ADDRESS_TYPE_TO,
             ':modelName' => get_class($this->model),
             ':id' => $this->model->id
         ];
        $connection = \Yii::$app->getDb();
        $command = $connection->createCommand($sql, $params);

        $html = '
        <thead>
            <tr>
                <th>'.\Yii::t('d3pop3', 'Time').'</th>
                <th>'.\Yii::t('d3pop3', 'Subject').'</th>
                <th>'.\Yii::t('d3pop3', 'To').'</th>
            </tr>     
        </thead>
        <tbody>
        ';
        foreach($command->queryAll() as $row){
            $html .= '
            <tr>
                <td>'.$row['email_datetime'].'</td>
                <td>'.$row['subject'].'</td>
                <td>'.$row['to'].$row['directToCompany'].'</td>
            </tr>';
        }

        return $html . '</tbody>';


    }
}