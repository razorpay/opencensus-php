<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;
use RZP\Models\Schedule;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Models\Schedule\Task as ScheduleTask;

class Entity extends Merchant\Entity
{
    const CIN                      = 'cin';
    const PAN                      = 'pan';
    const PIN                      = 'pin';
    const CITY                     = 'city';
    const TYPE                     = 'type';
    const MODEL                    = 'model';
    const GSTIN                    = 'gstin';
    const NOTES                    = 'notes';
    const STATE                    = 'state';
    const STATUS                   = 'status';
    const MOBILE                   = 'mobile';
    const P_GSTIN                  = 'p_gstin';
    const ADDRESS                  = 'address';
    const COUNTRY                  = 'country';
    const PAN_NAME                 = 'pan_name';
    const LANDLINE                 = 'landline';
    const SCHEDULE                 = 'schedule';
    const CAN_SUBMIT               = 'can_submit';
    const DESTINATION              = 'destination';
    const KYC_DETAILS              = 'kyc_details';
    const PROMOTER_PAN             = 'promoter_pan';
    const FUNDS_ON_HOLD            = 'funds_on_hold';
    const FIELDS_PENDING           = 'fields_pending';
    const PAYMENTDETAILS           = 'paymentdetails';
    const BUSINESS_DETAILS         = 'business_details';
    const DATE_ESTABLISHED         = 'date_established';
    const SECONDARY_EMAILS         = 'secondary_emails';
    const ACTIVATION_STATUS        = 'activation_status';
    const PROMOTER_PAN_NAME        = 'promoter_pan_name';
    const ACTIVATION_DETAILS       = 'activation_details';
    const ADDRESS_PROOF_URL        = 'address_proof_file';
    const REGISTERED_ADDRESS       = 'registered_address';
    const SETTLEMENT_DETAILS       = 'settlement_details';
    const TRANSACTION_VOLUME       = 'transaction_volume';
    const BUSINESS_PROOF_URL       = 'business_proof_file';
    const OPERATIONAL_ADDRESS      = 'operational_address';
    const SETTLEMENT_SCHEDULES     = 'settlement_schedules';
    const MERCHANT_CONFIGURATIONS  = 'merchant_configurations';
    const AVERAGE_TRANSACTION_SIZE = 'average_transaction_size';

    protected static $sign = 'acc';

    protected static $delimiter = '_';

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::EMAIL,
        self::LIVE,
        self::SUSPENDED_AT,
        self::FUNDS_ON_HOLD,
        self::ACTIVATION_DETAILS,
        self::SECONDARY_EMAILS,
        self::BUSINESS_DETAILS,
        self::NOTES,
        self::SETTLEMENT_DETAILS,
        self::MERCHANT_CONFIGURATIONS
    ];

    protected $publicSetters = [
        self::ACTIVATION_DETAILS,
        self::SECONDARY_EMAILS,
        self::BUSINESS_DETAILS,
        self::NOTES,
        self::SETTLEMENT_DETAILS,
        self::MERCHANT_CONFIGURATIONS
    ];

    protected $embeddedRelations = [
        self::SETTLEMENT_SCHEDULES
    ];

    protected static $morphMap = [];

    public function settlementSchedules()
    {
        return $this->morphOne(ScheduleTask\Entity::class, 'entity');
    }

    public function getMorphClass()
    {
        return 'merchant';
    }

    public function schedules()
    {
        return $this->hasMany('RZP\Models\Schedule\Entity');
    }

    // ----------------------- Getters --------------------------------------------
    public function getSettlementDestination()
    {
        return $this->bankAccount()->first();
    }

    public function getSchedule()
    {
        return $this->schedules()->first();
    }

    public function getActivatedAt()
    {
        return $this->getAttribute(self::ACTIVATED_AT);
    }

    public function getActivationStatus()
    {
        return $this->merchantDetail->getAttribute(self::ACTIVATION_STATUS);
    }

    public function getNotes()
    {
        return $this->getAttribute(self::NOTES);
    }

    public function getRegisteredAddress()
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

    public function getOperationAddress()
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

    public function getKYCDetails()
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
        $array[self::ACTIVATION_DETAILS] = [
            self::ACTIVATED      => $this->isActivated(),
            self::ACTIVATED_AT   => $this->getActivatedAt(),
            self::STATUS         => $this->getActivationStatus(),
        ];
    }

    public function setPublicSecondaryEmailsAttribute(array & $array)
    {
        $merchantDetail = $this->merchantDetail;

        $transactionReportEmail = $merchantDetail->getTransactionReportEmail();
        $technicalSpocEmail     = $merchantDetail->getTechnicalSpocEmail();
        $businessSpocEmail      = $merchantDetail->getBusinessSpocEmail();

        $array[self::SECONDARY_EMAILS] = [
            MerchantDetail\Entity::TRANSACTION_REPORT_EMAIL => $transactionReportEmail,
            MerchantDetail\Entity::TECHNICAL_SPOC_EMAIL     => $technicalSpocEmail,
            MerchantDetail\Entity::BUSINESS_SPOC_EMAIL      => $businessSpocEmail,
        ];
    }

    public function setPublicBusinessDetailsAttribute(array & $array)
    {
        $merchantDetail = $this->merchantDetail;

        $array[self::BUSINESS_DETAILS] = [
            self::MOBILE                   => $this->merchantDetail->getContactMobile(),
            self::LANDLINE                 => $this->merchantDetail->getContactLandline(),
            self::TYPE                     => $this->merchantDetail->getBusinessType(),
            self::PAYMENTDETAILS           => $this->merchantDetail->getBusinessPaymentdetails(),
            self::MODEL                    => $this->merchantDetail->getBusinessModel(),
            self::REGISTERED_ADDRESS       => $this->getRegisteredAddress(),
            self::OPERATIONAL_ADDRESS      => $this->getOperationAddress(),
            self::DATE_ESTABLISHED         => $this->merchantDetail->getBusinessDateOfEstablishment(),
            self::TRANSACTION_VOLUME       => $this->merchantDetail->getTransactionVolume(),
            self::AVERAGE_TRANSACTION_SIZE => $merchantDetail->getTransactionValue(),
            self::KYC_DETAILS              => $this->getKYCDetails()
        ];
    }

    public function setPublicNotesAttribute(array & $array)
    {
        $array[self::NOTES] = $this->getNotes();
    }

    public function setPublicSettlementDetailsAttribute(array & $array)
    {
        $settlementDestinationId = null;

        $settlementDestination = $this->getSettlementDestination();

        if ($settlementDestination !== null)
        {
            $settlementDestinationId = BankAccount\Entity::getSignedId($settlementDestination->getId());
        }

        $schedule = $this->getSchedule()->toArrayPublic();

        // Unset the keys that are not required
        unset($schedule[Schedule\Entity::MERCHANT_ID]);
        unset($schedule[Schedule\Entity::ID]);

        $array[self::SETTLEMENT_DETAILS] = [
            self::DESTINATION => $settlementDestinationId,
            self::SCHEDULE    => $schedule
        ];
    }

    public function setPublicMerchantConfigurationsAttribute(array & $array)
    {
        $array[self::MERCHANT_CONFIGURATIONS] = [
            self::RECEIPT_EMAIL_ENABLED => $this->isReceiptEmailsEnabled(),
            self::BRAND_COLOR           => $this->getBrandColor()
        ];
    }
    // ----------------------- End of setters -------------------------------------

    public function scopeMerchantId($query, $merchantId)
    {
        $merchantIdColumn = $this->dbColumn(Entity::PARENT_ID);

        $query->where($merchantIdColumn, '=', $merchantId);
    }
}
