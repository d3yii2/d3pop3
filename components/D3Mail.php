<?php

namespace d3yii2\d3pop3\components;

use d3system\exceptions\D3ActiveRecordException;
use d3yii2\d3files\components\FileHandler;
use d3yii2\d3files\models\D3files;
use d3yii2\d3files\models\D3filesModel;
use d3yii2\d3files\models\D3filesModelName;
use d3yii2\d3pop3\dictionaries\ConnectingSettingsDict;
use d3yii2\d3pop3\models\D3pop3ConnectingSettings;
use d3yii2\d3pop3\models\D3pop3Email;
use d3yii2\d3pop3\models\D3pop3EmailAddress;
use d3yii2\d3pop3\models\D3pop3EmailModel;
use d3yii2\d3pop3\models\D3pop3RegexMasks;
use d3yii2\d3pop3\models\D3pop3SendReceiv;
use d3yii2\d3pop3\models\D3pPerson;
use d3yii2\d3pop3\models\TypeSmtpForm;
use eaBlankonThema\components\FlashHelper;
use Html2Text\Html2Text;
use Html2Text\Html2TextException;
use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\db\Exception as DbException;
use yii\db\Expression;
use yii\helpers\FileHelper;
use yii\helpers\VarDumper;
use yii\swiftmailer\Message;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii2d3\d3emails\logic\Email;
use yii2d3\d3emails\models\base\D3pop3EmailSignature;
use yii2d3\d3emails\models\forms\MailForm;
use yii2d3\d3persons\models\D3pPersonContact;
use yii2d3\d3persons\models\User;

use function basename;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function get_class;
use function in_array;
use function is_array;
use function strlen;
use function uniqid;

/**
 * Class D3Mail
 * @package d3yii2\d3pop3\components
 */
class D3Mail
{
    private const EMPTY_NAME = '';
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
     * @return D3pop3Email
     */
    public function getEmail(): D3pop3Email
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
     * @param string $bodyHtml
     * @return D3Mail
     */
    public function setBodyHtml($bodyHtml): self
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
     * @param string $fileTypes
     * @return $this
     */
    public function addAttachment(
        $fileName,
        $filePath,
        $fileTypes = '/(gif|pdf|dat|jpe?g|png|doc|docx|xls|xlsx|htm|txt|log|mxl|xml|zip)$/i'
    ): self {
        $this->attachmentList[] = compact('fileName', 'filePath', 'fileTypes');

        return $this;
    }

