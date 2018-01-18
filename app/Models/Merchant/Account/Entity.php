<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\Detail as MerchantDetail;

class Entity extends Merchant\Entity
{
    const CIN                      = 'cin';
    const PAN                      = 'pan';
    const PIN                      = 'pin';
    const CITY                     = 'city';
    const GSTIN                    = 'gstin';
    const NOTES                    = 'notes';
    const STATE                    = 'state';
    const STATUS                   = 'status';
    const MOBILE                   = 'mobile';
    const P_GSTIN                  = 'p_gstin';
    const ADDRESS                  = 'address';
    const COUNTRY                  = 'country';
    const MANAGED                  = 'managed';
    const PAN_NAME                 = 'pan_name';
    const LANDLINE                 = 'landline';
    const SCHEDULE                 = 'schedule';
    const IFSC_CODE                = 'ifsc_code';
    const CAN_SUBMIT               = 'can_submit';
    const DESTINATION              = 'destination';
    const KYC_DETAILS              = 'kyc_details';
    const ACCOUNT_TYPE             = 'account_type';
    const BANK_ACCOUNT             = 'bank_account';
    const PROMOTER_PAN             = 'promoter_pan';
    const TNC_ACCEPTED             = 'tnc_accepted';
    const BUSINESS_NAME            = 'business_name';
    const BUSINESS_TYPE            = 'business_type';
    const FUND_TRANSFER            = 'fund_transfer';
    const FUNDS_ON_HOLD            = 'funds_on_hold';
    const ACCOUNT_NUMBER           = 'account_number';
    const BUSINESS_MODEL           = 'business_model';
    const CONFIGURATIONS           = 'configurations';
    const FIELDS_PENDING           = 'fields_pending';
    const PAYMENTDETAILS           = 'paymentdetails';
    const ACCOUNT_DETAILS          = 'account_details';
    const BENEFICIARY_NAME         = 'beneficiary_name';
    const DATE_ESTABLISHED         = 'date_established';
    const SECONDARY_EMAILS         = 'secondary_emails';
    const ACTIVATION_STATUS        = 'activation_status';
    const PROMOTER_PAN_NAME        = 'promoter_pan_name';
    const ACTIVATION_DETAILS       = 'activation_details';
    const ADDRESS_PROOF_URL        = 'address_proof_file';
    const REGISTERED_ADDRESS       = 'registered_address';
    const TRANSACTION_VOLUME       = 'transaction_volume';
    const BUSINESS_PROOF_URL       = 'business_proof_file';
    const OPERATIONAL_ADDRESS      = 'operational_address';
    const SETTLEMENT_SCHEDULES     = 'settlement_schedules';
    const AVERAGE_TRANSACTION_SIZE = 'average_transaction_size';

    protected static $sign = 'acc';

    protected static $delimiter = '_';

