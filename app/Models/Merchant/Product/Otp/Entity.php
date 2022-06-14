<?php

namespace RZP\Models\Merchant\Product\Otp;

use RZP\Models\Base;
use RZP\Models\Merchant\Detail;

/**
 * Class Entity
 *
 * @package RZP\Models\Merchant\Otp
 *
 * @property Detail\Entity $merchantDetail
 *
 */
class Entity extends Base\PublicEntity
{

    const ID                         = 'id';
    const MERCHANT_ID                = 'merchant_id';
    const EXTERNAL_REFERENCE_NUMBER  = 'external_reference_number';
    const OTP_SUBMISSION_TIMESTAMP   = 'otp_submission_timestamp';
    const OTP_VERIFICATION_TIMESTAMP = 'otp_verification_timestamp';
    const RAZORPAY_VERIFIED          = 'razorpay_verified';
    const CONTACT_MOBILE             = 'contact_mobile';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $generateIdOnCreate = true;

    protected $primaryKey         = self::ID;

    protected $entity             = 'merchant_otp_verification_logs';

    protected $fillable           = [
        self::MERCHANT_ID,
        self::EXTERNAL_REFERENCE_NUMBER,
        self::OTP_SUBMISSION_TIMESTAMP,
        self::OTP_VERIFICATION_TIMESTAMP,
        self::RAZORPAY_VERIFIED,
        self::CONTACT_MOBILE
    ];

    protected $public             = [
        self::ID,
        self::EXTERNAL_REFERENCE_NUMBER,
        self::OTP_SUBMISSION_TIMESTAMP,
        self::OTP_VERIFICATION_TIMESTAMP,
        self::RAZORPAY_VERIFIED,
        self::CONTACT_MOBILE
    ];


    public function getContactMobile()
    {
        return $this->getAttribute(self::CONTACT_MOBILE);
    }

    public function getExternalReferenceNumber()
    {
        return $this->getAttribute(self::EXTERNAL_REFERENCE_NUMBER);
    }

    public function getOtpSubmissionTimeStamp()
    {
        return $this->getAttribute(self::OTP_SUBMISSION_TIMESTAMP);
    }

    public function getOtpVerificationTimestamp()
    {
        return $this->getAttribute(self::OTP_VERIFICATION_TIMESTAMP);
    }

    public function merchantDetail()
    {
        return $this->belongsTo('RZP\Models\Merchant\Detail\Entity', self::MERCHANT_ID, self::MERCHANT_ID);
    }

}
