<?php

namespace RZP\Models\Merchant\Product\Config;

use App;
use RZP\Base;
use RZP\Exception;
use Lib\PhoneBook;
use Razorpay\IFSC\IFSC;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Product\Util;
use RZP\Models\Merchant\Product\TncMap;


class Validator extends Base\Validator
{
    const INVALID_IFSC_CODE_MESSAGE = 'Invalid IFSC Code';

    protected static $pgRules             = [
        Util\Constants::SETTLEMENTS     => 'sometimes|array',
        Util\Constants::CHECKOUT        => 'sometimes|array',
        Util\Constants::PAYMENT_CAPTURE => 'sometimes|array',
        Util\Constants::NOTIFICATIONS   => 'sometimes|array',
        Util\Constants::REFUND          => 'sometimes|array',
        Util\Constants::PAYMENT_METHODS => 'sometimes|array',
        Util\Constants::TNC_ACCEPTED    => 'sometimes|boolean|in:1',
        Util\Constants::OTP             => 'sometimes|array',
        Util\Constants::IP              => 'sometimes|ip',
    ];

    protected static $routeProductRules   = [
        Util\Constants::SETTLEMENTS     => 'sometimes|array',
        Util\Constants::TNC_ACCEPTED    => 'sometimes|boolean|in:1',
    ];

    protected static $notificationsRules  = [
        Util\Constants::WHATSAPP => 'sometimes|boolean',
        Util\Constants::SMS      => 'sometimes|boolean',
        Util\Constants::EMAIL    => 'sometimes|array',
    ];

    protected static $checkoutRules = [
        Util\Constants::THEME_COLOR    => 'sometimes|regex:(^#[0-9a-fA-F]{6}$)',
        Util\Constants::FLASH_CHECKOUT => 'sometimes|boolean',
        Util\Constants::LOGO           => 'sometimes|max:2000',
    ];

    protected static $refundRules = [
        Merchant\Entity::DEFAULT_REFUND_SPEED => 'sometimes|filled|string|in:normal,optimum'
    ];

    protected static $paymentCaptureRules = [
        Util\Constants::MODE                    => 'required|string|in:automatic,manual',
        Util\Constants::AUTOMATIC_EXPIRY_PERIOD => 'sometimes|integer',
        Util\Constants::MANUAL_EXPIRY_PERIOD    => 'sometimes|integer',
        Util\Constants::REFUND_SPEED            => 'required|string|in:normal'
    ];

    protected static $settlementsRules = [
        Util\Constants::BENEFICIARY_NAME => 'sometimes|string|min:4|max:120',
        Util\Constants::IFSC_CODE        => 'sometimes|alpha_num|max:11|custom',
        Util\Constants::ACCOUNT_NUMBER   => 'sometimes|regex:/^[a-zA-Z0-9]+$/|between:5,20|custom',
    ];

    protected static $otpRules = [
        Util\Constants::CONTACT_MOBILE             => 'required|string',
        Util\Constants::REFERENCE_NUMBER           => 'sometimes|string',
        Util\Constants::OTP_SUBMISSION_TIMESTAMP   => 'sometimes|string',
        Util\Constants::OTP_VERIFICATION_TIMESTAMP => 'sometimes|string',
    ];

    protected static $pgValidators = [
        'notifications',
        'payment_capture',
        'settlements',
        'checkout',
        'refund',
        'payment_methods',
        'otp',
        'tnc_input_check'
    ];

    public function __construct($entity = null)
    {
        parent::__construct($entity);

        $app = App::getFacadeRoot();

        $this->merchant = $app['basicauth']->getMerchant();
    }

