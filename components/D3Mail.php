<?php

namespace d3yii2\d3pop3\components;

use d3system\exceptions\D3ActiveRecordException;
use d3yii2\d3files\models\D3files;
use d3yii2\d3pop3\dictionaries\ConnectingSettingsDict;
use d3yii2\d3pop3\models\D3pop3ConnectingSettings;
use d3yii2\d3pop3\models\D3pop3Email;
use d3yii2\d3pop3\models\D3pop3EmailAddress;
use d3yii2\d3pop3\models\D3pop3EmailModel;
use d3yii2\d3pop3\models\D3pop3SendReceiv;
use d3yii2\d3pop3\models\D3pPerson;
use d3yii2\d3pop3\models\D3pop3EmailError;
use d3yii2\d3pop3\models\TypeSmtpForm;
use eaBlankonThema\components\FlashHelper;
use Html2Text\Html2Text;
use Html2Text\Html2TextException;
use ReflectionException;
use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\db\Exception as DbException;
use yii\helpers\VarDumper;
use yii\swiftmailer\Message;
use yii\web\ForbiddenHttpException;
use yii2d3\d3persons\models\User;
use d3yii2\d3files\components\D3Files as D3FilesComponent;

use function get_class;
use function in_array;
use function is_array;
use function strlen;

/**
 * Class D3Mail
 * @package d3yii2\d3pop3\components
 */
class D3Mail
{
    public const EMAIL_MODEL_CLASS = D3pop3Email::class;

    /** @var D3pop3Email */
    private $email;
    /** @var string */
    private $emailId = '';
    /** @var string */
    private $subject;
    /** @var string */
    private $bodyPlain;
    /** @var string */
    private $bodyHtml;
    /** @var string */
    private $emailDatetime;
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
    /** @var array */
    private $attachmentContentList = [];
    /**
     * @var string
     */
    private $email_container_class;
    /**
     * @var int
     */
    private $email_container_id;

    /**
     * @param string $emailDatetime
     * @return D3Mail
     */
    public function setEmailDatetime(string $emailDatetime): self
    {
        $this->emailDatetime = $emailDatetime;
        return $this;
    }


    /**
     * @param string $email_container_class
     * @return D3Mail
     */
    public function setEmailContainerClass(string $email_container_class): self
    {
        $this->email_container_class = $email_container_class;
        return $this;
    }

    /**
     * @param int $email_container_id
     * @return D3Mail
     */
    public function setEmailContainerId(int $email_container_id): self
    {
        $this->email_container_id = $email_container_id;
        return $this;
    }

    /**
     * @return D3pop3EmailAddress[]
     */
    public function getAddressList(): array
    {
        return $this->addressList;
    }

    /**
     * @return D3pop3Email
     */
    public function getEmail(): ?D3pop3Email
    {
        return $this->email;
    }

    /**
     * @param D3pop3Email $email
     */
    public function setEmail(D3pop3Email $email): void
    {
        $this->email = $email;
    }

    /**
     * @param string|null $bodyHtml
     * @return D3Mail
     */
    public function setBodyHtml(?string $bodyHtml): self
    {
        $this->bodyHtml = $bodyHtml;
        return $this;
    }

    /**
     * @param int $userId
     * @return D3Mail
     * @throws D3ActiveRecordException
     */
    public function setFromUserId(int $userId): self
    {
        $this->from_user_id = $userId;
        if (!$user = User::findOne($userId)) {
            throw new D3ActiveRecordException($user, null, 'User not found by ID: ' . $userId);
        }
        $this->from_email = $user->email;

        /** @var D3pPerson $person */
        if ($person = $user->getD3pPeople()->one()) {
            $this->from_name = $person->getFullName();
        }
        return $this;
    }

    /**
     * @param int $companyId
     * @return $this
     */
    public function addSendReceiveToInCompany(int $companyId): self
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
    public function setEmailModel($model): self
    {
        $emailModel = new D3pop3EmailModel();
        $emailModel->model_name = get_class($model);
        $emailModel->model_id = $model->id;
        $emailModel->status = D3pop3EmailModel::STATUS_NEW;
        $this->emailModelList[] = $emailModel;
        return $this;
    }

    /**
     * @param $fileName
     * @param $filePath
     * @param null $model
     * @return $this
     */
    public function addAttachment(
        $fileName,
        $filePath,
        $model = null
    ): self {
        $this->attachmentList[] = compact('fileName', 'filePath', 'model');

        return $this;
    }