    /**
     * @param $fileName
     * @param $content
     * @param string $fileTypes
     * @return $this
     */
    public function addAttachmentContent(
        $fileName,
        $content,
        $fileTypes = '/(gif|pdf|dat|jpe?g|png|doc|docx|xls|xlsx|htm|txt|log|mxl|xml|zip)$/i'
    ): self {
        $this->attachmentContentList[] = compact('fileName', 'content', 'fileTypes');

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmailId(): ?string
    {
        return $this->email->id;
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
     */
    public function getAttachments(): ?array
    {
        try {
            return D3files::getRecordFilesList(D3pop3Email::class, $this->email->id);
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
        try {
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
                        //\Yii::$app->mailer->setStreamOptions(['ssl' => ['allow_self_signed' => true, 'verify_peer' => false]]);
                    }

                    Yii::$app->mailer->setTransport($tranportConfig);
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
                foreach ($this->email->getD3pop3EmailAddresses()->all() as $address) {
                    switch ($address->address_type) {
                        case D3pop3EmailAddress::ADDRESS_TYPE_REPLAY:
                            $message->setReplyTo($address->fullAddress());
                            break;
                        case D3pop3EmailAddress::ADDRESS_TYPE_CC:
                            $message->setCc($address->fullAddress());
                            break;
                        case D3pop3EmailAddress::ADDRESS_TYPE_BCC:
                            $message->setBcc($address->fullAddress());
                            break;
                        default:
                            $message->setTo($address->fullAddress());
                    }
                }

                try {
                    foreach (D3files::getRecordFilesList(D3pop3Email::class, $this->email->id) as $file) {
                        $message->attach($file['file_path'], ['fileName' => $file['file_name']]);
                    }

                    return $message->send();
                } catch (\Exception $e) {
                    $err = 'Send exception message: ' . $e->getMessage() . PHP_EOL
                       . $e->getTraceAsString() . PHP_EOL
                       . (
                           isset($tranportConfig)
                            ? 'Can not send email. ' . VarDumper::dumpAsString($tranportConfig)
                            : 'Can not send by default mailer.'
                    );
                    Yii::error($err);
                    return false;
                }
            } catch (\Exception $e) {
                Yii::error(
                    'Cannot set the mail attributes in mailer.'.PHP_EOL
                    . ' Error: ' . $e->getMessage().PHP_EOL
                    . $e->getTraceAsString()
                );
                return false;
            }
        } catch (\Exception $e) {
            Yii::error(
                'Cannot set the custom SMTP connection'.PHP_EOL
                . ' Error: ' . $e->getMessage().PHP_EOL
                . $e->getTraceAsString()
            );
            return false;
        }
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
    ): self
    {
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
    public function updateSendReceiveStatus(string $status = D3pop3SendReceiv::STATUS_SENT): self
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
            ->addSendReceiveOutFromCompany(0, \d3yii2\d3pop3\models\base\D3pop3SendReceiv::STATUS_DRAFT);

        if ($replyAddreses = $this->getReplyAddreses()) {
            $replyD3Mail->addAddressTo($replyAddreses[0]->email_address, $replyAddreses[0]->name);
        } else {
            $replyD3Mail->addAddressTo($this->email->from, $this->email->from_name);
        }
        try {
            $replyD3Mail->save();
        } catch (\Exception $e) {
        }

        return $replyD3Mail;
    }

    /**
     * @param string $bodyPlain
     * @return $this
     */
    public function setBodyPlain($bodyPlain): self
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
        /** @var D3pop3EmailAddress $address */
        foreach ($this->getEmailAddress() as $address) {
            if ($address->address_type === D3pop3EmailAddress::ADDRESS_TYPE_REPLAY) {
                $list[] = $address;
            }
        }

        return $list;
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
    public function addAddressTo(string $email, $name = null): self
    {
        $address = new D3pop3EmailAddress();
        $address->address_type = D3pop3EmailAddress::ADDRESS_TYPE_TO;
        $address->email_address = $email;
        $address->name = $name;
        $this->addressList[] = $address;
        return $this;
    }

    /**
     * @throws D3ActiveRecordException
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws \ReflectionException
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

        $this->saveAddressList();

        $this->saveSendReceive();

        $this->saveEmailModelList();

        $this->saveAddressList();

        $this->saveAttachmentContentList();
    }

    /**
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws ReflectionException
     */
    public function saveAttachmentContentList(): void
    {
        foreach ($this->attachmentContentList as $attachment) {
            $ext = pathinfo($attachment['fileName'], PATHINFO_EXTENSION);
            if (!preg_match($attachment['fileTypes'], $ext)) {
                continue;
            }
            D3files::saveContent(
                $attachment['fileName'],
                D3pop3Email::class,
                $this->email->id,
                $attachment['content'],
                $attachment['fileTypes']
            );
        }
    }

    /**
     * @throws \Exception
     */
    public function saveAttachmentsList(): void
    {
        foreach ($this->attachmentList as $attachment) {
            $ext = pathinfo($attachment['fileName'], PATHINFO_EXTENSION);
            if (!preg_match($attachment['fileTypes'], $ext)) {
                continue;
            }
            D3files::saveFile(
                $attachment['fileName'],
                D3pop3Email::class,
                $this->email->id,
                $attachment['filePath'],
                $attachment['fileTypes']
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
     * @throws D3ActiveRecordException
     */
    public function saveAddressList(): void
    {
        foreach ($this->addressList as $address) {
            /** @var D3pop3EmailAddress $address_id */
            $address->email_id = $this->email->id;
            if (!$address->save()) {
                $errors = $address->getErrors();
                if (isset($errors['email_address'])) {
                    throw new D3ActiveRecordException($address, null, '', ['email_address']);
                }
                throw new D3ActiveRecordException($address);
            }
        }
    }

    /**
     * @throws D3ActiveRecordException
     */
    public function saveSendReceive(): void
    {
        foreach ($this->sendReceiveList as $sendReceive) {
            $sendReceive->email_id = $this->email->id;
            if (!$sendReceive->save()) {
                throw new D3ActiveRecordException($sendReceive);
            }
        }
    }

    /**
     * @return D3Mail
     * @throws Exception
     * @throws Html2TextException
     * @throws \Exception
     */
    public function createComposed(): self
    {
        /** @var D3pop3ConnectingSettings $settings */
        $settings = D3pop3ConnectingSettings::findOne($this->email->email_container_id);

        if (empty($settings->email)) {
            throw new Exception(Yii::t('d3pop3', 'Please set email in My Company Email Settings'));
        }

        $replyD3Mail = new self();

        $replyD3Mail->setEmailId([
            'Composed',
            Yii::$app->SysCmp->getActiveCompanyId(),
            'MAIL',
            $this->email->id,
            date('YmdHis'),
        ])
            ->setSubject($this->email->subject)
            ->setBodyPlain('> ' . str_replace("\n", "\n> ", $this->getPlainBody()))
            ->setFromEmail($settings->email)
            ->setFromName(Yii::$app->person->firstName . ' ' . Yii::$app->person->lastName)
            ->addSendReceiveOutFromCompany();

        if ($replyAddreses = $this->getReplyAddreses()) {
            $replyD3Mail->addAddressTo($replyAddreses[0]->email_address, $replyAddreses[0]->name);
        } else {
            $replyD3Mail->addAddressTo($this->email->from, $this->email->from_name);
        }
        try {
            $replyD3Mail->save();
        } catch (\Exception $e) {
        }

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

        if (!empty($toAdreses[0]->email_address)) {
            $form->to[] = $toAdreses[0]->email_address ?? '';
        }
        $form->to_name = isset($toAdreses[0]->name)
            ? $toAdreses[0]->name . ' &lt;' . $toAdreses[0]->email_address . '&gt;'
            : self::EMPTY_NAME;

        $form->subject = $this->email->subject;

        $signatureModel = Email::getActiveCompanySignatureModel();

        if ($signatureModel
            && !empty($signatureModel->signature)
            && D3pop3EmailSignature::POSITION_TOP === $signatureModel->position
        ) {
            $form->body .= $signatureModel->signature . PHP_EOL . PHP_EOL;
        }

        $form->body .= $this->email->body_plain;

        if ($signatureModel
            && !empty($signatureModel->signature)
            && D3pop3EmailSignature::POSITION_BOTTOM === $signatureModel->position
        ) {
            $form->body .= PHP_EOL . PHP_EOL . $signatureModel->signature;
        }

        return $form;
    }

    /**
     * @return array|D3pop3EmailAddress[]
     */
    public function getToAdreses(): array
    {
        /** @var D3pop3EmailAddress[] $list */
        $list = [];
        /** @var D3pop3EmailAddress $address */
        foreach ($this->getEmailAddress() as $address) {
            if ($address->address_type === D3pop3EmailAddress::ADDRESS_TYPE_TO) {
                $list[] = $address;
            }
        }

        return $list;
    }

    /**
     * @param MailForm $form
     * @return bool
     * @throws Exception
     */
    public function loadFromForm(MailForm $form): bool
    {
        $this->setFromEmail($form->from)
            ->setFromName($form->from_name)
            ->clearAddressTo()
            ->setSubject($form->subject)
            ->setBodyPlain($form->body);

        $this->setRecipients($form, 'to', D3pop3EmailAddress::ADDRESS_TYPE_TO);
        $this->setRecipients($form, 'cc', D3pop3EmailAddress::ADDRESS_TYPE_CC);
        $this->setRecipients($form, 'bcc', D3pop3EmailAddress::ADDRESS_TYPE_BCC);

        return true;
    }

    /**
     * @return $this
     */
    private function clearAddressTo(): self
    {
        $this->addressList = [];
        return $this;
    }

    /**
     * @param MailForm $form
     * @param string $attr
     * @param string $type
     * @throws Exception
     */
    private function setRecipients(MailForm $form, string $attr, string $type): void
    {
        $contactIds = [];
        $emails = [];

        foreach ($form->{$attr} as $i => $target) {
            if (!is_numeric($target) && !in_array($target, $emails, true)) {
                $emails[] = $target;
                continue;
            }
            $contactIds[] = (int)$target;
        }

        if (!empty($contactIds)) {
            $contacts = D3pPersonContact::find()->where(['in', 'id', $contactIds])->with('person')->all();

            if (!$contacts) {
                throw new Exception('Contacts not found by ID list: ' . implode(',', $contacts));
            }

            foreach ($contacts as $contact) {
                $name = $contact->person->first_name . ' ' . $contact->person->last_name;

                $methodName = 'addAddress' . $type;

                $this->$methodName($contact->contact_value, $name);
            }
        }

        foreach ($emails as $email) {
            switch ($type) {
                case D3pop3EmailAddress::ADDRESS_TYPE_REPLAY:
                    $this->addAddressReply($email, self::EMPTY_NAME);
                    break;
                case D3pop3EmailAddress::ADDRESS_TYPE_CC:
                    $this->addAddressCc($email, self::EMPTY_NAME);
                    break;
                case D3pop3EmailAddress::ADDRESS_TYPE_BCC:
                    $this->addAddressBcc($email, self::EMPTY_NAME);
                    break;
                default:
                    $this->addAddressTo($email, self::EMPTY_NAME);
            }
        }
    }

    /**
     * @param string $email
     * @param string|null $name
     * @return $this
     */
    public function addAddressReply(string $email, $name = null): self
    {
        $address = new D3pop3EmailAddress();
        $address->address_type = D3pop3EmailAddress::ADDRESS_TYPE_REPLAY;
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
    public function addAddressCc(string $email, $name = null): ?self
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
    public function addAddressBcc(string $email, $name = null): ?self
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
