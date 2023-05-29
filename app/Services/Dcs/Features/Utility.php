<?php

namespace RZP\Services\Dcs\Features;

use Illuminate\Support\Str;

class Utility
{
    public static array $cachePrefixes = [
        "d35593be04",
        "9864630de5",
        "904e6b365f",
        "99e065d633",
        "56336e9d09",
        "663j6e9h09",
        "j6396l9d09",
        "p6x36emd09",
    ];
    /**
     * Utility to extract the actual name from the DCS feature name if the name contains ":"
     * We have to support this for the features having same name in two separate domains in DCS
     *
     * @param string $featureName
     * @return string
     */
    public static function extractActualDcsName(string $featureName): string
    {
        if(Str::contains($featureName, ":"))
        {
            return Str::after($featureName,":");
        }

        return $featureName;
    }

    /**
     * @param array $dcsFeatureNameToAPIFeatureName
     * @param string $featureName
     * @return string
     */
    public static function searchAndReturnDcsNameWithCorrespondingColonSeparator(array $dcsFeatureNameToAPIFeatureName, string $featureName, string $dcsKey = ""): string
    {
        foreach ($dcsFeatureNameToAPIFeatureName as $key => $value)
        {
            $cleanedDcsKey = strtolower($dcsKey);
            $cleanedDcsKeyFromMap = strtolower(str_replace("/", "\\",Constants::$featureToDCSKeyMapping[$key]));
            if ($dcsKey != "" && $cleanedDcsKey === $cleanedDcsKeyFromMap && Str::endsWith($key, ":" . $featureName) === true) {
                return $key;
            }
        }

        return "";
    }

    public static function getRandomPrefix(): string
    {
        return self::$cachePrefixes[array_rand(self::$cachePrefixes)];
    }
}
