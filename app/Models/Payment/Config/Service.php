<?php


namespace RZP\Models\Payment\Config;

use RZP\Models\Base;


class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    /*
     * Function to fetch the config.
     * Parameters :- type of config
     * Return list of config
     *
     * */
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
        $config = $this->core->create($input);

        return $config->toArrayPublic();
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
        $config = $this->core->update($input);

        return $config->toArrayPublic();
    }
}
