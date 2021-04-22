<?php

namespace RZP\Models\CardMandate;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    const MANDATE_ID                 = 'mandate_id';
    const MANDATE_CARD_ID            = 'mandate_card_id';
    const MANDATE_CARD_NAME          = 'mandate_card_name';
    const MANDATE_CARD_LAST4         = 'mandate_card_last4';
    const MANDATE_CARD_NETWORK       = 'mandate_card_network';
    const MANDATE_CARD_TYPE          = 'mandate_card_type';
    const MANDATE_CARD_ISSUER        = 'mandate_card_issuer';
    const MANDATE_CARD_INTERNATIONAL = 'mandate_card_international';
    const MANDATE_SUMMARY_URL        = 'mandate_summary_url';
    const MANDATE_REGISTER_ID        = 'mandate_register_id';
    const STATUS                     = 'status';
    const DEBIT_TYPE                 = 'debit_type';
    const CURRENCY                   = 'currency';
    const MAX_AMOUNT                 = 'max_amount';
    const AMOUNT                     = 'amount';
    const START_AT                   = 'start_at';
    const END_AT                     = 'end_at';
    const TOTAL_CYCLES               = 'total_cycles';
    const MANDATE_INTERVAL           = 'mandate_interval';
    const FREQUENCY                  = 'frequency';
    const PAUSED_BY                  = 'paused_by';
    const CANCELLED_BY               = 'cancelled_by';

    protected $entity = 'card_mandate';

    protected $generateIdOnCreate = true;

    protected $fillable = [
    ];

    protected $public = [
        self::ID,
        self::MANDATE_REGISTER_ID,
        self::MANDATE_SUMMARY_URL,
        self::STATUS,
        self::CREATED_AT,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::MANDATE_REGISTER_ID,
        self::MANDATE_SUMMARY_URL,
        self::STATUS,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::STATUS => Status::CREATED,
    ];

    public function setMandateSummaryUrl($url)
    {
        $this->setAttribute(self::MANDATE_SUMMARY_URL, $url);
    }

    public function setMandateRegisterId($url)
    {
        $this->setAttribute(self::MANDATE_REGISTER_ID, $url);
    }

    public function setMandateId($url)
    {
        $this->setAttribute(self::MANDATE_ID, $url);
    }

    public function setStatus($status)
    {
        Status::checkStatus($status);

        $previousState = $this->getStatus();

        Status::checkStatusChange($previousState, $status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function getMaxAmount()
    {
        return Constants::MANDATE_HQ_MAX_AMOUNT_DEFAULT;
    }

    public function getMandateSummaryUrl()
    {
        return $this->getAttribute(self::MANDATE_SUMMARY_URL);
    }

    public function getMandateRegisterId()
    {
        return $this->getAttribute(self::MANDATE_REGISTER_ID);
    }

    public function getMandateId()
    {
        return $this->getAttribute(self::MANDATE_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    // Relations
    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }
}