    /**
     * @param $fileName
     * @param $content
     * @param null $model
     * @return $this
     */
    public function addAttachmentContent(
        $fileName,
        $content,
        $model = null
    ): self {
        $this->attachmentContentList[] = compact('fileName', 'content', 'model');

        return $this;
    }

    public function getEmailId()
    {
        return $this->email->id ?? '';
    }

    /**
     * @param array|string $emailId
     * @return $this
     */
    public function setEmailId($emailId): self
    {
        if (is_array($emailId)) {
            $this->emailId = implode('-', $emailId);
        } else {
            $this->emailId = $emailId;
        }
        return $this;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function getAttachments(): ?array
    {
        try {
            return D3files::getRecordFilesList(self::EMAIL_MODEL_CLASS, $this->email->id);
        } catch (ForbiddenHttpException $e) {
            FlashHelper::addDanger(Yii::t('d3system', 'Unexpected Server Error'));
        }
        return [];
    }

    /**
     * @return bool
     */
    public function send(): bool
    {
        $prevMailerConfig = Yii::$app->mailer->getTransport();
        /** @var \d3yii2\d3pop3\d3pop3 $module */
        $module = Yii::$app->getModule('D3Pop3');

        try {
            if($module->forceUseFileTransport){
                Yii::$app->mailer->setTransport(['useFileTransport' => true]);
            }else {
                /**
                 * Set the custom SMTP connection if exists for this mailbox
                 */
                $settingEmailContainer = new SettingEmailContainer();
                if ($settingEmailContainer->fetchEmailSmtpData($this->from_email)) {
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

                        if (!empty($smtpConfig['ssl']) && TypeSmtpForm::SSL_ENCRYPTION_NONE !== $smtpConfig['ssl']) {
                            $tranportConfig['encryption'] = $smtpConfig['ssl'];

                            //@FIXME - should be self signed certificates supported?
                            //\Yii::$app->mailer->setStreamOptions(
                            //['ssl' => ['allow_self_signed' => true, 'verify_peer' => false]]);
                        }

                        Yii::$app->mailer->setTransport($tranportConfig);
                    }
                }
            }
            try {
                /** @var Message $message */
                $message = Yii::$app->mailer->compose()
                    ->setFrom($this->email->from)
                    ->setSubject($this->email->subject);
                if ($this->email->body_plain) {
                    $message->setTextBody($this->email->body_plain);
                }
                if ($this->email->body) {
                    $message->setHtmlBody($this->email->body);
                }

                /** @var D3pop3EmailAddress $address */
                $to = [];
                $replyTo = [];
                $cc = [];
                $bcc = [];
                foreach ($this->email->getD3pop3EmailAddresses()->all() as $address) {
                    switch ($address->address_type) {
                        case D3pop3EmailAddress::ADDRESS_TYPE_REPLY:
                            $replyTo[] = $address->email_address;
                            break;
                        case D3pop3EmailAddress::ADDRESS_TYPE_CC:
                            $cc[] = $address->email_address;
                            break;
                        case D3pop3EmailAddress::ADDRESS_TYPE_BCC:
                            $bcc[] = $address->email_address;
                            break;
                        default:
                            $to[] = $address->email_address;
                    }
                }

                if (!empty($to)) {
                    $message->setTo($to);
                }
                if (!empty($replyTo)) {
                    $message->setReplyTo($replyTo);
                }
                if (!empty($cc)) {
                    $message->setCc($cc);
                }
                if (!empty($bcc)) {
                    $message->setBcc($bcc);
                }
                
                try {
                    foreach (D3files::getRecordFilesList(self::EMAIL_MODEL_CLASS, $this->email->id) as $file) {
                        $message->attach($file['file_path'], ['fileName' => $file['file_name']]);
                    }

                    $sent = $message->send();
                    
                    if ($sent) {
                        $this->setSendReceiveStatus(D3pop3SendReceiv::STATUS_SENT);
                        $this->saveSendReceive();
                    }
                    Yii::$app->mailer->setTransport($prevMailerConfig);
                    return $sent;
                } catch (\Exception $e) {
                    $err = 'Send exception message: ' . $e->getMessage() . PHP_EOL
                       . $e->getTraceAsString() . PHP_EOL
                       . (
                           isset($tranportConfig)
                            ? 'Can not send email. ' . VarDumper::dumpAsString($tranportConfig)
                            : 'Can not send by default mailer.'
                    );

                    Yii::error($err);
                }
            } catch (\Exception $e) {
                Yii::error(
                    'Cannot set the mail attributes in mailer.'.PHP_EOL
                    . ' Error: ' . $e->getMessage().PHP_EOL
                    . $e->getTraceAsString()
                );
            }
        } catch (\Exception $e) {
            Yii::error(
                'Cannot set the custom SMTP connection'.PHP_EOL
                . ' Error: ' . $e->getMessage().PHP_EOL
                . $e->getTraceAsString()
            );
        }
        Yii::$app->mailer->setTransport($prevMailerConfig);
        return false;
    }

