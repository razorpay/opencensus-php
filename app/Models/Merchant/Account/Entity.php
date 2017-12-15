<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Models\Schedule\Task as ScheduleTask;

class Entity extends Merchant\Entity
{
    const NOTES                   = 'notes';
    const STATUS                  = 'status';
    const CAN_SUBMIT              = 'can_submit';
    const DESTINATION             = 'destination';
    const SCHEDULE                = 'schedule';
    const FUNDS_ON_HOLD           = 'funds_on_hold';
    const FIELDS_PENDING          = 'fields_pending';
    const BUSINESS_DETAILS        = 'business_details';
    const SECONDARY_EMAILS        = 'secondary_emails';
    const ACTIVATION_STATUS       = 'activation_status';
    const ACTIVATION_DETAILS      = 'activation_details';
    const SETTLEMENT_DETAILS      = 'settlement_details';
    const SETTLEMENT_SCHEDULES    = 'settlement_schedules';
    const MERCHANT_CONFIGURATIONS = 'merchant_configurations';

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
        self::MERCHANT_CONFIGURATIONS,
        self::CREATED_AT,
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

    // ----------------------- Getters --------------------------------------------
    public function getSettlementDestination()
    {
        return $this->bankAccount()->first();
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
        $array[self::BUSINESS_DETAILS] = '';
    }

    public function setPublicNotesAttribute(array & $array)
    {
        $array[self::NOTES] = $this->getNotes();
    }

    public function setPublicSettlementDetailsAttribute(array & $array)
    {
        $array[self::SETTLEMENT_DETAILS] = [
            self::DESTINATION => $this->getSettlementDestination(),
            self::SCHEDULE    => $this->getSettlementSchedule()
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
