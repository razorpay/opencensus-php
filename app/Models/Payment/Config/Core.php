<?php


namespace RZP\Models\Payment\Config;

use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    protected $mutex;

    /**
     * @var BasicAuth
     */
    protected $ba;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->ba = $this->app['basicauth'];
    }

    public function create($input)
    {
        $merchant = $this->merchant;

        $resource = 'config_create_' . $merchant->getId();

        return $this->mutex->acquireAndRelease(
            $resource,
            function() use ($input, $merchant)
            {
                $this->trace->info(TraceCode::CONFIG_CREATE_REQUEST, $input);

                $config = new Entity;

                $config->merchant()->associate($merchant);

                if ((isset($input['type']) === true) and
                    ($input['type'] === Type::LATE_AUTH))
                {
                    $this->checkAndUpdateConfig($input);

                    $this->trackLateAuthConfigEvent(EventCode::PAYMENT_CONFIG_CREATION_INITIATED, $input);

                }

                $config->build($input);

                if ((isset($input['type']) === true) && ($input['type'] === Type::DCC))
                {
                    return $this->validateAndSaveDccConfig($input, $merchant, $config);
                }

                $config = $this->repo->config->transaction(function () use($input, $merchant, $config)
                {
                    //updating the default value of config if already exist
                    if ($this->isDefaultConfig($input))
                    {
                        $defaultConfig = $this->repo->config->fetchDefaultConfigByMerchantIdAndType($merchant->getId(), $input['type']);

                        if ((isset($defaultConfig) === true) and
                            ($input['type'] === Type::LATE_AUTH))
                        {
                            throw new Exception\BadRequestException(
                                ErrorCode::BAD_REQUEST_DEFAULT_LATE_AUTH_CONFIG_PRESENT, null, null,
                                'Default Config is present for the provided merchant');
                        }

                        if ((isset($defaultConfig) === true) and
                            ($input['type'] === Type::PAYMENT_FAILED))
                        {
                            throw new Exception\BadRequestException(
                                ErrorCode::BAD_REQUEST_DEFAULT_PAYMENT_FAILED_CONFIG_PRESENT, null, null,
                                'Default Payment Failed Config is present for the provided merchant');
                        }

                        if (isset($defaultConfig) === true)
                        {
                            if ($input['type'] === Type::LOCALE)
                            {
                                throw new Exception\BadRequestException(
                                    ErrorCode::BAD_REQUEST_DEFAULT_LOCALE_CONFIG_PRESENT, null, null,
                                'Default locale config already present for the merchant');
                            }

                            $defaultConfig->is_default = false;

                            $this->repo->saveOrFail($defaultConfig);
                        }

                        if ((isset($defaultConfig) === true) and ($input['type'] === Type::DCC))
                        {
                            $defaultConfig->is_default = false;
                        }
                    }

                    $this->repo->saveOrFail($config);

                    return $config;
                });

                return $config;
            });
    }

    public function trackLateAuthConfigEvent($eventCode, $input, $source = 'api')
    {
        $properties = $input;

        $properties['user_agent']  = $this->app['request']->header('User-Agent');

        $properties['merchant_id'] = $this->merchant->getId();

        $properties['source'] = $source;

        if ($this->ba->isProxyAuth() === true)
        {
            $properties['source'] = 'merchant_dashboard';
        }
        elseif ($this->ba->isAdminAuth() === true)
        {
            $properties['source'] = 'admin_dashboard';
        }

        $this->app['diag']->trackPaymentConfigEvent($eventCode, null, null, $properties);
    }

    public function checkAndUpdateConfig(& $input)
    {
        if ((isset($input['config']['capture']) === true) and
            ($input['config']['capture'] === 'automatic'))
        {
            if ((isset($input['config']['capture_options']['automatic_expiry_period']) === false) and
                (isset($input['config']['capture_options']['manual_expiry_period']) === false))
            {
                $input['config']['capture_options']['automatic_expiry_period'] = 7200;

                $input['config']['capture_options']['manual_expiry_period'] = null;

                return;
            }

            if (isset($input['config']['capture_options']['automatic_expiry_period']) === false)
            {
                $input['config']['capture_options']['automatic_expiry_period'] = 20;
            }

            if (isset($input['config']['capture_options']['manual_expiry_period']) === false)
            {
                $input['config']['capture_options']['manual_expiry_period'] = null;
            }
        }

        if ((isset($input['config']['capture']) === true) and
            ($input['config']['capture'] === 'manual'))
        {
            if (isset($input['config']['capture_options']['automatic_expiry_period']) === false)
            {
                $input['config']['capture_options']['automatic_expiry_period'] = null;
            }
        }
    }

    private function updateCheckoutConfig($config,$input, $id, $type)
    {
        $config->edit($input);

        if ((isset($input['is_default']) === true) and $this->isDefaultConfig($input))
        {
            // find if any default config exist
            $defaultConfig = $this->repo->config->fetchDefaultConfigByMerchantIdAndType($this->merchant->getId(), $type);

            if ((isset($defaultConfig) === true) and
                $id !== $defaultConfig->getId())
            {
                $defaultConfig->is_default = false;

                $this->repo->saveOrFail($defaultConfig);
            }
        }

        $this->repo->saveOrFail($config);
    }

    public function getFormattedConfigForCheckout($configId, $merchantId, & $data)
    {
        $config = null;

        if (isset($configId) === false)
        {
            $config = $this->repo->config->fetchDefaultConfigByMerchantIdAndType($merchantId, 'checkout');
        }
        else {
            $config = $this->repo->config->findByPublicIdAndMerchant($configId, $this->merchant);
        }

        if (isset($config) === true)
        {
            $data['checkout_config'] = json_decode($config->config, true);
        }
    }

    private function isDefaultConfig($input)
    {
        if (($input['is_default'] === true) or (strval($input['is_default']) === '1'))
        {
            return true;
        }

        return false;
    }

    private function updateLocaleConfig($config, $input)
    {
        $config->setConfig(json_encode($input['config']));

        $this->repo->saveOrFail($config);

        return $config;

    }

    private function updatePaymentFailedConfig($config, $input)
    {
        $config->setConfig(json_encode($input['config']));

        $this->repo->saveOrFail($config);

        return $config;
    }

    private function updateDccConfig($config, $input)
    {
        $config->setConfig(json_encode($input['config']));

        $this->repo->saveOrFail($config);

        return $config;
    }

    public function update($input)
    {
        $this->trace->info(TraceCode::CONFIG_UPDATE_REQUEST, $input);

        //find the config with Id, merchant, and type.
        $merchant = $this->merchant;

        $resource = 'config_update_' . $merchant->getId();

        return $this->mutex->acquireAndRelease(
            $resource,
            function() use ($input, $merchant)
            {
                $id = $input['id'];

                Entity::verifyIdAndStripSign($id);

                $type = $input['type'];

                $config = $this->repo->config->findByPublicIdAndMerchantAndType($id, $this->merchant->getId(), $type);

                if (isset($config) === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_INVALID_CONFIG_ID, null, null,
                        'Config is not present for the provided ID');
                }
                else
                {
                    $config = $this->repo->transaction(function () use($input, $merchant, $config, $id, $type)
                    {
                        if ($type === Type::CHECKOUT)
                        {
                            $this->updateCheckoutConfig($config, $input, $id, $type);
                        }

                        if ($type === Type::LOCALE)
                        {
                            $this->updateLocaleConfig($config, $input);
                        }

                        if ($type === Type::DCC)
                        {
                            $this->updateDccConfig($config, $input);
                        }

                        if ($type === Type::PAYMENT_FAILED)
                        {
                            $this->updatePaymentFailedConfig($config, $input);
                        }

                        return $config;
                    });

                    return  $config;
                }
            });
    }

    public function updateLateAuthConfig(array $input)
    {
        $this->trace->info(TraceCode::CONFIG_UPDATE_REQUEST, $input);

        $merchant = $this->merchant;

        $resource = 'config_update_' . $merchant->getId();

        $this->trackLateAuthConfigEvent(EventCode::PAYMENT_CONFIG_UPDATION_INITIATED, $input);

        if ((isset($input['type']) === true) and
            ($input['type'] === Type::LATE_AUTH))
        {
            $this->checkAndUpdateConfig($input);
        }

        return $this->mutex->acquireAndRelease(
            $resource,
            function() use ($input, $merchant)
            {
                $type = $input['type'];

                $configEntity = $this->repo->config->fetchDefaultConfigByMerchantIdAndType($merchant->getId(), $type);

                if (isset($configEntity) === true)
                {
                    $configEntity->setConfig(json_encode($input['config']));

                    $this->repo->saveOrFail($configEntity);
                }
                else
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_CONFIG_NOT_FOUND, null, null,
                        'Config is not present for the provided merchant');

                }

                return  $configEntity;
            });
    }

    public function withMerchant(Merchant\Entity $merchant)
    {
        $this->merchant = $merchant;

        return $this;
    }

    /**
     * @param $input
     * @param Merchant\Entity $merchant
     * @param $config
     * @return $config
     * @throws Exception\BadRequestException
     */
    private function validateAndSaveDccConfig($input, Merchant\Entity $merchant, $config)
    {
            $configEntity = $this->repo->config->fetchConfigByMerchantIdAndType($merchant->getId(), $input['type'])->first();

            if ((isset($configEntity) === true))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_DCC_CONFIG_PRESENT, null, null,
                    "Dcc Config is already present for the provided merchant");
            }

            $this->repo->saveOrFail($config);

            return $config;
    }

    public function getPaymentFailedConfig($merchantId)
    {
        $config = $this->repo->config->fetchDefaultConfigByMerchantIdAndType($merchantId, Type::PAYMENT_FAILED);

        if(isset($config) === true)
        {
            return json_decode($config->config, true);
        }

        return false;
    }

    public function validateAndSaveCustomerFeeConfig($inputConfig)
    {

        $convenienceFeeConfig = [];

        if(isset($inputConfig['rules']) === false)
        {
            return $convenienceFeeConfig;
        }

        if(isset($inputConfig['message']) === true and
            strlen($inputConfig['message']) > 0)
        {
            $convenienceFeeConfig['message'] = $inputConfig['message'];
        }

        $convenienceFeeConfig['label'] = 'Convenience Fee';

        if(isset($inputConfig['label']) === true and
            strlen($inputConfig['label']) > 0 )
        {
            $convenienceFeeConfig['label'] =  $inputConfig['label'];
        }


        $convenienceFeeConfig['rules'] = [];

        foreach ($inputConfig['rules'] as $rule)
        {
            $this->validateAndAddConfigRules($convenienceFeeConfig['rules'], $rule);
        }

        return $convenienceFeeConfig;
    }

    public function validateAndAddConfigRules(array & $convenienceFeeConfig, $rule)
    {

        if(isset($rule['fee']['percentage_value']) === true)
        {
            $rule['fee']['percentage_value'] = floatval($rule['fee']['percentage_value']);
        }


        if($rule['method'] === 'card' and
            isset($rule['card.type']) === true)
        {
            $cardTypes = $rule['card.type'];

            foreach ($cardTypes as $cardType)
            {
                if(in_array($cardType, Entity::CARD_TYPES) === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG,
                        'convenience_fee_config.rules.card.type',
                        null,
                        "{$cardType} is not a valid card type."
                    );
                }
                else if(isset($convenienceFeeConfig['card']['type'][$cardType]) === true)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG,
                        'convenience_fee_config.rules.card.type',
                        null,
                        "Duplicate configuration for {$cardType} "
                    );
                }
                $convenienceFeeConfig['card']['type'][$cardType]['fee'] = $rule['fee'];
            }
        }
        else if($rule['method'] === 'card')
        {
            if(isset($convenienceFeeConfig[$rule['method']]) === true and
                isset($convenienceFeeConfig['card']['fee']) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG,
                    'convenience_fee_config.rules.card',
                    null,
                    "Duplicate configuration for card"
                );
            }
            $convenienceFeeConfig['card']['fee'] = $rule['fee'];
        }
        else
        {
            if(isset($convenienceFeeConfig[$rule['method']]) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG,
                    'convenience_fee_config.rules.card',
                    null,
                    "Duplicate configuration for {$rule['method']}"
                );
            }
            $convenienceFeeConfig[$rule['method']]['fee'] = $rule['fee'];
        }
    }

    public function getConvenienceFeeConfigForCheckout($order) : array
    {

        $paymentConfig = $this->repo->config->findOrFail($order->getFeeConfigId());

        $feeConfig = $paymentConfig->getFormattedConfig();

        if(isset($feeConfig['rules']) === false or
            empty($feeConfig['rules']))
        {
            return [];
        }

        $convenienceFeeConfig = [];

        $convenienceFeeConfig['label_on_checkout'] = $feeConfig['label'];

        if(isset($feeConfig['message']) === true )
        {
            $convenienceFeeConfig['checkout_message'] = $feeConfig['message'];
        }

        foreach($feeConfig['rules'] as $method => $config)
        {
            if($method === 'card')
            {
                if(isset($config['type']) === true)
                {
                    foreach($config['type'] as $cardType => $typeConfig)
                    {
                        if($typeConfig['fee']['payee'] === 'customer' and
                            isset($typeConfig['fee']['flat_value']) === true)
                        {
                            $convenienceFeeConfig['methods'][$method]['type'][$cardType]['amount'] = $typeConfig['fee']['flat_value'];
                        }
                        else
                        {
                            $convenienceFeeConfig['methods'][$method]['type'][$cardType] = [];
                        }
                    }
                }

                if(isset($config['fee']) === true and
                    $config['fee']['payee'] === 'customer' and
                    isset($config['fee']['flat_value']) === true)
                {
                    $convenienceFeeConfig['methods']['card']['amount'] = $config['fee']['flat_value'];
                }
            }
            else
            {
                if($config['fee']['payee'] === 'customer' and
                    isset($config['fee']['flat_value']) === true)
                {
                    $convenienceFeeConfig['methods'][$method]['amount'] = $config['fee']['flat_value'];
                }
            }
        }

        return $convenienceFeeConfig;

    }
}
