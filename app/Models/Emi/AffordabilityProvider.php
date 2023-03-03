<?php

namespace RZP\Models\Emi;

class AffordabilityProvider
{
    public static function getEnabledProviders($all_addon_methods, $addon_methods, $method): array
    {
        return self::getConsolidatedEnabledEmiProviders($all_addon_methods, $addon_methods, $method, 1);
    }

    public static function getConsolidatedEnabledEmiProviders($all_addon_methods, $addon_methods, $method,  int $methodEnabled): array
    {
        $consolidatedEnabledEmiProviders = [];

        foreach ($all_addon_methods[$method] as $provider)
        {
            if(isset($addon_methods[$method]) === true)
            {
                if(isset($addon_methods[$method][$provider]) === true && $addon_methods[$method][$provider] === 1)
                {
                    $consolidatedEnabledEmiProviders[$provider] = $methodEnabled;
                }
                else
                {
                    $consolidatedEnabledEmiProviders[$provider] = 0;
                }
            }
            else
            {
                $consolidatedEnabledEmiProviders[$provider] = 0;
            }
        }

        return $consolidatedEnabledEmiProviders;
    }
}
