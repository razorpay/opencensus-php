<?php

namespace RZP\Mail\BankingAccount\Activation;

use RZP\Models\BankingAccount\Activation\Detail\Entity;

class BankPartnerAssigned extends Base
{
    const SUBJECT = "New Lead from Razorpay";
    
    const PARTNER_LMS_LEAD_LINK_FORMAT = '%s/lead-details/bacc_%s';

    protected $merchantBusinessName;

    public function __construct(string $bankingAccountId, array $eventDetails)
    {
        parent::__construct($bankingAccountId, $eventDetails);

        $this->merchantBusinessName = $this->bankingAccount->merchant->merchantDetail->getBusinessName();
    }

    protected function getSubject(): string
    {
        $reference_number = $this->bankingAccount->getBankReferenceNumber();

        $merchant_name = $this->bankingAccount->merchant->getName();

        $constitution_type = $this->bankingAccount->bankingAccountActivationDetails[Entity::BUSINESS_CATEGORY];
        
        $constitution_type = ucwords(str_replace('_', ' ', $constitution_type));

        return self::SUBJECT.": #".$reference_number." | ".$merchant_name." | ".$constitution_type;
    }

    protected function addMailData()
    {
        $data = $this->viewData;

        $bankingAccountActivationDetails = $this->bankingAccount->bankingAccountActivationDetails;

        $constitution_type = $bankingAccountActivationDetails[Entity::BUSINESS_CATEGORY];

        $constitution_type = ucwords(str_replace('_', ' ', $constitution_type));

        $config = app()->config;

        $bankingUrl = $config['applications.bank_lms_banking_service_url'];

        $partner_lms_link = sprintf(self::PARTNER_LMS_LEAD_LINK_FORMAT, $bankingUrl, $this->bankingAccount->getId());


        $data[Entity::MERCHANT_POC_NAME] = $bankingAccountActivationDetails[Entity::MERCHANT_POC_NAME];

        $data[Entity::MERCHANT_POC_PHONE_NUMBER] = $bankingAccountActivationDetails[Entity::MERCHANT_POC_PHONE_NUMBER];

        $data["constitution_type"] = $constitution_type;

        $data["partner_lms_link"] = $partner_lms_link;

        $this->with($data);

        return parent::addMailData();
    }

    protected function addHtmlView()
    {
       $this->view('emails.banking_account.bank-partner-assigned');
    
       return $this;
    }
}
