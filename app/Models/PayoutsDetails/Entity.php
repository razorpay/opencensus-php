<?php

namespace RZP\Models\PayoutsDetails;

use RZP\Models\Base\PublicEntity;
use RZP\Models\Payout;
use RZP\Constants\Table;

/**
 * @property Payout\Entity $payout
 */
class Entity extends PublicEntity
{
    const PAYOUT_ID                 = 'payout_id';
    const QUEUE_IF_LOW_BALANCE_FLAG = 'queue_if_low_balance_flag';
    const TAX_PAYMENT_ID            = 'tax_payment_id';
    const TDS_CATEGORY_ID           = 'tds_category_id';
    const ADDITIONAL_INFO           = 'additional_info';
    const STATUS                    = 'status';
    const ERROR                     = 'error';
    const SUCCESS                   = 'SUCCESS';

    const TAX_PAYMENT_PUBLIC_ID_PREFIX  = 'txpy';
    const TAX_PAYMENT_ID_DELIMITER      = '_';

    // additional-info json keys
    const TDS_AMOUNT_KEY        = 'tds_amount';
    const SUBTOTAL_AMOUNT_KEY   = 'subtotal_amount';
    const ATTACHMENTS_KEY       = 'attachments';

    // Input/output w.r.t cohesive payouts
    const TDS                   = 'tds';
    const CATEGORY_ID           = 'category_id';
    const TDS_AMOUNT            = 'amount';
    const SUBTOTAL_AMOUNT       = 'subtotal_amount';
    const ATTACHMENTS           = 'attachments';
    const ATTACHMENTS_FILE_ID   = 'file_id';
    const ATTACHMENTS_FILE_NAME = 'file_name';
    const ATTACHMENTS_FILE_HASH = 'file_hash';
    const UPDATE_REQUEST        = 'update_request';
    const PAYOUT_IDS            = 'payout_ids';
    const MASTER_MERCHANT_ID    = 'master_merchant_id';
    const MASTER_BALANCE_ID     = 'master_balance_id';

    // Relations
    const PAYOUT = 'payout';

    const PAYOUT_PUBLIC_SIGN = 'pout';

    protected $entity = Table::PAYOUTS_DETAILS;

    protected $primaryKey = self::PAYOUT_ID;

    protected $fillable   = [
        self::PAYOUT_ID,
        self::QUEUE_IF_LOW_BALANCE_FLAG,
        self::TDS_CATEGORY_ID,
        self::ADDITIONAL_INFO,
        self::TAX_PAYMENT_ID,
    ];

    protected $visible = [
        self::PAYOUT_ID,
        self::QUEUE_IF_LOW_BALANCE_FLAG,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::TDS_CATEGORY_ID,
        self::ADDITIONAL_INFO,
        self::TAX_PAYMENT_ID,
    ];

