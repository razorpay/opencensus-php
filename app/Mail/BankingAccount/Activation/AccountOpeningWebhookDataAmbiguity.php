<?php


namespace RZP\Mail\BankingAccount\Activation;


use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;

class AccountOpeningWebhookDataAmbiguity extends Base
{
    const SUBJECT       = 'Important | RBL Webhook Data Mismatch Alert';

    public function __construct(string $bankingAccountId, array $eventDetails)
    {
        $this->eventDetails = $eventDetails;

        parent::__construct($bankingAccountId, $eventDetails);
    }

    protected function addMailData()
    {
        $data = [
            'merchantId'      => $this->bankingAccount[Entity::MERCHANT_ID],
            'bankReferenceNumber' => $this->bankingAccount->getBankReferenceNumber(),
            'razorpayDetails' => [
                'businessName'        => $this->bankingAccount->merchant->merchantDetail->getBusinessName(),
                'pinCode'             => $this->bankingAccount[Entity::PINCODE],
                'businessCity'        => $this->bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_CITY],
                'businessAddress'     => $this->bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_DOCUMENTS_ADDRESS],
                'bankReferenceNumber' => $this->bankingAccount->getBankReferenceNumber(),
                'email'               => $this->bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_POC_EMAIL],
                'phoneNumber'         => $this->bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER],

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
