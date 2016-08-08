<?php

namespace RZP\Models\Payment\Analytics;

use Crypt;
use RZP\Constants\HttpRequestHeader;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    const ID                            = 'id';
    const PAYMENT_ID                    = 'payment_id';
    const CHECKOUT_ID                   = 'checkout_id';
    const TERMINAL_ID                   = 'terminal_id';
    const STATUS                        = 'status';
    const ATTEMPTS                      = 'attempt';
    const TERMINAL_RESPONSE_TIME        = 'terminal_response_time';
    const STATUS_CODE                   = 'status_code';
    const STATUS_MSG                    = 'status_msg';
    const PAYMENT_TYPE                  = 'payment_type';
    const LIBRARY                       = 'library';
    const BROWSER                       = 'browser';
    const OS                            = 'os';
    const DEVICE                        = 'device';
    const PLATFORM                      = 'platform';
    const IP                            = 'ip';
    const REFERER                       = 'referer';
    const USER_AGENT                    = 'user_agent';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';

    protected $fillable = array(
        self::PAYMENT_ID,
        self::CHECKOUT_ID,
        self::TERMINAL_ID,
        self::STATUS,
        self::ATTEMPTS,
        self::LIBRARY,
        self::BROWSER,
        self::OS,
        self::DEVICE,
        self::PLATFORM,
        self::IP,
        self::REFERER,
        self::TERMINAL_RESPONSE_TIME,
        self::STATUS_CODE,
        self::STATUS_MSG,
        self::PAYMENT_TYPE,
    );

    protected $public = array(
        self::ID,
        self::PAYMENT_ID,
        self::TERMINAL_ID,
        self::STATUS,
        self::ATTEMPTS,
        self::LIBRARY,
        self::BROWSER,
        self::OS,
        self::DEVICE,
        self::PLATFORM,
        self::IP,
        self::REFERER,
        self::TERMINAL_RESPONSE_TIME,
        self::STATUS_CODE,
        self::STATUS_MSG,
        self::PAYMENT_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT
    );

    protected $table = \RZP\Constants\Table::PAYMENT_ANALYTICS;

    protected $generateIdOnCreate = true;

    protected $entity = 'payment_analytics';

    protected static $sign = '';

    protected static $delimiter = '';

    public function getPaymentId()
    {
        return $this->attributes[self::PAYMENT_ID];
    }

    public function getCheckoutId()
    {
        return $this->attributes[self::CHECKOUT_ID];
    }

    public function getTerminalId()
    {
        return $this->attributes[self::TERMINAL_ID];
    }

    public function getStatus()
    {
        return $this->attributes[self::STATUS];
    }

    public function getTerminalResponseTime()
    {
        return $this->attributes[self::TERMINAL_RESPONSE_TIME];
    }

    public function getStatusCode()
    {
        return $this->attributes[self::STATUS_CODE];
    }

    public function getStatusMsg()
    {
        return $this->attributes[self::STATUS_MSG];
    }

    public function getAttemptsAttribute()
    {
        return $this->attributes[self::ATTEMPTS];
    }

    public function getPaymentType()
    {
        return $this->attributes[self::PAYMENT_TYPE];
    }

    public function getLibrary()
    {
        return $this->attributes[self::LIBRARY];
    }

    public function getBrowser()
    {
        return $this->attributes[self::BROWSER];
    }

    public function getOs()
    {
        return $this->attributes[self::OS];
    }

    public function getDevice()
    {
        return $this->attributes[self::DEVICE];
    }

    public function getPlatformAttribute()
    {
        $platform = $this->attributes[self::PLATFORM];

        if ($platform === null)
        {
            return $platform;
        }

        return  Metadata::getStringForPlatformValue($this->attributes[self::PLATFORM]);
    }

    public function getIp()
    {
        return $this->attributes[self::IP];
    }

    public function getReferer()
    {
        return $this->attributes[self::REFERER];
    }

    public function setAttempts($attempts)
    {
        $this->setAttribute(self::ATTEMPTS, $attempts);
    }

    public function setPlatformAttribute($platform)
    {
        $this->attributes[self::PLATFORM] = Metadata::getValueForPlatform($platform);
    }

    public function setCheckoutId($checkoutId)
    {
        $this->setAttribute(self::CHECKOUT_ID, $checkoutId);
    }

    public function setLibrary($library = Metadata::DIRECT)
    {
        $this->setAttribute(self::LIBRARY, $library);
    }

    public function setBrowserAttribute($browser)
    {
        $this->attributes[self::BROWSER] = Metadata::getValueForBrowser($browser);
    }

    public function setOsAttribute($os)
    {
        $this->attributes[self::OS] = Metadata::getValueForOs($os);
    }

    public function setDeviceAttribute($device)
    {
        $this->attributes[self::DEVICE] = Metadata::getValueForDevice($device);
    }

    public function setIpAttribute($ip)
    {
        $this->attributes[self::IP] = $ip;
    }

    public function setUserAgentAttribute($ua)
    {
        $this->attributes[self::USER_AGENT] = $ua;
    }

    public function setRefererAttribute($referer)
    {
        $this->attributes[self::REFERER] = $referer;
    }

    public function buildLog($metadata)
    {
        // Figure out validaiton!

        // $analyticVal = $this->getValidator();
        // s($analyticVal);
        // $analyticVal->validateInput('metadata', $metadata);
        // sd('validated');
        //

        $this->setPaymentAnalyticData($metadata);
    }

    protected function setPaymentAnalyticData($metadata)
    {
        // set checkout_id
        if (isset($metadata[self::CHECKOUT_ID]))
        {
            $this->setCheckoutId($metadata[self::CHECKOUT_ID]);

            // set attempts
            $this->setPaymentAttempts();
        }

        // set library
        if (isset($metadata[self::LIBRARY]))
        {
            $this->setLibrary($metadata[self::LIBRARY]);
        }

        // set platform
        if (isset($metadata[self::PLATFORM]))
        {
            $this->setPlatformAttribute($metadata[self::PLATFORM]);
        }

        $this->setHttpRequestData();
    }

    protected function setPaymentAttempts()
    {
        $checkoutId = $this->getCheckoutId();

        if ($checkoutId === null)
        {
            return;
        }

        $oldPayments = $this->paymentAnalyticRepo->getRecentMerchantPaymentsForCheckoutId($checkoutId);

        $count = $oldPayments->count();

        if (($count > 0) and
            ($count !== $oldPayments->first()->getAttempt()))
        {
            $this->trace->warning(
                TraceCode::PAYMENT_CHECKOUT_INVALID_ID,
                $checkoutId);

            return;
        }

        $attempt = $count + 1;

        $this->setAttempts($attempt);
    }

    protected function setHttpRequestData()
    {
        // get user-agent service
        $app = \App::getFacadeRoot();

        $uAgent = $app['agent'];

        // set browser
        $this->setBrowserAttribute($uAgent->browser());

        // set os
        $this->setOsAttribute($uAgent->platform());

        // set device
        $device = $this->getDeviceValue($uAgent);

        $this->setDeviceAttribute($device);

        // get the HTTP request
        $request = $app['request'];

        // set ip
        $ip = $request->ip();

        $this->setIpAttribute($ip);

        // set referer
        if ($request->header(HttpRequestHeader::REFERER) !== null)
        {
            $this->setRefererAttribute($request->header(HttpRequestHeader::REFERER));
        }

        // set user-agent
        if ($request->header(HttpRequestHeader::USER_AGENT) !== null)
        {
            $this->setUserAgentAttribute($request->header(HttpRequestHeader::USER_AGENT));
        }
    }

    protected function getDeviceValue($uAgent)
    {
        if ($uAgent->isMobile())
        {
            $device = Metadata::MOBILE;
        }
        else if($uAgent->isDesktop())
        {
            $device = Metadata::DESKTOP;
        }
        else if ($uAgent->isTablet())
        {
            $device = Metadata::TABLET;
        }

        return $device;
    }
}