    public function validateAccountNumber($attribute, $bankAccountNumber)
    {
        if (\RZP\Models\BankAccount\Validator::isBlacklistedAccountNumber($bankAccountNumber))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_BANK_ACCOUNT);
        }
    }

    public function validateIfscCode($attribute, $value)
    {
        if (IFSC::validate($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_IFSC_CODE_MESSAGE, Util\Constants::IFSC_CODE);
        }
    }

    public function validateRefund(array $input)
    {
        if(isset($input[Util\Constants::REFUND]) === false)
        {
            return;
        }

        $refundInput = $input[Util\Constants::REFUND];

        $this->validateInput('refund', $refundInput);
    }

    protected function validateNotifications(array $input)
    {
        if(isset($input[Util\Constants::NOTIFICATIONS]) === false)
        {
            return;
        }

        $notificationInput = $input[Util\Constants::NOTIFICATIONS];

        $this->validateInput('notifications', $notificationInput);
    }

    protected function validateCheckout(array $input)
    {
        if(isset($input[Util\Constants::CHECKOUT]) === false)
        {
            return;
        }

        $checkoutInput = $input[Util\Constants::CHECKOUT];

        $this->validateInput('checkout', $checkoutInput);
    }

    protected function validatePaymentCapture(array $input)
    {
        if(isset($input[Util\Constants::PAYMENT_CAPTURE]) === false)
        {
            return;
        }

        $paymentCaptureInput = $input[Util\Constants::PAYMENT_CAPTURE];

        $this->validateInput('payment_capture', $paymentCaptureInput);
    }

    protected function validateSettlements(array $input)
    {
        if(isset($input[Util\Constants::SETTLEMENTS]) === false)
        {
            return;
        }

        $settlementsInput = $input[Util\Constants::SETTLEMENTS];

        $this->validateInput('settlements', $settlementsInput);
    }

    protected function validatePaymentMethods(array $input)
    {
        if(isset($input[Util\Constants::PAYMENT_METHODS]) === false)
        {
            return;
        }

        $isRazorXExperimentEnabled = \Request::all()[Util\Constants::CONFIG_UPDATE_FLOW_ENABLED] ?? false;

        if ($isRazorXExperimentEnabled === false)
        {
            (new PaymentMethodsValidator())->validateInput('paymentMethods', $input[Util\Constants::PAYMENT_METHODS]);
        } else
        {
            (new PaymentMethodsValidator())->validateInput('paymentMethodUpdate', $input[Util\Constants::PAYMENT_METHODS]);
        }
    }

    public function validateOtp(array $input)
    {

        if (isset($input[Util\Constants::OTP]) === true and $this->merchant->isNoDocOnboardingEnabled() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_OTP_NOT_REQUIRED);
        }

        if (isset($input[Util\Constants::OTP]) === true)
        {
            $this->validateInput('otp', $input[Util\Constants::OTP]);
        }

        if (empty($input[Util\Constants::OTP][Util\Constants::CONTACT_MOBILE]) === false)
        {
            $merchant_detail = $this->merchant->merchantDetail()->first();

            $otpCore =  (new Util\OtpRequestHandler());

            $formattedContactNumber = $otpCore->formatContactNumber($input[Util\Constants::OTP][Util\Constants::CONTACT_MOBILE], $this->merchant);

            if ($merchant_detail->getContactMobile() != $formattedContactNumber)
            {
                throw new  Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_OTP_VERIFICATION_LOG);
            }
        }
    }

    public function validateTncInputCheck($input)
    {
        $app = App::getFacadeRoot();

        $partnerId = $app['basicauth']->getPartnerMerchantId();

        $isExpEnabled = (new TncMap\Acceptance\Core())->isPartnerExcludedFromProvidingSubmerchantIp($partnerId);

        //for no doc merchants and non whitelisted partner's submerchants ip and tnc are required to be passed together
        if($this->merchant->isNoDocOnboardingEnabled() === true or $isExpEnabled === false)
        {
            if((isset($input[Util\Constants::IP]) === true and isset($input[Util\Constants::TNC_ACCEPTED]) === false) or (isset($input[Util\Constants::IP]) === false and isset($input[Util\Constants::TNC_ACCEPTED]) === true))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TNC_ACCEPTANCE_AND_IP_NOT_TOGETHER);
            }
        }
        else
        {
            if(isset($input[Util\Constants::IP]) === true and isset($input[Util\Constants::TNC_ACCEPTED]) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TNC_ACCEPTANCE_AND_IP_NOT_TOGETHER);
            }
        }
    }

}
