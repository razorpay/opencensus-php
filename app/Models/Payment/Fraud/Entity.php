<?php

namespace RZP\Models\Payment\Fraud;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Settings;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const PAYMENT_ID                = 'payment_id';
    const ARN                       = 'arn';
    const TYPE                      = 'type';
    const SUB_TYPE                  = 'sub_type';
    const AMOUNT                    = 'amount';
    const CURRENCY                  = 'currency';
    const BASE_AMOUNT               = 'base_amount';
    const REPORTED_TO_RAZORPAY_AT   = 'reported_to_razorpay_at';
    const REPORTED_TO_ISSUER_AT     = 'reported_to_issuer_at';
    const CHARGEBACK_CODE           = 'chargeback_code';
    const IS_ACCOUNT_CLOSED         = 'is_account_closed';
    const REPORTED_BY               = 'reported_by';
    const SOURCE                    = 'source';
    const BATCH_ID                  = 'batch_id';

    const SEND_MAIL                 = 'send_mail';


    protected $entity                   = Constants\Entity::PAYMENT_FRAUD;

    protected $generateIdOnCreate       = true;

    protected $guarded = [];

    // ----------------------- Getters -----------------------------------------

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getBaseAmount()
    {
        return $this->getAttribute(self::BASE_AMOUNT);
    }

    public function getReportedBy()
    {
        return $this->getAttribute(self::REPORTED_BY);
    }

    public function getReportedToRazorpayAt()
    {
        return $this->getAttribute(self::REPORTED_TO_RAZORPAY_AT) ?? $this->getAttribute(self::CREATED_AT);
    }

    public function getReportedToIssuerAt()
    {
        return $this->getAttribute(self::REPORTED_TO_ISSUER_AT) ?? $this->getReportedToRazorpayAt();
    }
}
