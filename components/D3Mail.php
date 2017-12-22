<?php

namespace d3yii2\d3pop3\components;

use d3yii2\d3pop3\models\D3pop3Email;
use d3yii2\d3pop3\models\D3pop3EmailAddress;
use d3yii2\d3files\models\D3files;
use d3yii2\d3pop3\models\D3pop3EmailModel;
use d3yii2\d3pop3\models\D3pop3SendReceiv;
use yii\db\ActiveRecord;

class D3Mail
{
    /** @var D3pop3Email */
    private $email;

    /** @var string */
    private $emailId;

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
    public function setBodyPlain(string $bodyPlain)
    {
        $this->bodyPlain = $bodyPlain;
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

    /** @var string */
    private $subject;

    /** @var string */
    private $bodyPlain;

    private $from_name;

    private $from_email;

    /** @var D3pop3EmailAddress[] */
    private $addressList = [];

    /** @var array D3pop3SendReceiv[] */
    private $sendReceiveList = [];

    /** @var array  D3pop3EmailModel */
    private $emailModelList = [];

    /** @var array */
    private $attachmentList = [];

    /**
     * @param string $email
     * @param string $name
     * @return $this
     */
    public function addAddressTo(string $email, string $name = '')
    {
        $address = new D3pop3EmailAddress();
        $address->address_type = D3pop3EmailAddress::ADDRESS_TYPE_TO;
        $address->email_address = $email;
        $address->name = $name;
        $this->addressList[] = $address;
        return $this;
    }

    public function addSendReceiveOutFromCompany($companyId)
    {
        $sendReceiv = new D3pop3SendReceiv();
        $sendReceiv->direction = D3pop3SendReceiv::DIRECTION_OUT;
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
        $this->email = new D3pop3Email();
        $this->email->email_datetime = date('Y-m-d H:i:s');
        $this->email->receive_datetime = date('Y-m-d H:i:s');
        $this->email->subject = $this->subject;
        $this->email->body_plain = $this->bodyPlain;
        $this->email->from_name = $this->from_name;
        $this->email->from = $this->from_email;
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

    public function send(): bool
    {
        $message = \Yii::$app->mailer->compose()
            ->setFrom($this->email->from)
            ->setSubject($this->email->subject)
            ->setTextBody($this->email->body_plain)//->setHtmlBody('<b>HTML content</b>')
        ;
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

        foreach (D3files::getRecordFilesList(D3pop3Email::className(), $this->email->id) as $file) {
            $message->attach($file['file_path'], ['fileName' => $file['file_name']]);
        }

        return $message->send();
    }
}