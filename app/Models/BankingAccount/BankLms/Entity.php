<?php

namespace RZP\Models\BankingAccount\BankLms;

use RZP\Constants\Table;
use \RZP\Models\BankingAccount;
use \RZP\Models\BankingAccount\Status;
use RZP\Models\Admin\Admin;
use \RZP\Models\BankingAccount\Activation\Detail\Entity as ActivationDetails;

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
    const COMPLETED_STAGES = 'completed_stages';
    const LEAD_FOLLOW_UP_DATE = 'lead_follow_up_date';

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
        self::ACCOUNT_ACTIVATION_DATE,
        self::BENEFICIARY_NAME,
        self::BANK_REFERENCE_NUMBER,
        self::PINCODE,
        self::BANKING_ACCOUNT_DETAILS,
        self::BANKING_ACCOUNT_ACTIVATION_DETAILS,
        self::MERCHANT_NAME,
        self::SENT_TO_BANK_DATE,
        self::COMPLETED_STAGES,
        self::LEAD_FOLLOW_UP_DATE,
        self::STATUS_LAST_UPDATED_AT,
        self::BANKING_ACCOUNT_CA_SPOC_DETAILS,
        self::USING_NEW_STATES,
        self::SPOCS,
        self::REVIEWERS,
    ];

    protected $publicSetters = [
        self::ID,
        self::BANKING_ACCOUNT_DETAILS,
        self::STATUS_LAST_UPDATED_AT,
        self::BANKING_ACCOUNT_ACTIVATION_DETAILS,
        self::MERCHANT_NAME,
        self::SENT_TO_BANK_DATE,
        self::COMPLETED_STAGES,
        self::LEAD_FOLLOW_UP_DATE,
        self::USING_NEW_STATES,
        self::SPOCS,
        self::REVIEWERS,
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
        self::ACCOUNT_ACTIVATION_DATE,
        self::BENEFICIARY_NAME,
        self::BANK_REFERENCE_NUMBER,
        self::PINCODE,
        self::BANKING_ACCOUNT_DETAILS,
        self::BANKING_ACCOUNT_ACTIVATION_DETAILS,
        self::MERCHANT_NAME,
        self::SENT_TO_BANK_DATE,
        self::COMPLETED_STAGES,
        self::LEAD_FOLLOW_UP_DATE,
        self::STATUS_LAST_UPDATED_AT,
        self::BANKING_ACCOUNT_CA_SPOC_DETAILS,
        self::USING_NEW_STATES,
        self::SPOCS,
        self::REVIEWERS,
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
        self::ACCOUNT_ACTIVATION_DATE,
        self::BENEFICIARY_NAME,
        self::BANK_REFERENCE_NUMBER,
        self::PINCODE,
        self::BANKING_ACCOUNT_DETAILS,
        self::BANKING_ACCOUNT_ACTIVATION_DETAILS,
        self::MERCHANT_NAME,
        self::SENT_TO_BANK_DATE,
        self::COMPLETED_STAGES,
        self::LEAD_FOLLOW_UP_DATE,
        self::STATUS_LAST_UPDATED_AT,
        self::BANKING_ACCOUNT_CA_SPOC_DETAILS,
        self::USING_NEW_STATES,
        self::SPOCS,
        self::REVIEWERS,
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
        $sentToBankLog = $this->activationStates()
            ->where(self::STATUS, '=', BankingAccount\Status::INITIATED)
            ->whereRaw('( `sub_status` IS NULL or `sub_status` = \'none\' )');

        if(empty($sentToBankLog))
        {
            $array[self::SENT_TO_BANK_DATE] = null;

            return;
        }

        $array[self::SENT_TO_BANK_DATE] = $sentToBankLog->pluck(self::CREATED_AT)->last();
    }

    public function setPublicCompletedStagesAttribute(array & $array)
    {
        $bankingAccountStatus = $this->getStatus();

        if(empty($bankingAccountStatus) === false)
        {
            $array[self::COMPLETED_STAGES] = Status::getCompletedStages($bankingAccountStatus);
        }
    }

    public function setPublicLeadFollowUpDateAttribute(array & $array)
    {
        $followUpDate = $this->getReferenceDateForStatus();
        $array[self::LEAD_FOLLOW_UP_DATE] = $followUpDate;
    }

    public function setPublicSpocsAttribute(array & $array)
    {
        $id = $this->getPublicId();
        $bankingAccount = (new BankingAccount\Repository())->findByPublicId($id);
        $spocs = $bankingAccount->morphToMany(Admin\Entity::class, self::ENTITY, Table::ADMIN_AUDIT_MAP, self::ENTITY_ID, Entity::ADMIN_ID)
                ->withPivot(Entity::AUDITOR_TYPE)
                ->where(Entity::AUDITOR_TYPE, '=', 'spoc');

        $array[self::SPOCS] = $spocs->get()->toArrayPublic();
    }

    public function setPublicReviewersAttribute(array & $array)
    {
        $id = $this->getPublicId();
        $bankingAccount = (new BankingAccount\Repository())->findByPublicId($id);
        $reviewers = $bankingAccount->morphToMany(Admin\Entity::class, self::ENTITY, Table::ADMIN_AUDIT_MAP, self::ENTITY_ID, Entity::ADMIN_ID)
            ->withPivot(Entity::AUDITOR_TYPE)
            ->where(Entity::AUDITOR_TYPE, '=', 'reviewer');

        $array[self::REVIEWERS] = $reviewers->get()->toArrayPublic();
    }

    public function reviewers()
    {
        $id = $this->getPublicId();
        $bankingAccount = (new BankingAccount\Repository())->findByPublicId($id);
        return $bankingAccount->morphToMany(Admin\Entity::class, self::ENTITY, Table::ADMIN_AUDIT_MAP, self::ENTITY_ID, Entity::ADMIN_ID)
                ->withPivot(Entity::AUDITOR_TYPE)
                ->where(Entity::AUDITOR_TYPE, '=', 'reviewer');
    }

    // Sales POCs
    public function spocs()
    {
        $id = $this->getPublicId();
        $bankingAccount = (new BankingAccount\Repository())->findByPublicId($id);
        return $bankingAccount->morphToMany(Admin\Entity::class, self::ENTITY, Table::ADMIN_AUDIT_MAP, self::ENTITY_ID, Entity::ADMIN_ID)
                ->withPivot(Entity::AUDITOR_TYPE)
                ->where(Entity::AUDITOR_TYPE, '=', 'spoc');
    }

}
