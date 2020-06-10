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

    /**
     * @param string           $type
     *
     *
     * @return array
     */
    public function fetch(string $type = 'checkout')
    {
        $configs = $this->repo->config->fetchConfigByMerchantIdAndType($this->merchant->getId(), $type);

        return $configs->toArrayPublic();
    }

    /**
     * @param array           $input
     *
     *
     * @return Entity
     */
    public function create(array $input)
    {
        $config = $this->core->create($input);

        return $config->toArrayPublic();
    }

    /**
     * @param array           $input
     *
     *
     * @return Entity
     */
    public function update(array $input)
    {
        (new Validator())->validateInput('edit', $input);

        $config = $this->core->update($input);

        return $config->toArrayPublic();
    }
}
