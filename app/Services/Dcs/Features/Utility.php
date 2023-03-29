<?php

namespace RZP\Services\Dcs\Features;

use Illuminate\Support\Str;

class Utility
{
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
}
