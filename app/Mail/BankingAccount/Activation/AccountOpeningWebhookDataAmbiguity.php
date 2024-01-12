<?php


namespace RZP\Mail\BankingAccount\Activation;

use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;

class AccountOpeningWebhookDataAmbiguity extends BaseV2
{
    const SUBJECT       = 'Important | RBL Webhook Data Mismatch Alert';

    public function __construct(array $bankingAccount, array $eventDetails)
    {
        $this->eventDetails = $eventDetails;

        parent::__construct($bankingAccount, $eventDetails);
    }

    protected function addMailData()
    {
        /** @var \RZP\Models\Merchant\Detail\Entity $merchantDetail */
        $merchantDetail = (new \RZP\Models\Merchant\Detail\Repository)->findOrFail($this->bankingAccount[Entity::MERCHANT_ID]);

        $data = [
            'merchantId'            => $this->bankingAccount[Entity::MERCHANT_ID],
            'bankReferenceNumber'   => $this->bankingAccount[Entity::BANK_REFERENCE_NUMBER],
            'razorpayDetails' => [
                'businessName'        => $merchantDetail->getBusinessName(),
                'pinCode'             => $this->bankingAccount[Entity::PINCODE],
                'businessCity'        => $this->bankingAccount[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::MERCHANT_CITY],
                'businessAddress'     => $this->bankingAccount[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::MERCHANT_DOCUMENTS_ADDRESS],
                'bankReferenceNumber' => $this->bankingAccount[Entity::BANK_REFERENCE_NUMBER],
                'email'               => $this->bankingAccount[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::MERCHANT_POC_EMAIL],
                'phoneNumber'         => $this->bankingAccount[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER],

            ],
            'rblWebhookDetails' => [
                'businessName'        => $this->eventDetails['properties'][Entity::BENEFICIARY_NAME],
                'pinCode'             => $this->eventDetails['properties'][Entity::BENEFICIARY_PIN],
                'businessCity'        => $this->eventDetails['properties'][Entity::BENEFICIARY_CITY],
                'businessAddress'     => $this->eventDetails['properties'][Entity::BENEFICIARY_ADDRESS1],
                'bankReferenceNumber' => $this->eventDetails['properties'][Entity::BANK_REFERENCE_NUMBER],
                'email'               => $this->eventDetails['properties'][Entity::BENEFICIARY_EMAIL],
                'phoneNumber'         => $this->eventDetails['properties'][Entity::BENEFICIARY_MOBILE]
            ]
        ];

        $this->with($data);

        return parent::addMailData();
    }

    protected function addHtmlView()
    {
        $this->view('emails.banking_account.data_ambiguity_alert');

        return $this;
    }

    protected function getSubject()
    {
        return self::SUBJECT;
    }
}
