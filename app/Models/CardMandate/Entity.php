<?php

namespace RZP\Models\CardMandate;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    const MANDATE_SUMMARY_URL = 'mandate_summary_url';
    const MANDATE_REGISTER_ID = 'mandate_register_id';
    const MANDATE_ID          = 'mandate_id';
    const STATUS              = 'status';

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
