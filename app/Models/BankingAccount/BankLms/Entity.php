<?php

namespace RZP\Models\BankingAccount\BankLms;

use \RZP\Models\BankingAccount;

/**
 * This just inheriting banking account entity not a real one
 */
class Entity extends BankingAccount\Entity
{
    // Additional attributes to filter from Bank LMS dashboard
    const FILTER_MERCHANTS = 'filter_merchants';
    const BANK_POC_USER_ID = 'bank_poc_user_id';
    const MERCHANT_NAME = 'merchant_name';
    const SENT_TO_BANK_DATE = 'sent_to_bank_date';
    const BANK_POC_NAME = 'bank_poc_name';

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CHANNEL,
        self::STATUS,
        self::SUB_STATUS,
        self::MERCHANT_ID,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_IFSC,
        self::BANK_INTERNAL_STATUS,
        self::REFERENCE1,
        self::ACCOUNT_TYPE,
        self::ACCOUNT_CURRENCY,
        self::BENEFICIARY_EMAIL,
        self::BENEFICIARY_MOBILE,
        self::BENEFICIARY_NAME,
        self::BANK_REFERENCE_NUMBER,
        self::PINCODE,
        self::BANKING_ACCOUNT_DETAILS,
        self::BANKING_ACCOUNT_ACTIVATION_DETAILS,
        self::MERCHANT_NAME,
        self::SENT_TO_BANK_DATE,
        self::BANK_POC_NAME,
        self::STATUS_LAST_UPDATED_AT,
        self::BANKING_ACCOUNT_CA_SPOC_DETAILS
    ];

    protected $publicSetters = [
        self::ID,
        self::BANKING_ACCOUNT_DETAILS,
        self::STATUS_LAST_UPDATED_AT,
        self::BANKING_ACCOUNT_ACTIVATION_DETAILS,
        self::MERCHANT_NAME,
        self::SENT_TO_BANK_DATE,
        self::BANK_POC_NAME
    ];

    /**
     * @var array
     *  This will be used for toArrayCaPartnerBankPoc filter
     */
    protected $bankBranchPoc = [
        self::ID,
        self::ENTITY,
        self::CHANNEL,
        self::STATUS,
        self::SUB_STATUS,
        self::MERCHANT_ID,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_IFSC,
        self::BANK_INTERNAL_STATUS,
        self::REFERENCE1,
        self::ACCOUNT_TYPE,
        self::ACCOUNT_CURRENCY,
        self::BENEFICIARY_EMAIL,
        self::BENEFICIARY_MOBILE,
        self::BENEFICIARY_NAME,
        self::BANK_REFERENCE_NUMBER,
        self::PINCODE,
        self::BANKING_ACCOUNT_DETAILS,
        self::BANKING_ACCOUNT_ACTIVATION_DETAILS,
        self::MERCHANT_NAME,
        self::SENT_TO_BANK_DATE,
        self::BANK_POC_NAME,
        self::STATUS_LAST_UPDATED_AT,
        self::BANKING_ACCOUNT_CA_SPOC_DETAILS
    ];

    /**
     * @var array
     *  This will be used for toArrayCaPartnerBankManager filter
     */
    protected $bankBranchManager = [
        self::ID,
        self::ENTITY,
        self::CHANNEL,
        self::STATUS,
        self::SUB_STATUS,
        self::MERCHANT_ID,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_IFSC,
        self::BANK_INTERNAL_STATUS,
        self::REFERENCE1,
        self::ACCOUNT_TYPE,
        self::ACCOUNT_CURRENCY,
        self::BENEFICIARY_EMAIL,
        self::BENEFICIARY_MOBILE,
        self::BENEFICIARY_NAME,
        self::BANK_REFERENCE_NUMBER,
        self::PINCODE,
        self::BANKING_ACCOUNT_DETAILS,
        self::BANKING_ACCOUNT_ACTIVATION_DETAILS,
        self::MERCHANT_NAME,
        self::SENT_TO_BANK_DATE,
        self::BANK_POC_NAME,
        self::STATUS_LAST_UPDATED_AT,
        self::BANKING_ACCOUNT_CA_SPOC_DETAILS
    ];

    public function toArrayCaPartnerBankPoc(): array
    {
        $result = parent::toArrayAdmin();

        return array_only($result, $this->bankBranchPoc);
    }

    public function toArrayCaPartnerBankManager(): array
    {
        $result = parent::toArrayAdmin();

        return array_only($result, $this->bankBranchManager);
    }

    public function setPublicMerchantNameAttribute(array & $array)
    {
        $array[self::MERCHANT_NAME] = $this->merchant->getName();
    }

    public function setPublicSentToBankDateAttribute(array & $array)
    {
        $sentToBankLog = $this->activationStates->where(self::STATUS, '=', BankingAccount\Status::INITIATED);

        if(empty($sentToBankLog))
        {
            $array[self::SENT_TO_BANK_DATE] = null;

            return;
        }

        $array[self::SENT_TO_BANK_DATE] = $sentToBankLog->pluck(self::CREATED_AT)->first();
    }

    public function setPublicBankPocNameAttribute(array & $array)
    {
        $bankPocUser = $this->bankingAccountActivationDetails->first()->getBankPOCUser();

        if(empty($bankPocUser))
        {
            $array[self::BANK_POC_NAME] = null;

            return;
        }

        $array[self::BANK_POC_NAME] = $bankPocUser->getName();
    }
}