    protected $publicSetters = [
        self::TDS,
        self::ATTACHMENTS,
        self::SUBTOTAL_AMOUNT,
        self::TAX_PAYMENT_ID,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::TDS,
        self::ATTACHMENTS_KEY,
        self::SUBTOTAL_AMOUNT,
        self::TAX_PAYMENT_ID,
    ];


    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::QUEUE_IF_LOW_BALANCE_FLAG => 0,
    ];

    protected $casts = [
        self::QUEUE_IF_LOW_BALANCE_FLAG => 'bool',
    ];

    // ============================= RELATIONS =============================

    public function payout()
    {
        return $this->belongsTo(Payout\Entity::class);
    }

    // ============================= END RELATIONS =============================


    // ============================= GETTERS =============================

    public function getQueueIfLowBalanceFlag()
    {
        return $this->getAttribute(self::QUEUE_IF_LOW_BALANCE_FLAG);
    }

    public function getPayoutId()
    {
        return $this->getAttribute(self::PAYOUT_ID);
    }

    public function getTdsCategoryId()
    {
        return $this->getAttribute(self::TDS_CATEGORY_ID);
    }

    public function getTaxPaymentId()
    {
        return $this->getAttribute(self::TAX_PAYMENT_ID);
    }

    public function getAdditionalInfo()
    {
        return json_decode($this->getAttribute(self::ADDITIONAL_INFO), true);
    }

    public function getTdsDetailsAttribute()
    {
        if (array_key_exists(self::TDS_CATEGORY_ID, $this->attributes) === false)
        {
            return null;
        }

        return [
            self::CATEGORY_ID => $this->attributes[self::TDS_CATEGORY_ID],
            self::TDS_AMOUNT  => $this->getTdsAmountFromAdditionalInfo(),
        ];
    }

    public function getAttachmentsAttribute()
    {
        if (array_key_exists(self::ADDITIONAL_INFO, $this->attributes) === false)
        {
            return null;
        }

        $infoJson = $this->getAdditionalInfo();

        if (empty($infoJson) === false)
        {
            if (array_key_exists(self::ATTACHMENTS_KEY, $infoJson))
            {
                return $infoJson[self::ATTACHMENTS];
            }
        }

        return [];
    }

    public function getSubtotalAmountAttribute()
    {
        if (array_key_exists(self::ADDITIONAL_INFO, $this->attributes) === false)
        {
            return null;
        }

        $infoJson = $this->getAdditionalInfo();

        if (empty($infoJson) === false)
        {
            if (array_key_exists(self::SUBTOTAL_AMOUNT_KEY, $infoJson))
            {
                return $infoJson[self::SUBTOTAL_AMOUNT_KEY];
            }
        }

        return null;
    }

    public function getTdsAmountFromAdditionalInfo()
    {
        if (array_key_exists(self::ADDITIONAL_INFO, $this->attributes) === false)
        {
            return null;
        }

        $infoJson = $this->getAdditionalInfo();

        if (empty($infoJson) === false)
        {
            if (array_key_exists(self::TDS_AMOUNT_KEY, $infoJson))
            {
                return $infoJson[self::TDS_AMOUNT_KEY];
            }
        }

        return null;
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setQueueIfLowBalanceFlag(bool $queueIfLowBalanceFlag)
    {
        $this->setAttribute(self::QUEUE_IF_LOW_BALANCE_FLAG, $queueIfLowBalanceFlag);
    }

    public function setTdsCategoryId(int $tdsCategoryId)
    {
        $this->setAttribute(self::TDS_CATEGORY_ID, $tdsCategoryId);
    }

    public function setAdditionalInfo(string $additionalInfo)
    {
        $this->setAttribute(self::ADDITIONAL_INFO, json_encode($additionalInfo, true));
    }

    public function setTaxPaymentId(string $taxPaymentId)
    {
        $this->setAttribute(self::TAX_PAYMENT_ID, $taxPaymentId);
    }

    // ============================= END SETTERS =============================

    // ============================= PUBLIC SETTERS =============================

    public function setPublicTdsAttribute(array & $attributes)
    {
        // $this->attributes is the db entry
        // $attributes is the final response attributes
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            unset($attributes[self::TDS]);
        }
        else
        {
            if ((array_key_exists(self::TDS_CATEGORY_ID, $this->attributes) === false)
                or (isset($this->attributes[self::TDS_CATEGORY_ID]) === false))
            {
                $attributes[self::TDS] = null;
            }
            else
            {
                $attributes[self::TDS] = $this->getTdsDetailsAttribute();
            }
        }
    }

    public function setPublicAttachmentsAttribute(array & $attributes)
    {
        // $this->attributes is the db entry
        // $attributes is the final response attributes
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            unset($attributes[self::ATTACHMENTS]);
        }
        else
        {
            if ((array_key_exists(self::ADDITIONAL_INFO, $this->attributes) === false)
                or (isset($this->attributes[self::ADDITIONAL_INFO]) === false))
            {
                $attributes[self::ATTACHMENTS] = [];
            }
            else
            {
                $attributes[self::ATTACHMENTS] = $this->getAttachmentsAttribute();
            }
        }
    }

    public function setPublicSubtotalAmountAttribute(array & $attributes)
    {
        // $this->attributes is the db entry
        // $attributes is the final response attributes
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            unset($attributes[self::SUBTOTAL_AMOUNT]);
        }
        else
        {
            if ((array_key_exists(self::ADDITIONAL_INFO, $this->attributes) === false)
                or (isset($this->attributes[self::ADDITIONAL_INFO]) === false))
            {
                $attributes[self::SUBTOTAL_AMOUNT] = null;
            }
            else
            {
                $attributes[self::SUBTOTAL_AMOUNT] = $this->getSubtotalAmountAttribute();
            }
        }
    }

    public function setPublicTaxPaymentIdAttribute(array & $attributes)
    {
        // $this->attributes is the db entry
        // $attributes is the final response attributes
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            unset($attributes[self::TAX_PAYMENT_ID]);
        }
        else
        {
            if ((array_key_exists(self::TAX_PAYMENT_ID, $this->attributes) === true)
                and (isset($this->attributes[self::TAX_PAYMENT_ID]) === true))
            {
                $attributes[self::TAX_PAYMENT_ID] = self::TAX_PAYMENT_PUBLIC_ID_PREFIX . self::TAX_PAYMENT_ID_DELIMITER . $this->attributes[self::TAX_PAYMENT_ID];
            }
            else
            {
                $attributes[self::TAX_PAYMENT_ID] = null;
            }
        }
    }
    // ============================= END PUBLIC SETTERS =============================
}