    protected $entity = 'account';

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::EMAIL,
        self::LIVE,
        self::MANAGED,
        self::TNC_ACCEPTED,
        self::FUNDS_ON_HOLD,
        self::ACTIVATION_DETAILS,
        self::SECONDARY_EMAILS,
        self::ACCOUNT_DETAILS,
        self::NOTES,
        self::FUND_TRANSFER,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::MANAGED,
        self::NOTES,
        self::TNC_ACCEPTED,
        self::FUND_TRANSFER,
        self::CONFIGURATIONS,
        self::ACCOUNT_DETAILS,
        self::SECONDARY_EMAILS,
        self::ACTIVATION_DETAILS,
    ];

    protected static $generators = [
        self::ID,
        self::INVOICE_CODE,
    ];

    public static $bankAccountToDetailAttributesMap = [
        Entity::IFSC_CODE            => MerchantDetail\Entity::BANK_BRANCH_IFSC,
        Entity::ACCOUNT_NUMBER       => MerchantDetail\Entity::BANK_ACCOUNT_NUMBER,
        Entity::ACCOUNT_TYPE         => MerchantDetail\Entity::BANK_ACCOUNT_TYPE,
        Entity::BENEFICIARY_NAME     => MerchantDetail\Entity::BANK_ACCOUNT_NAME,
    ];

    /**
     * This function is used in case of polymorphic relations where we associate one entity
     * with multiple other entities using (entity_type and entity_id). It determines the string that
     * will be stored for entity_type when the association is with the Account entity. A mapping is
     * maintained in ApiServiceProvider class :: registerMorphRelationMaps function. But, since the
     * array used there is an associative array and an entry for the key 'merchant' already exists,
     * we are using getMorphClass function instead of the updating the map.
     *
     * @return string
     */
    public function getMorphClass()
    {
        return 'merchant';
    }

    /**
     * Same as above ^
     *
     * @return string
     */
    public function getForeignKey()
    {
        return self::MERCHANT_ID;
    }

    public function schedules()
    {
        return $this->hasMany('RZP\Models\Schedule\Entity');
    }

    // ----------------------- Getters --------------------------------------------

    public function getSettlementDestination()
    {
        return $this->bankAccount;
    }

    public function getActivatedAt()
    {
        return $this->getAttribute(self::ACTIVATED_AT);
    }

    public function getActivationStatus()
    {
        return $this->merchantDetail->getAttribute(self::ACTIVATION_STATUS);
    }

    public function getRegisteredAddress(): array
    {
        $merchantDetail = $this->merchantDetail;

        $address = [
            self::ADDRESS => $merchantDetail->getBusinessRegisteredAddress(),
            self::CITY    => $merchantDetail->getBusinessRegisteredCity(),
            self::STATE   => $merchantDetail->getBusinessRegisteredState(),
            self::PIN     => $merchantDetail->getBusinessRegisteredPin()
        ];

        return $address;
    }

    public function getOperationAddress(): array
    {
        $merchantDetail = $this->merchantDetail;

        $address = [
            self::ADDRESS => $merchantDetail->getBusinessOperationAddress(),
            self::CITY    => $merchantDetail->getBusinessOperationCity(),
            self::STATE   => $merchantDetail->getBusinessOperationState(),
            self::PIN     => $merchantDetail->getBusinessOperationPin()
        ];

        return $address;
    }

    public function getKycDetails(): array
    {
        $merchantDetail = $this->merchantDetail;

        $array = [
            self::CIN                => $merchantDetail->getCompanyCin(),
            self::GSTIN              => $merchantDetail->getGstin(),
            self::P_GSTIN            => $merchantDetail->getPGstin(),
            self::PAN                => $merchantDetail->getPan(),
            self::PAN_NAME           => $merchantDetail->getPanName(),
            self::PROMOTER_PAN       => $merchantDetail->getPromoterPan(),
            self::PROMOTER_PAN_NAME  => $merchantDetail->getPromoterPanName(),
            self::BUSINESS_PROOF_URL => $merchantDetail->getBusinessProofFile(),
            self::ADDRESS_PROOF_URL  => $merchantDetail->getAddressProofFile()
        ];

        return $array;
    }

    // ----------------------- End of getters -------------------------------------

    // ----------------------- Setters --------------------------------------------

    public function setPublicFundsOnHoldAttribute(array & $array)
    {
        $array[self::FUNDS_ON_HOLD] = $this->getHoldFunds();
    }

    public function setPublicActivationDetailsAttribute(array & $array)
    {
        $array[self::ACTIVATION_DETAILS] = $this->getActivationDetails();
    }

    public function setPublicManagedAttribute(array & $array)
    {
        $array[self::MANAGED] = $this->isManaged();
    }

    public function setPublicSecondaryEmailsAttribute(array & $array)
    {
        $transactionReportEmail = $this->getTransactionReportEmailAttribute();

        $array[self::SECONDARY_EMAILS] = [
            MerchantDetail\Entity::TRANSACTION_REPORT_EMAIL => $transactionReportEmail,
        ];
    }

    public function setPublicAccountDetailsAttribute(array & $array)
    {
        $array[self::ACCOUNT_DETAILS] = $this->getAccountDetails();
    }

    public function setPublicTncAcceptedAttribute(array & $array)
    {
        $array[self::TNC_ACCEPTED] = true;
    }

    public function setPublicNotesAttribute(array & $array)
    {
        $array[self::NOTES] = $this->getNotes();
    }

    public function setPublicFundTransferAttribute(array & $array)
    {
        $settlementDestinationId = null;

        $settlementDestination = $this->getSettlementDestination();

        if ($settlementDestination !== null)
        {
            $settlementDestination = $settlementDestination->toArrayPublic();

            $settlementDestinationId = $settlementDestination[BankAccount\Entity::ID];
        }

        $array[self::FUND_TRANSFER] = [
            self::DESTINATION => $settlementDestinationId,
        ];
    }

    public function setPublicConfigurationsAttribute(array & $array)
    {
        $array[self::CONFIGURATIONS] = [
            self::BRAND_COLOR => $this->getBrandColor()
        ];
    }

    // ----------------------- End of setters -------------------------------------

    /**
     * Defining this function helps us to add a filter for merchant_id in a query -
     * Eg: $this->newQuery()->merchantId($merchant->getId())
     *
     * This function overrides the function defined in Base\EloquentEx class.
     * The base function is used for fetching entities which have merchant_id. It is tightly
     * coupled with RepositoryFetch class's fetch(), fetchByIdAndMerchantId() etc methods.
     *
     * The same behaviour is required for marketplace merchants but instead of adding a filter
     * for merchant_id, the filter is required for parent_id. Defining a separate function named
     * scopeParentId and usage $this->newQuery()->parentId($parentMerchant->getId()) would have
     * been an ideal case, but would require the new function to be supported in all the above
     * mentioned functions of RepositoryFetch class. Hence, overriding the function definition here.
     *
     * Though the name is scopeMerchantId, it actually adds a filter for parent_id.
     *
     * @param $query
     * @param $merchantId
     */
    public function scopeMerchantId($query, $merchantId)
    {
        $parentIdColumn = $this->dbColumn(Entity::PARENT_ID);

        $query->where($parentIdColumn, '=', $merchantId);
    }

    /**
     * Managed accounts currently include only linked accounts
     *
     * @return bool
     */
    public function isManaged(): bool
    {
        return ($this->isLinkedAccount() === true);
    }

    /**
     * Returns the public response array for the key account_details
     *
     * @return array
     */
    protected function getAccountDetails(): array
    {
        $merchantDetail = $this->merchantDetail;

        $accountDetails = [
            self::MOBILE                   => $this->merchantDetail->getContactMobile(),
            self::LANDLINE                 => $this->merchantDetail->getContactLandline(),
            self::BUSINESS_NAME            => $this->merchantDetail->getBusinessName(),
            self::BUSINESS_TYPE            => $this->merchantDetail->getBusinessType(),
            self::PAYMENTDETAILS           => $this->merchantDetail->getBusinessPaymentdetails(),
            self::BUSINESS_MODEL           => $this->merchantDetail->getBusinessModel(),
            self::REGISTERED_ADDRESS       => $this->getRegisteredAddress(),
            self::OPERATIONAL_ADDRESS      => $this->getOperationAddress(),
            self::DATE_ESTABLISHED         => $this->merchantDetail->getBusinessDateOfEstablishment(),
            self::TRANSACTION_VOLUME       => $this->merchantDetail->getTransactionVolume(),
            self::AVERAGE_TRANSACTION_SIZE => $merchantDetail->getTransactionValue(),
            self::KYC_DETAILS              => $this->getKycDetails()
        ];

        return $accountDetails;
    }

    /**
     * Returns the public response for the key activation_details.
     * Other attributes like can_submit and required_fields are dynamically computed and
     * returned from the Account\Service class :: toArrayPublic function
     *
     * @return array
     */
    protected function getActivationDetails(): array
    {
        $activation_details = [
            self::STATUS       => $this->getActivationStatus(),
            self::ACTIVATED_AT => $this->getActivatedAt(),
        ];

        return $activation_details;
    }

    /**
     * Overriding the function because some of the params are computed dynamically,
     * using the functions of the Core class.g
     *
     * @return array
     */
    public function toArrayPublic()
    {
        $response = parent::toArrayPublic();

        Formatter::get()->computeAndSetAdditionalPublicAttributes($this, $response);

        return $response;
    }
}
