<?php


namespace d3yii2\d3pop3\widgets;

use eaBlankonThema\widget\ThExternalLink;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Class D3Pop3MailModels
 * @package d3yii2\d3pop3\widgets
 * @deprecated use \yii2d3\d3emails\widgets\D3Pop3MailModels
 */
class D3Pop3MailModels extends Widget
{
    public $model;
    public $title;
    public $titleDescription;
    public $titleHtmlOptions = [];
    public $collapsed = false;
    public $tableOptions = [
        'class' => 'table table-striped table-success table-bordered'
    ];
    private $attachedModels = [];

    public function init()
    {
        parent::init();
        foreach(\Yii::$app->getModule('d3emails')->attachedModels as $am){
            $this->attachedModels[$am['model']] = $am;
        }

    }

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
               em.id,
               em.model_name,
               em.status  
            FROM
              `d3pop3_email_models` em 
            WHERE em.email_id = :emailId 
         ';

         $params = [
             ':emailId' => $this->model->id
         ];
        $connection = \Yii::$app->getDb();
        $command = $connection->createCommand($sql, $params);

        $html = '
        <thead>
            <tr>
                <th>'.\Yii::t('d3pop3', 'Model').'</th>
                <th>'.\Yii::t('d3pop3', 'Status').'</th>
            </tr>     
        </thead>
        <tbody>
        ';
        foreach($command->queryAll() as $row){

            if(isset($this->attachedModels[$row['model_name']])){
                $am = $this->attachedModels[$row['model_name']];
                $html .= '
                    <tr>
                        <td>'. ThExternalLink::widget([
                            'text' => \Yii::t($am['labelTranslationCategory'], $am['label']),
                            'url' => $this->createUrl($am['urlTemplate'],$row['id'])
                    ]).'</td>
                        <td>'.$row['status'].'</td>
                    </tr>';
                continue;
            }
            $html .= '
            <tr>
                <td>'.$row['model_name'].'</td>
                <td>'.$row['status'].'</td>
            </tr>';
        }

        return $html . '</tbody>';
    }

    private function createUrl($urlTemplate, $id){
        if(!\is_array($urlTemplate)){
            return str_replace('{id}',$id,$urlTemplate);
        }
        foreach($urlTemplate as $k => $v){
            $urlTemplate[$k] = $this->createUrl($v,$id);
        }
        return $urlTemplate;
    }
}