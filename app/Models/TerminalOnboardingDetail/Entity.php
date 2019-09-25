<?php


namespace RZP\Models\TerminalOnboardingDetail;

use RZP\Models\Base;
use RZP\Constants;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const TERMINAL_ID               = 'terminal_id';
    const STATUS                    = 'status';
    const RETRY                     = 'retry';
    const ERROR_CODE                = 'error_code';
    const ERROR_DESCRIPTION         = 'error_description';
    const ATTEMPTS                  = 'attempts';           // gateway create attempts
    const VERIFY_BUCKET             = 'verify_bucket';
    const VERIFY_AT                 = 'verify_at';

    protected $public = [
        self::TERMINAL_ID,
        self::STATUS,
        self::RETRY,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::ATTEMPTS,
        self::VERIFY_BUCKET,
        self::VERIFY_AT,
    ];

    protected $fillable = [
        self::TERMINAL_ID,
        self::STATUS,
        self::RETRY,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::ATTEMPTS,
        self::VERIFY_BUCKET,
        self::VERIFY_AT,
    ];

    protected $visible = [
        self::TERMINAL_ID,
        self::STATUS,
        self::RETRY,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::ATTEMPTS,
        self::VERIFY_BUCKET,
        self::VERIFY_AT,
    ];

    protected $defaults = [
        self::STATUS                     => 'created',
        self::ATTEMPTS                   => 0,
        self::VERIFY_BUCKET              => 0,
    ];

    protected $entity = Constants\Entity::TERMINAL_ONBOARDING_DETAIL;

    protected $generateIdOnCreate = true;
    
    public function terminal()
    {
        return $this->belongsTo('RZP\Models\Terminal\Entity');
    }


    // Public Setters
    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setRetry($retry)
    {
        $this->setAttribute(self::RETRY, $retry);
    }

    public function setErrorCode($errorCode)
    {
        $this->setAttribute(self::ERROR_CODE, $errorCode);
    }

    public function setErrorDescription($errorDescription)
    {
        $this->setAttribute(self::ERROR_DESCRIPTION, $errorDescription);
    }

    public function setAttempts($attempts)
    {
        $this->setAttribute(self::ATTEMPTS, $attempts);
    }

    public function setVerifyBucket($verify_bucket)
    {
        $this->setAttribute(self::VERIFY_BUCKET, $verify_bucket);
    }

    public function setVerifyAt($verify_at)
    {
        $this->setAttribute(self::VERIFY_AT, $verify_at);
    }


    // Public Getters
    public function getTerminalId()
    {
        return $this->getAttribute(self::TERMINAL_ID);
    }
    
    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getRetry()
    {
        return $this->getAttribute(self::RETRY);
    }

    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    public function getErrorDescription()
    {
        return $this->getAttribute(self::ERROR_DESCRIPTION);
    }

    public function getAttempts()
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    public function getVerifyBucket()
    {
        return $this->getAttribute(self::VERIFY_BUCKET);
    }

    public function getVerifyAt()
    {
        return $this->getAttribute(self::VERIFY_AT);
    }
}
