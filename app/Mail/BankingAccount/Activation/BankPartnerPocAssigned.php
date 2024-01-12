<?php

namespace RZP\Mail\BankingAccount\Activation;

use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Activation\Detail\Entity as ActivationEntity;

class BankPartnerPocAssigned extends BaseV2
{
    const SUBJECT = "Assigned to You";
    
    const PARTNER_LMS_LEAD_LINK_FORMAT = '%s/lead-details/bacc_%s';

    protected $merchantBusinessName;

    protected $merchantName;

    public function __construct(array $bankingAccount, array $eventDetails)
    {
        parent::__construct($bankingAccount, $eventDetails);

        /** @var \RZP\Models\Merchant\Entity $merchant */
        $merchant = (new \RZP\Models\Merchant\Repository)->findOrFail($this->bankingAccount[Entity::MERCHANT_ID]);

        $this->merchantName = $merchant->getName();

        $this->merchantBusinessName = $merchant->merchantDetail->getBusinessName();
    }

    protected function getSubject(): string
    {
        $reference_number = $this->bankingAccount[Entity::BANK_REFERENCE_NUMBER];

        $merchant_name = $this->merchantName;

        $constitution_type = $this->bankingAccount[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][Entity::BUSINESS_CATEGORY];

        $constitution_type = ucwords(str_replace('_', ' ', $constitution_type));

        return self::SUBJECT.": #".$reference_number." | ".$merchant_name." | ".$constitution_type;
    }

    protected function addMailData()
    {
        $data = $this->viewData;

        $bankingAccountActivationDetails = $this->bankingAccount[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS];

        $constitution_type = $bankingAccountActivationDetails[Entity::BUSINESS_CATEGORY];

        $constitution_type = ucwords(str_replace('_', ' ', $constitution_type));

        $config = app()->config;

        $bankingUrl = $config['applications.bank_lms_banking_service_url'];

        $partner_lms_link = sprintf(self::PARTNER_LMS_LEAD_LINK_FORMAT, $bankingUrl, $this->bankingAccount[Entity::ID]);

        $data[ActivationEntity::MERCHANT_POC_NAME] = $bankingAccountActivationDetails[ActivationEntity::MERCHANT_POC_NAME];

        $data[ActivationEntity::MERCHANT_POC_PHONE_NUMBER] = $bankingAccountActivationDetails[ActivationEntity::MERCHANT_POC_PHONE_NUMBER];

        $data["constitution_type"] = $constitution_type;

        $data["partner_lms_link"] = $partner_lms_link;

        $this->with($data);

        return parent::addMailData();
    }

    protected function addHtmlView()
    {
       $this->view('emails.banking_account.bank-partner-poc-assigned');
    
       return $this;
    }
}
