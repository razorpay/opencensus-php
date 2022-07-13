<?php

namespace RZP\Models\Merchant\Detail;

class ShopEstablishmentAreaCodeMapping
{
    /**
     * Area code and City name mapping
     */
    const AREA_CODE_CITY_NAMES_MAP = [
        "CAL" => [
            "KOLKATA",
            "KOLKATTA",
            "CALCUTTA",
            "CALCUTA",
        ],
        "GUR" => [
            "GURGAN",
            "GURGAON",
            "GURGOAN",
            "GURGON",
            "GURUGRAM",
            "GURUGARAM",
        ]
    ];

    /**
     * State name and Area code mapping
     */
    const STATE_AREA_CODE_MAP = [
        "DL" => "DL",
        "HP" => "HP",
        "HA" => "HR",
        "JH" => "JH",
        "JK" => "JK",
        "MP" => "MP",
        "UP" => "UP",
        "WB" => "WB",
    ];

    /**
     * This functions responsibility is to return area code based on registered city and State address.
     * It fetches Area code based on the City first if it not found, than fetch based on the State.
     *
     * @param string $registeredCity
     * @param string $registeredState
     *
     * @return string|null
     */
    public function getAreaCode(string $registeredCity, string $registeredState): ?string
    {
        $registeredCity  = strtoupper($registeredCity);
        $registeredState = strtoupper($registeredState);

        $areaCode = $this->getCityAreaCode($registeredCity);

        if (empty($areaCode) === true)
        {
            $areaCode = $this->getStateAreaCode($registeredState);
        }

        return $areaCode;
    }

    /**
     * @param string $place
     *
     * @return string|null
     */
    private function getCityAreaCode(string $place): ?string
    {
        foreach (self::AREA_CODE_CITY_NAMES_MAP as $areaCode => $cityNames)
        {
            if (in_array($place, $cityNames) === true)
            {
                return $areaCode;
            }
        }

        return null;
    }

    /**
     * @param string $place
     *
     * @return string|null
     */
    private function getStateAreaCode(string $place): ?string
    {
        if (isset(self::STATE_AREA_CODE_MAP[$place]) === true)
        {
            return self::STATE_AREA_CODE_MAP[$place];
        }

        return null;
    }
}
