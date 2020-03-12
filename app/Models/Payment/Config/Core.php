<?php


namespace RZP\Models\Payment\Config;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function getFormattedConfigForCheckout($configId, $merchantId, & $data)
    {
        $config = null;

        if (isset($configId) === false)
        {
            $config = $this->repo->config->fetchDefaultConfigByMerchantIdAndType($merchantId, 'checkout');
        }
        else
        {
            $config = $this->repo->config->findByPublicId($configId);
        }

        if (isset($config) === true)
        {
            $data['config'] = json_decode($config->config, true);
        }
    }
}
