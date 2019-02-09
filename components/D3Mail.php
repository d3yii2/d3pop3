<?php

namespace d3yii2\d3pop3\components;

use d3yii2\d3pop3\models\D3pop3ConnectingSettings;
use d3yii2\d3pop3\models\D3pop3ConnectingSmtpSettings;
use d3yii2\d3pop3\models\D3pop3Email;
use d3yii2\d3pop3\models\D3pop3EmailAddress;
use d3yii2\d3files\models\D3files;
use d3yii2\d3pop3\models\D3pop3EmailModel;
use d3yii2\d3pop3\models\D3pop3SendReceiv;
use d3yii2\d3pop3\models\D3pPerson;
use Html2Text\Html2Text;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii2d3\d3emails\models\forms\MailForm;
use yii2d3\d3persons\models\User;
use d3yii2\d3pop3\dictionaries\ConnectingSettingsDict;

class D3Mail
{
    /** @var D3pop3Email */
    private $email;

    /** @var string */
    private $emailId;

    /** @var string */
    private $subject;

    /** @var string */
    private $bodyPlain;

    /** @var string */
    private $bodyHtml;

    private $from_name;

    private $from_email;

    /** @var int */
    private $from_user_id;

    /** @var D3pop3EmailAddress[] */
    private $addressList = [];

    /** @var array D3pop3SendReceiv[] */
    private $sendReceiveList = [];

    /** @var array  D3pop3EmailModel */
    private $emailModelList = [];

    /** @var array */
    private $attachmentList = [];

    /**
     * @return D3pop3Email
     */
    public function getEmail(): D3pop3Email
    {
        return $this->email;
    }

    /**
     * @param D3pop3Email $email
     */
    public function setEmail(D3pop3Email $email)
    {
        $this->email = $email;
    }

    /**
     * @param array $emailIdList
     * @return $this
     */
    public function setEmailId(array $emailIdList)
    {
        $this->emailId = implode('-', $emailIdList);
        return $this;
    }