    /**
     * @param string $createdBy
     * @throws DbException
     */
    public function init(string $createdBy): void
    {
        $fromList = ConnectingSettingsDict::getFromList();
        reset($fromList);
        $email = key($fromList);
        $this
            ->setEmailId([
                $createdBy,
                Yii::$app->SysCmp->getActiveCompanyId(),
                Yii::$app->user->getId(),
                date('YmdHis'),
            ])
            ->setFromEmail($email)
            ->setFromName($fromList[$email])
            ->addSendReceiveOutFromCompany();
    }

    /**
     * @param int $companyId
     * @param string $status
     * @return $this
     * @throws DbException
     */
    public function addSendReceiveOutFromCompany(
        int $companyId = 0,
        string $status = D3pop3SendReceiv::STATUS_NEW
    ): self {
        if (!$companyId) {
            $companyId = (int)Yii::$app->SysCmp->getActiveCompanyId();
        }
        $sendReceiv = new D3pop3SendReceiv();
        $sendReceiv->direction = D3pop3SendReceiv::DIRECTION_OUT;
        $sendReceiv->company_id = $companyId;
        $sendReceiv->status = $status;
        $this->sendReceiveList[] = $sendReceiv;
        return $this;
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setSendReceiveStatus(string $status = D3pop3SendReceiv::STATUS_DRAFT): self
    {
        $this->sendReceiveList = $this->email->d3pop3SendReceivs ?? [];
        $this->setSendReceiveAttrs(['status' => $status]);
        return $this;
    }

    /**
     * @param array $attrs
     * @return $this
     */
    public function setSendReceiveAttrs(array $attrs): self
    {
        foreach ($this->sendReceiveList as $i => $model) {
            /** @var D3pop3SendReceiv $model */
            $model->setAttributes($attrs);
            $this->sendReceiveList[$i] = $model;
        }
        return $this;
    }

    /**
     * @param mixed $from_name
     * @return $this
     */
    public function setFromName($from_name): self
    {
        $this->from_name = $from_name;
        return $this;
    }

    /**
     * @param mixed $from_email
     * @return $this
     */
    public function setFromEmail($from_email): self
    {
        $this->from_email = $from_email;
        return $this;
    }

    /**
     * Nosūta no tā paša epasta, uz kuru saņemts. Mekle pēc setting id
     *
     * @return D3Mail
     * @throws Exception
     * @throws Html2TextException
     * @throws \Exception
     */
    public function createReply(): self
    {
        /** @var D3pop3ConnectingSettings $settings */
        $settings = D3pop3ConnectingSettings::findOne($this->email->email_container_id);

        if (empty($settings->email)) {
            throw new Exception(Yii::t('d3pop3', 'Please set email in My Company Email Settings'));
        }

        $replyD3Mail = new self();

        $replyD3Mail->setEmailId([
            'REPLY',
            Yii::$app->SysCmp->getActiveCompanyId(),
            'MAIL',
            $this->email->id,
            date('YmdHis'),
        ])
            ->setSubject('RE: ' . $this->email->subject)
            ->setBodyPlain('> ' . str_replace("\n", "\n> ", $this->getPlainBody()))
            ->setFromEmail($settings->email)
            ->setFromName(Yii::$app->person->firstName . ' ' . Yii::$app->person->lastName)
            ->addSendReceiveOutFromCompany(0, D3pop3SendReceiv::STATUS_DRAFT)
            ->addAddressReply($this->email->from, $this->email->from_name);

        $replyD3Mail->save();

        return $replyD3Mail;
    }

    /**
     * @param string|null $bodyPlain
     * @return $this
     */
    public function setBodyPlain(?string $bodyPlain): self
    {
        $this->bodyPlain = $bodyPlain;
        return $this;
    }

    /**
     * @param string $subject
     * @return $this
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * ja nav plain body, konvertee HTML body
     * @return string
     * @throws Html2TextException
     */
    private function getPlainBody(): string
    {
        if (!$this->email->body) {
            return $this->email->body_plain;
        }

        $convertedBody = Html2Text::convert($this->email->body, true);
        if (!$this->email->body_plain) {
            return $convertedBody;
        }

        /**
         * apstraadaa gafdiijumu, ja plain body ir piemeeram "An HTML viewer is required to see this message"
         */
        if (strlen($convertedBody) > 3 * strlen($this->email->body_plain)) {
            return $convertedBody;
        }
        return $this->email->body_plain;
    }

    /**
     * @return D3pop3EmailAddress[]
     */
    public function getReplyAddreses(): array
    {
        /** @var D3pop3EmailAddress[] $list */
        $list = [];
        foreach ($this->getEmailAddress() as $address) {
            if ($address->address_type === D3pop3EmailAddress::ADDRESS_TYPE_REPLY) {
                $list[] = $address;
            }
        }

        return $list;
    }

    /**
     * @return string
     */
    public function getEmailStatus(): ?string
    {
        return $this->email->d3pop3SendReceivs[0]->status ?? null;
    }

    /**
     * @return array|D3pop3EmailAddress[]
     */
    private function getEmailAddress(): array
    {
        if (!$this->addressList) {
            $this->addressList = $this->email->getD3pop3EmailAddresses()->all();
        }

        return $this->addressList;
    }

    /**
     * @param string $email
     * @param string|null $name
     * @return $this
     */
    public function addAddressTo(string $email, string $name = null): self
    {
        $address = new D3pop3EmailAddress();
        $address->email_id = $this->getEmailId();
        $address->address_type = D3pop3EmailAddress::ADDRESS_TYPE_TO;
        $address->email_address = $email;
        $address->name = $name;
        $this->addressList[] = $address;
        return $this;
    }

    /**
     * @throws D3ActiveRecordException
     * @throws Exception
     * @throws ForbiddenHttpException|\ReflectionException
     */
    public function save(): void
    {
        if (!$this->email) {
            $this->email = new D3pop3Email();
        }

        $this->email->email_datetime = $this->emailDatetime ?? date('Y-m-d H:i:s');
        $this->email->receive_datetime = date('Y-m-d H:i:s');
        $this->email->email_id = $this->emailId;
        $this->email->subject = $this->subject;
        $this->email->body = $this->bodyHtml;
        $this->email->body_plain = $this->bodyPlain;
        $this->email->from_name = $this->from_name;
        $this->email->from = $this->from_email;
        $this->email->from_user_id = $this->from_user_id;
        $this->email->email_container_id = $this->email_container_id;
        $this->email->email_container_class = $this->email_container_class;

        if (!$this->email->save()) {
            throw new D3ActiveRecordException($this->email);
        }

        $this->saveRelations();
    }

    /**
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws ReflectionException
     */
    public function saveAttachmentContentList(): void
    {
        foreach ($this->attachmentContentList as $attachment) {
            $modelClass = isset($attachment['model']) ? get_class($attachment['model']) : self::EMAIL_MODEL_CLASS;
            $modelId = isset($attachment['model']) ? $attachment['model']->id : $this->email->id;
            $fileTypes = D3FilesComponent::getAllowedFileTypes($modelClass);
            
            D3files::saveContent(
                $attachment['fileName'],
                $modelClass,
                $modelId,
                $attachment['content'],
                $fileTypes
            );
        }
    }

    /**
     * @throws \Exception
     */
    public function saveAttachmentsList(): void
    {
        foreach ($this->attachmentList as $attachment) {
            $modelClass = isset($attachment['model']) ? get_class($attachment['model']) : self::EMAIL_MODEL_CLASS;
            $modelId = isset($attachment['model']) ? $attachment['model']->id : $this->email->id;
            $fileTypes = D3FilesComponent::getAllowedFileTypes($modelClass);
            
            D3files::saveFile(
                $attachment['fileName'],
                $modelClass,
                $modelId,
                $attachment['filePath'],
                $fileTypes
            );
        }
    }

    /**
     * @throws D3ActiveRecordException
     */
    public function saveEmailModelList(): void
    {
        foreach ($this->emailModelList as $emailModel) {
            $emailModel->email_id = $this->email->id;
            if (!$emailModel->save()) {
                throw new D3ActiveRecordException($emailModel);
            }
        }
    }

    /**
     * save addresses
     */
    public function saveAddressList(): void
    {
        $oldAddressList = $this->email->d3pop3EmailAddresses;

        foreach ($this->addressList as $address) {
            /**
             * existing addresses do not touch
             */
            foreach($oldAddressList as $oldAddressKey => $oldAddress){
                if($oldAddress->address_type === $address->address_type && $oldAddress->email_address === $address->email_address){
                    unset($oldAddressList[$oldAddressKey],$address);
                    break;
                }
            }
            if(!isset($address)){
                continue;
            }
            $address->email_id = $this->getEmailId();
            if (!$address->save()) {
                Yii::error(VarDumper::dumpAsString($address->getErrors())
                    . PHP_EOL . VarDumper::dumpAsString($address->attributes));

            }
        }

        foreach($oldAddressList as $oldAddress){
            $oldAddress->delete();
        }
    }

    /**
     * @throws D3ActiveRecordException
     */
    public function saveSendReceive(): void
    {
        foreach ($this->sendReceiveList as $sendReceive) {
            $sendReceive->email_id = $this->getEmailId();
            if (!$sendReceive->save()) {
                throw new D3ActiveRecordException($sendReceive);
            }
        }
    }

    /**
     * @return D3Mail
     * @throws Exception
     * @throws \Exception
     */
    public function createComposed(): self
    {
        
        $replyD3Mail = new self();

        $replyD3Mail->setEmailId([
            'Composed',
            Yii::$app->SysCmp->getActiveCompanyId(),
            'MAIL',
            $this->getEmailId(),
            date('YmdHis'),
        ])
            //->setFromEmail($settings->email)
            ->setFromName(Yii::$app->person->firstName . ' ' . Yii::$app->person->lastName)
            ->addSendReceiveOutFromCompany(0, D3pop3SendReceiv::STATUS_DRAFT);
        
        $replyD3Mail->save();

        return $replyD3Mail;
    }

    /**
     * Save the relations: D3pop3EmailAddress, D3pop3SendReceiv, D3pop3EmailModel, Attachemnt contents
     * @throws D3ActiveRecordException
     * @throws Exception|\ReflectionException
     */
    public function saveRelations(): void
    {
        $this->saveAddressList();

        $this->saveSendReceive();

        $this->saveEmailModelList();

        try {
            $this->saveAttachmentsList();
            $this->saveAttachmentContentList();
        } catch (ForbiddenHttpException $e) {
            $mailError = new D3pop3EmailError;
            $mailError->email_id = $this->emailId;
            $mailError->message = $e->getMessage();
            if (!$mailError->save()) {
                Yii::error($e->getMessage());
                Yii::error($e->getTraceAsString());
            }
        }

    }

    /**
     * @return $this
     */
    public function clearAddressTo(): self
    {
        $this->addressList = [];
        return $this;
    }

    /**
     * @param D3pop3EmailAddress $model
     */
    public function setToAddressList(D3pop3EmailAddress $model): void
    {
        $this->addressList[] = $model;
    }

    /**
     * @param string $email
     * @param string|null $name
     * @return $this
     */
    public function addAddressReply(string $email, string $name = null): self
    {
        $address = new D3pop3EmailAddress();
        $address->address_type = D3pop3EmailAddress::ADDRESS_TYPE_REPLY;
        $address->email_address = $email;
        $address->name = $name;
        $this->addressList[] = $address;
        return $this;
    }

    /**
     * @param string $email
     * @param string|null $name
     * @return $this|null
     */
    public function addAddressCc(string $email, string $name = null): ?self
    {
        if ($this->existsInAddressList($email, [D3pop3EmailAddress::ADDRESS_TYPE_TO])) {
            return null;
        }

        $address = new D3pop3EmailAddress();
        $address->address_type = D3pop3EmailAddress::ADDRESS_TYPE_CC;
        $address->email_address = $email;
        $address->name = $name;
        $this->addressList[] = $address;
        return $this;
    }

    /**
     * @param string $email
     * @param array $types
     * @return bool
     */
    private function existsInAddressList(string $email, array $types): bool
    {
        foreach ($this->addressList as $item) {
            if ($item->email_address === $email && in_array($item->address_type, $types, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $email
     * @param string|null $name
     * @return $this|null
     */
    public function addAddressBcc(string $email, string $name = null): ?self
    {
        if ($this->existsInAddressList(
            $email,
            [D3pop3EmailAddress::ADDRESS_TYPE_TO, D3pop3EmailAddress::ADDRESS_TYPE_CC]
        )) {
            return null;
        }

        $address = new D3pop3EmailAddress();
        $address->address_type = D3pop3EmailAddress::ADDRESS_TYPE_BCC;
        $address->email_address = $email;
        $address->name = $name;
        $this->addressList[] = $address;
        return $this;
    }
}
