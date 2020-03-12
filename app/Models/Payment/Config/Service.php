<?php


namespace RZP\Models\Payment\Config;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use function Clue\StreamFilter\fun;


class Service extends Base\Service
{
    /*
     * Function to fetch the config.
     * Parameters :- type of config
     * Return list of config
     *
     * */
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function fetch(string $type = 'checkout')
    {
        $configs = $this->repo->config->fetchConfigByMerchantIdAndType($this->merchant->getId(), $type);

        return $configs->toArrayPublic();
    }

    /*
     * Function to create config
     * Parameters :- config data and type of config
     * Return :- Created config
     *
     * */
    public function create(array $input)
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
                    if ($input['default'] === true or strval($input['default']) === '1')
                    {
                        $defaultConfig = $this->repo->config->fetchDefaultConfigByMerchantIdAndType($merchant->getId(), $input['type']);

                        if (isset($defaultConfig) === true)
                        {
                            $defaultConfig->default = false;

                            $this->repo->saveOrFail($defaultConfig);
                        }
                    }

                    $this->repo->saveOrFail($config);

                    return $config;
                });

                return $config->toArrayPublic();
            });
    }

    /*
     * Function to update the config
     * Params :- id, data tto update, type of config
     * Return :- Updated config
     *
     */
    public function update(array $input)
    {
        (new Validator())->validateInput('edit', $input);

        if ($input['type'] === 'checkout')
        {
            return $this->updateCheckoutConfig($input);
        }
    }

    private function updateCheckoutConfig(array $input)
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

                if (isset($config))
                {
                    $config = $this->repo->transaction(function () use($input, $merchant, $config, $id, $type)
                    {
                        $config->edit($input);

                        if (isset($input['default']) and ($input['default'] === true or strval($input['default']) === '1'))
                        {
                            // find if any default config exist
                            $defaultConfig = $this->repo->config->fetchDefaultConfigByMerchantIdAndType($this->merchant->getId(), $type);

                            if (isset($defaultConfig) === true and $id !== $defaultConfig->getId())
                            {
                                $defaultConfig->default = false;

                                $this->repo->saveOrFail($defaultConfig);
                            }
                        }

                        $this->repo->saveOrFail($config);

                        return $config;
                    });

                    return  $config->toArrayPublic();
                }
                else {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_INVALID_CONFIG_ID, null, null,
                        'Config is not present for the provided ID');

                }
            });

    }
}