    /**
     * @param string $subject
     * @return $this
     */
    public function setSubject(string $subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @param string $bodyPlain
     * @return $this
     */
    public function setBodyPlain(string $bodyPlain): self
    {
        $this->bodyPlain = $bodyPlain;
        return $this;
    }

    /**
     * @param string $bodyHtml
     * @return D3Mail
     */
    public function setBodyHtml(string  $bodyHtml): self
    {
        $this->bodyHtml = $bodyHtml;
        return $this;
    }

    /**
     * @param mixed $from_name
     * @return $this
     */
    public function setFromName($from_name)
    {
        $this->from_name = $from_name;
        return $this;
    }

    /**
     * @param mixed $from_email
     * @return $this
     */
    public function setFromEmail($from_email)
    {
        $this->from_email = $from_email;
        return $this;
    }

    /**
     * @param int $userId
     * @return D3Mail
     * @throws Exception
     */
    public function setFromUserId(int $userId): self
    {
        $this->from_user_id = $userId;
        if(!$user = User::findOne($userId)){
            throw new Exception('No found user. user_id=' . $userId);
        }
        $this->from_email = $user->email;

        /** @var D3pPerson $person */
        if($person = $user->getD3pPeople()->one()){
            $this->from_name = $person->getFullName();
        }
        return $this;
    }

    /**
     * @param string $email
     * @param string|null $name
     * @return $this
     */
    public function addAddressTo(string $email, $name = null): self
    {
        $address = new D3pop3EmailAddress();
        $address->address_type = D3pop3EmailAddress::ADDRESS_TYPE_TO;
        $address->email_address = $email;
        $address->name = $name;
        $this->addressList[] = $address;
        return $this;
    }

    private function clearAddressTo()
    {
        $this->addressList = [];
        return $this;
    }

    public function addSendReceiveOutFromCompany(int $companyId = 0)
    {
        if(!$companyId){
            $companyId = \Yii::$app->SysCmp->getActiveCompanyId();
        }
        $sendReceiv = new D3pop3SendReceiv();
        $sendReceiv->direction = D3pop3SendReceiv::DIRECTION_OUT;
        $sendReceiv->company_id = $companyId;
        $sendReceiv->status = D3pop3SendReceiv::STATUS_NEW;
        $this->sendReceiveList[] = $sendReceiv;
        return $this;
    }

    public function addSendReceiveToInCompany(int $companyId)
    {
        $sendReceiv = new D3pop3SendReceiv();
        $sendReceiv->direction = D3pop3SendReceiv::DIRECTION_IN;
        $sendReceiv->company_id = $companyId;
        $sendReceiv->status = D3pop3SendReceiv::STATUS_NEW;
        $this->sendReceiveList[] = $sendReceiv;
        return $this;
    }

    /**
     * @param ActiveRecord $model
     * @return $this
     */
    public function setEmailModel($model)
    {
        $emailModel = new D3pop3EmailModel();
        $emailModel->model_name = get_class($model);
        $emailModel->model_id = $model->id;
        $emailModel->status = D3pop3EmailModel::STATUS_NEW;
        $this->emailModelList[] = $emailModel;
        return $this;
    }

    public function addAttachment($fileName, $filePath, $fileTypes = '/(gif|pdf|dat|jpe?g|png|doc|docx|xls|xlsx|htm|txt|log|mxl|xml|zip)$/i')
    {
        $this->attachmentList[] = [
            'fileName' => $fileName,
            'filePath' => $filePath,
            'fileTypes' => $fileTypes,
        ];

        return $this;
    }

    public function save()
    {
        if(!$this->email) {
            $this->email = new D3pop3Email();
        }
        $this->email->email_datetime = date('Y-m-d H:i:s');
        $this->email->receive_datetime = date('Y-m-d H:i:s');
        $this->email->subject = $this->subject;
        $this->email->body = $this->bodyHtml;
        $this->email->body_plain = $this->bodyPlain;
        $this->email->from_name = $this->from_name;
        $this->email->from = $this->from_email;
        $this->email->from_user_id = $this->from_user_id;
        if (!$this->email->save()) {
            throw new \Exception('D3pop3Email save error: ' . json_encode($this->email->getErrors()));
        }

        foreach ($this->addressList as $address) {
            $address->email_id = $this->email->id;
            if (!$address->save()) {
                throw new \Exception('D3pop3EmailAddress save error: ' . json_encode($address->getErrors()));
            }
        }

        foreach ($this->sendReceiveList as $sendReceive) {
            $sendReceive->email_id = $this->email->id;
            if (!$sendReceive->save()) {
                throw new \Exception('D3pop3SendReceiv save error: ' . json_encode($sendReceive->getErrors()));
            }
        }

        foreach ($this->emailModelList as $emailModel) {
            $emailModel->email_id = $this->email->id;
            if (!$emailModel->save()) {
                throw new \Exception('D3pop3EmailModel save error: ' . json_encode($emailModel->getErrors()));
            }
        }

        foreach ($this->attachmentList as $attachment) {
            D3files::saveFile($attachment['fileName'], D3pop3Email::className(), $this->email->id, $attachment['filePath'], $attachment['fileTypes']);
        }


    }

    /**
     * @return int
     */
    public function getEmailId(): int
    {
        return $this->email->id;
    }

    public function getAttachments()
    {
        return D3files::getRecordFilesList(D3pop3Email::className(),$this->email->id);
    }

    public function send(): bool
    {
        /**
         * Set the custom SMTP connection if exists for this mailbox
         */
        $settingEmailContainer = new SettingEmailContainer();
        if($settingEmailContainer->fetchEmailSmtpData($this->from_email)) {
            $smtpConfig = $settingEmailContainer->getEmailSmtpConnectionDetails();

            if ($smtpConfig) {

                $tranportConfig = [
                    'class' => 'Swift_SmtpTransport',
                    'host' => $smtpConfig['host'],
                    'port' => $smtpConfig['port'],
                ];

                if (!empty($smtpConfig['user'])) {
                    $tranportConfig['username'] = $smtpConfig['user'];
                }

                if (!empty($smtpConfig['password'])) {
                    $tranportConfig['password'] = $smtpConfig['password'];
                }

                if (!empty($smtpConfig['ssl']) && \d3yii2\d3pop3\models\TypeSmtpForm::SSL_ENCRYPTION_NONE !== $smtpConfig['ssl']) {
                    $tranportConfig['encryption'] = $smtpConfig['ssl'];

                    //@FIXME - should be self signed certificates supported?
                    //\Yii::$app->mailer->setStreamOptions(['ssl' => ['allow_self_signed' => true, 'verify_peer' => false]]);
                }

                \Yii::$app->mailer->setTransport($tranportConfig);
            }
        }
        $message = \Yii::$app->mailer->compose()
            ->setFrom($this->email->from)
            ->setSubject($this->email->subject);
        if($this->email->body_plain) {
            $message->setTextBody($this->email->body_plain);
        }
        if($this->email->body) {
            $message->setHtmlBody($this->email->body);
        }
        /** @var D3pop3EmailAddress $address */
        foreach ($this->email->getD3pop3EmailAddresses()->all() as $address) {
            if ($address->address_type === D3pop3EmailAddress::ADDRESS_TYPE_TO) {
                $message->setTo($address->fullAddress());
            } elseif ($address->address_type === D3pop3EmailAddress::ADDRESS_TYPE_REPLAY) {
                $message->setReplyTo($address->fullAddress());
            } elseif ($address->address_type === D3pop3EmailAddress::ADDRESS_TYPE_CC) {
                $message->setCc($address->fullAddress());
            }
        }

        foreach (D3files::getRecordFilesList(D3pop3Email::class, $this->email->id) as $file) {
            $message->attach($file['file_path'], ['fileName' => $file['file_name']]);
        }

        return $message->send();
    }

    /**
     * @return array|D3pop3EmailAddress[]
     */
    private function getEmailAddress()
    {
        if(!$this->addressList){
            $this->addressList = $this->email->getD3pop3EmailAddresses()->all();
        }

        return $this->addressList;
    }

    /**
     * @return array|D3pop3EmailAddress[]
     */
    public function getToAdreses(): array
    {
        /** @var D3pop3EmailAddress[] $list */
        $list = [];
        /** @var D3pop3EmailAddress $address */
        foreach($this->getEmailAddress() as $address){
            if($address->address_type === D3pop3EmailAddress::ADDRESS_TYPE_TO){
                $list[] = $address;
            }
        }

        return $list;
    }

    /**
     * @return D3pop3EmailAddress[]
     */
    public function getReplyAddreses()
    {

        /** @var D3pop3EmailAddress[] $list */
        $list = [];
        /** @var D3pop3EmailAddress $address */
        foreach($this->getEmailAddress() as $address){
            if($address->address_type === D3pop3EmailAddress::ADDRESS_TYPE_REPLAY){
                $list[] = $address;
            }
        }

        return $list;
    }


    /**
     * ja nav plain body, konvertee HTML body
     * @return string
     * @throws \Html2Text\Html2TextException
     */
    private function getPlainBody(): string
    {
        if(!$this->email->body){
            return $this->email->body_plain;
        }

        $convertedBody = Html2Text::convert($this->email->body, true);
        if(!$this->email->body_plain){
            return $convertedBody;
        }

        /**
         * apstraadaa gafdiijumu, ja plain body ir piemeeram "An HTML viewer is required to see this message"
         */
        if(\strlen($convertedBody) > 3*\strlen($this->email->body_plain)){
            return $convertedBody;
        }
        return $this->email->body_plain;
    }

    public function init(string $createdBy)
    {
        $fromList = ConnectingSettingsDict::getFromList();
        reset($fromList);
        $email = key($fromList);
        $this
            ->setEmailId([
                $createdBy,
                \Yii::$app->SysCmp->getActiveCompanyId(),
                \Yii::$app->user->getId(),
                date('YmdHis')
            ])
            ->setFromEmail($email)
            ->setFromName($fromList[$email])
            ->addSendReceiveOutFromCompany();
    }


    /**
     * @return D3Mail
     * @throws \Exception
     */
    public function createReply(): self
    {

        /** @var D3pop3ConnectingSettings $settings */
        $settings = D3pop3ConnectingSettings::findOne($this->email->email_container_id);

        if(!$settings->email){
            throw new \Exception(\Yii::t('d3pop3','Please set email in My Company Email Settings'));
        }


        $replyD3Mail = new self();

        $replyD3Mail->setEmailId(['REPLY',\Yii::$app->SysCmp->getActiveCompanyId(), 'MAIL', $this->email->id, date('YmdHis')])
            ->setSubject('RE: ' . $this->email->subject)
            ->setBodyPlain('> ' . str_replace("\n","\n> ",$this->getPlainBody()))
            ->setFromEmail($settings->email)
            ->setFromName(\Yii::$app->person->firstName . ' ' .  \Yii::$app->person->lastName)
            ->addSendReceiveOutFromCompany()
            //->addSendReceiveToInCompany($model->partner_id)
            //->setEmailModel($model)
            //->addAttachment($fileName,$filePath)
            ;

        if($replyAddreses = $this->getReplyAddreses()) {
            $replyD3Mail->addAddressTo($replyAddreses[0]->email_address, $replyAddreses[0]->name);

        }else{
            $replyD3Mail->addAddressTo($this->email->from, $this->email->from_name);
        }
        $replyD3Mail->save();

        return $replyD3Mail;

    }

    /**
     * @param MailForm $form
     * @return MailForm
     */
    public function loadToForm(MailForm $form): MailForm
    {
        $form->from = $this->email->from;
        $form->from_name = $this->email->from_name;

        $toAdreses = $this->getToAdreses();
        $form->to = $toAdreses[0]->email_address;
        $form->to_name = $toAdreses[0]->name;

        $form->subject = $this->email->subject;
        $form->body = $this->email->body_plain;

        return $form;

    }

    /**
     * @param MailForm $form
     * @return bool
     */
    public function loadFromForm(MailForm $form): bool
    {
        $this->setFromEmail($form->from)
        ->setFromName($form->from_name)
        ->clearAddressTo()
        ->addAddressTo($form->to,$form->to_name)
        ->setSubject($form->subject)
        ->setBodyPlain($form->body);
        return true;

    }
}