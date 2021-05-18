<?php

namespace RZP\Models\Merchant\Product\Util;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;

class PaymentGatewayRequestHelper
{
    const REQUEST_CONFIG_KEYS = [
        Constants::PAYMENT_CAPTURE => Constants::PAYMENT_CONFIG,
        Constants::NOTIFICATIONS   => Constants::NOTIFICATIONS,
        Constants::CHECKOUT        => Constants::ACCOUNT_CONFIG,
        Constants::SETTLEMENTS     => Constants::BANK_DETAILS,
        Constants::METHODS         => Constants::PAYMENT_METHODS,
        Constants::CONFIGURATION   => Constants::CONFIGURATION
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

        $request['type'] = 'late_auth';

        $request['config'] = [];

        $configOptions = [
            'refund_speed' => $configValue['refund_speed']
        ];

        if ($configValue['mode'] === 'manual')
        {
            $request['config']['capture'] = $configValue['mode'];

            $configOptions['manual_expiry_period'] = $configValue['manual_expiry_period'];
        }
        if ($configValue['mode'] === 'automatic')
        {
            $request['config']['capture'] = $configValue['mode'];

            $configOptions['manual_expiry_period']    = $configValue['manual_expiry_period'];

            $configOptions['automatic_expiry_period'] = $configValue['automatic_expiry_period'];
        }

        $request['config']['capture_options'] = $configOptions;

        return $request;
    }

    private static function transformNotifications(array $configValue): array
    {
        $request = [];

        if(isset($configValue['sms']) === true)
        {
            $request['sms'] = ['enable' => $configValue['sms']];
        }

        if(isset($configValue['whatsapp']) === true)
        {
            $request['whatsapp'] = $configValue['whatsapp'];
        }

        return $request;
    }

    private static function transformCheckout(array $input): array
    {
        if(isset($input['flash_checkout']) === true)
        {
            $flashCheckoutPayload = [];

            $flashCheckoutPayload['features'] = [];

            $flashCheckoutPayload['features']['noflashcheckout'] = ! $input['flash_checkout'];

            $input['flash_checkout'] = $flashCheckoutPayload;
        }
        if(isset($input[Merchant\Entity::BRAND_COLOR]) === true)
        {
            $color = $input[Merchant\Entity::BRAND_COLOR];

            $input[Merchant\Entity::BRAND_COLOR] = substr($color, 1);
        }

        return $input;
    }

    private static function transformSettlements(array $input): array
    {
        $request = [];

        if(isset($input['account_number']) === true)
        {
            $request[Detail\Entity::BANK_ACCOUNT_NUMBER] = $input['account_number'];
        }

        if(isset($input['ifsc_code']) === true)
        {
            $request[Detail\Entity::BANK_BRANCH_IFSC] = $input['ifsc_code'];
        }

        if(isset($input['name']) === true)
        {
            $request[Detail\Entity::BANK_ACCOUNT_NAME] = $input['name'];
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

    private static function transformMethods(array $input): array
    {
        //$transformedFeatures = [];
        //
        //$transformedInput = [];
        //
        //foreach ($input as $method => $config)
        //{
        //    if(isset($config[Constants::FEATURES]) === true)
        //    {
        //        $featureArray = $config[Constants::FEATURES];
        //
        //        $transformedFeatures = array_merge($transformedFeatures, self::getTransformedFeaturePayload($featureArray));
        //    }
        //}
        //
        //$transformedInput[Constants::FEATURES] = $transformedFeatures;

        return $input;
    }

    private static function getTransformedFeaturePayload(array $input): array
    {
        $featurePayload = [];

        $featurePayload[Constants::FEATURES] = [];

        foreach($input as $featureData => $value)
        {
            $featurePayload[Constants::FEATURES][$featureData] = $value;
        }

        return $featurePayload;
    }
}
