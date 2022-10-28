<?php

namespace RZP\Models\Merchant\Product\Util;

use App;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Trace\TraceCode;

class PaymentGatewayRequestHandler
{
    const REQUEST_CONFIG_KEYS = [
        Constants::PAYMENT_CAPTURE => Constants::PAYMENT_CONFIG,
        Constants::NOTIFICATIONS   => Constants::NOTIFICATIONS,
        Constants::CHECKOUT        => Constants::ACCOUNT_CONFIG,
        Constants::REFUND          => Constants::REFUND,
        Constants::SETTLEMENTS     => Constants::BANK_DETAILS,
        Constants::PAYMENT_METHODS => Constants::PAYMENT_METHODS,
        Constants::CONFIGURATION   => Constants::CONFIGURATION,
        Constants::TNC_ACCEPTED    => Constants::TNC_ACCEPTED,
        Constants::OTP             => Constants::OTP,
        Constants::IP              => Constants::IP
    ];

    const CHECKOUT_FIELD_MAPPING = [
        Constants::LOGO                       => Merchant\Entity::LOGO_URL,
        Constants::THEME_COLOR                => Merchant\Entity::BRAND_COLOR,
        Merchant\Entity::MAX_PAYMENT_AMOUNT   => Merchant\Entity::MAX_PAYMENT_AMOUNT,
        Merchant\Entity::DEFAULT_REFUND_SPEED => Merchant\Entity::DEFAULT_REFUND_SPEED
    ];

    public static function handleRequest(array $request)
    {
        $transformedRequest = [];

        foreach ($request as $configKey => $configValue)
        {
            $function = 'transform' . studly_case($configKey);

            $transformedRequest[self::REQUEST_CONFIG_KEYS[$configKey]] = self::$function($configValue);
        }

        return $transformedRequest;
    }

    private static function transformPaymentCapture(array $configValue): array
    {
        $request = [];

        $request[Constants::TYPE] = Constants::LATE_AUTH;

        $request[Constants::CONFIG] = [];

        $configOptions = [
            Constants::REFUND_SPEED => $configValue[Constants::REFUND_SPEED]
        ];

        if ($configValue[Constants::MODE] === Constants::MANUAL)
        {
            $request[Constants::CONFIG][Constants::CAPTURE] = $configValue[Constants::MODE];

            if (isset($configValue[Constants::MANUAL_EXPIRY_PERIOD]) === true)
            {
                $configOptions[Constants::MANUAL_EXPIRY_PERIOD] = $configValue[Constants::MANUAL_EXPIRY_PERIOD];
            }
        }

        if ($configValue[Constants::MODE] === Constants::AUTOMATIC)
        {
            $request['config']['capture'] = $configValue[Constants::MODE];

            if (isset($configValue[Constants::MANUAL_EXPIRY_PERIOD]) === true)
            {
                $configOptions[Constants::MANUAL_EXPIRY_PERIOD] = $configValue[Constants::MANUAL_EXPIRY_PERIOD];
            }

            if (isset($configValue[Constants::AUTOMATIC_EXPIRY_PERIOD]) === true)
            {
                $configOptions[Constants::AUTOMATIC_EXPIRY_PERIOD] = $configValue[Constants::AUTOMATIC_EXPIRY_PERIOD];
            }
        }

        $request['config']['capture_options'] = $configOptions;

        return $request;
    }

    private static function transformNotifications(array $configValue): array
    {
        $request = [];

        if (isset($configValue[Constants::SMS]) === true)
        {
            $request[Constants::SMS] = ['enable' => $configValue['sms']];
        }

        if (isset($configValue[Constants::WHATSAPP]) === true)
        {
            $request[Constants::WHATSAPP] = $configValue[Constants::WHATSAPP];
        }

        if (isset($configValue[Constants::EMAIL]) === true)
        {
            $request[Merchant\Entity::TRANSACTION_REPORT_EMAIL] = $configValue[Constants::EMAIL];
        }

        return $request;
    }

    private static function transformCheckout(array $input): array
    {
        $transformedInput = [];

        if (isset($input[Constants::FLASH_CHECKOUT]) === true)
        {
            $flashCheckoutPayload = [];

            $flashCheckoutPayload[Constants::FEATURES] = [];

            $flashCheckoutPayload[Constants::FEATURES][Constants::NOFLASHCHECKOUT] = !$input[Constants::FLASH_CHECKOUT];

            $transformedInput[Constants::FLASH_CHECKOUT] = $flashCheckoutPayload;
        }

        foreach (self::CHECKOUT_FIELD_MAPPING as $field => $actualField)
        {
            if (isset($input[$field]) === true)
            {
                $transformedInput[$actualField] = $input[$field];
            }
        }

        if (isset($transformedInput[Merchant\Entity::BRAND_COLOR]) === true)
        {
            $color = $transformedInput[Merchant\Entity::BRAND_COLOR];

            $transformedInput[Merchant\Entity::BRAND_COLOR] = substr($color, 1);
        }

        return $transformedInput;
    }

    private static function transformSettlements(array $input): array
    {
        $request = [];

        if (isset($input[Constants::ACCOUNT_NUMBER]) === true)
        {
            $app = App::getFacadeRoot();

            try
            {
                if(is_int($input[Constants::ACCOUNT_NUMBER]) === true)
                {
                    $input[Constants::ACCOUNT_NUMBER] = strval($input[Constants::ACCOUNT_NUMBER]);

                    $app['trace']->info(TraceCode::MERCHANT_BANK_DETAIL_PROVIDED_AS_INTEGER,
                        [
                            'message'                => 'bank account number was of integer type',
                        ]);
                }
            }
            catch (\Exception $e)
            {
                $app['trace']->traceException($e);
            }
            $request[Detail\Entity::BANK_ACCOUNT_NUMBER] = $input[Constants::ACCOUNT_NUMBER];
        }

        if (isset($input[Constants::IFSC_CODE]) === true)
        {
            $request[Detail\Entity::BANK_BRANCH_IFSC] = $input[Constants::IFSC_CODE];
        }

        if (isset($input[Constants::BENEFICIARY_NAME]) === true)
        {
            $request[Detail\Entity::BANK_ACCOUNT_NAME] = $input[Constants::BENEFICIARY_NAME];
        }

        return $request;
    }

    private static function transformConfiguration(array $request): array
    {
        $transformedRequest = [];

        foreach ($request as $configKey => $configValue)
        {
            $function = 'transform' . studly_case($configKey);

            $transformedRequest[$configKey] = self::$function($configValue);
        }

        return $transformedRequest;
    }

    private static function transformPaymentMethods(array $input): array
    {
        return $input;
    }

    private static function transformRefund(array $input): array
    {
        return $input;
    }

    private static function getTransformedFeaturePayload(array $input): array
    {
        $featurePayload = [];

        $featurePayload[Constants::FEATURES] = [];

        foreach ($input as $featureData => $value)
        {
            $featurePayload[Constants::FEATURES][$featureData] = $value;
        }

        return $featurePayload;
    }

    private static function transformTncAccepted(bool $input): bool
    {
        return $input;
    }

    private static function transformOtp(array $input): array
    {
        return $input;
    }

    private static function transformIp(string $input): string
    {
        return $input;
    }
}
