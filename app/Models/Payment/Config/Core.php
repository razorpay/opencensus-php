<?php


namespace RZP\Models\Payment\Config;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
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

                $config->build($input);

                $config = $this->repo->config->transaction(function () use($input, $merchant, $config)
                {
                    //updating the default value of config if already exist
                    if ($this->isDefaultConfig($input))
                    {
                        $defaultConfig = $this->repo->config->fetchDefaultConfigByMerchantIdAndType($merchant->getId(), $input['type']);

                        if (isset($defaultConfig) === true)
                        {
                            $defaultConfig->is_default = false;

                            $this->repo->saveOrFail($defaultConfig);
                        }
                    }

                    $this->repo->saveOrFail($config);

                    return $config;
                });

                return $config;
            });
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

                        return $config;
                    });

                    return  $config;
                }
            });
    }

    public function getFormattedConfigForCheckout($configId, $merchantId, & $data)
    {
        $config = null;

        if (isset($configId) === false) {
            $config = $this->repo->config->fetchDefaultConfigByMerchantIdAndType($merchantId, 'checkout');
        } else {
            $config = $this->repo->config->findByPublicId($configId);
        }

        if (isset($config) === true) {
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
}
