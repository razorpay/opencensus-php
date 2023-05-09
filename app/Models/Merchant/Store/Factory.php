<?php


namespace RZP\Models\Merchant\Store;


use RZP\Exception\LogicException;

class Factory
{
    public static function getStoreForNamespaceAndKey(string $namespace, string $key)
    {
        $config = ConfigKey::NAMESPACE_KEY_CONFIG[$namespace][$key] ?? [];

        if (empty($config) === true)
        {
            throw new LogicException("invalid namespace key");
        }

        switch ($config[Constants::STORE])
        {
            case Constants::REDIS:
                return new RedisStore();
            default:
                throw new LogicException("invalid store");
        }
    }

    /**
     * @param string $key
     *
     * @return DefaultKey|UPITerminalProcurementStatusBannerKey
     */
    public static function getInstance(string $key)
    {
        switch ($key)
        {
            case ConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER:
                return new UPITerminalProcurementStatusBannerKey();
            default:
                return new DefaultKey();
        }
    }
}
