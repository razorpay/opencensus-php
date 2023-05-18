<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

/**
 * State name and state code mapping
 */
class StateMap
{
    function getShopifyStateCode($address)
    {
        switch ($address['country']) {
            case 'in':
                $shopifyStateCode = $this->getShopifyStateCodeIN($address['state_code']);
                break;

            case 'za':
                $shopifyStateCode = $this->getShopifyStateCodeZA($address['state_code']);
                break;

            case 'my':
                $shopifyStateCode = $this->getShopifyStateCodeMY($address['state_code']);
                break;

            default:
                $shopifyStateCode = $address['state_code'];
                break;
        }
        return $shopifyStateCode;
    }

    function getShopifyStateCodeFromName($address)
    {
        switch ($address['country']) {
            case 'in':
                $shopifyStateCode = $this->getShopifyStateCodeFromNameIN($address['state']);
                break;

            case 'my':
                $shopifyStateCode = $this->getShopifyStateCodeFromNameMY($address['state']);
                break;

            default:
                $shopifyStateCode = $address['state'];
                break;
        }
        return $shopifyStateCode;
    }

    //Fetching the state code on Shopify using the state name for India
    function getShopifyStateCodeIN($stateCode)
    {

        $shippingStateCodeMap = [
            'AN' => 'AN',
            'AP' => 'AP',
            'AD' => 'AP',
            'AR' => 'AR',
            'AS' => 'AS',
            'BI' => 'BR',
            'BH' => 'BR',
            'CH' => 'CH',
            'CT' => 'CG',
            'DN' => 'DN',
            'DD' => 'DD',
            'DL' => 'DL',
            'GO' => 'GA',
            'GJ' => 'GJ',
            'HA' => 'HR',
            'HP' => 'HP',
            'JK' => 'JK',
            'JH' => 'JH',
            'KA' => 'KA',
            'KE' => 'KL',
            'LD' => 'LD',
            'LA' => 'LA',
            'MP' => 'MP',
            'MH' => 'MH',
            'MA' => 'MN',
            'ME' => 'ML',
            'MI' => 'MZ',
            'NA' => 'NL',
            'OR' => 'OR',
            'PO' => 'PY',
            'PB' => 'PB',
            'RJ' => 'RJ',
            'SK' => 'SK',
            'TN' => 'TN',
            'TR' => 'TR',
            'TG' => 'TS',
            'UP' => 'UP',
            'UT' => 'UK',
            'WB' => 'WB',
        ];

        $shopifyStateCode = isset($shippingStateCodeMap[$stateCode]) ? $shippingStateCodeMap[$stateCode] : $stateCode;

        return $shopifyStateCode;
    }

    //Fetching the state code on Shopify using the state name for South Africa
    function getShopifyStateCodeZA($stateCode)
    {

        $shippingStateCodeMap = [
            'GP' => 'GT',
            'KZN' => 'NL',
        ];

        $shopifyStateCode = isset($shippingStateCodeMap[$stateCode]) ? $shippingStateCodeMap[$stateCode] : $stateCode;

        return $shopifyStateCode;
    }

    //Fetching the state code on Shopify using the state name for Malaysia
    function getShopifyStateCodeMY($stateCode)
    {
        $shippingStateCodeMap = [
            '01' => 'JHR',
            '02' => 'KDH',
            '03' => 'KTN',
            '04' => 'MLK',
            '05' => 'NSN',
            '06' => 'PHG',
            '07' => 'PNG',
            '08' => 'PRK',
            '09' => 'PLS',
            '10' => 'SGR',
            '11' => 'TRG',
            '12' => 'SBH',
            '13' => 'SWK',
            '14' => 'KUL',
            '15' => 'LBN',
            '16' => 'PJY',
            'labuan federal territory' => 'labuan',
            'federal territory of kuala lumpur' => 'kuala lumpur',
            'malacca' => 'melaka'
        ];

        $shopifyStateCode = isset($shippingStateCodeMap[$stateCode]) ? $shippingStateCodeMap[$stateCode] : $stateCode;

        return $shopifyStateCode;
    }

    //Fetching the state code on Shopify using the state name for India
    function getShopifyStateCodeFromNameIN($stateName)
    {
        $stateCodeMap = [
            'ANDAMAN&NICOBARISLANDS'   => 'AN',
            'ANDAMANANDNICOBARISLANDS' => 'AN',
            'ANDHRAPRADESH'            => 'AP',
            'ARUNACHALPRADESH'         => 'AR',
            'ASSAM'                    => 'AS',
            'BIHAR'                    => 'BR',
            'CHANDIGARH'               => 'CH',
            'CHATTISGARH'              => 'CG',
            'CHHATTISGARH'             => 'CG',
            'DADRA&NAGARHAVELI'        => 'DN',
            'DADRAANDNAGARHAVELI'      => 'DN',
            'DAMAN&DIU'                => 'DD',
            'DAMANANDDIU'              => 'DD',
            'DELHI'                    => 'DL',
            'GOA'                      => 'GA',
            'GUJARAT'                  => 'GJ',
            'HARYANA'                  => 'HR',
            'HIMACHALPRADESH'          => 'HP',
            'JAMMU&KASHMIR'            => 'JK',
            'JAMMUANDKASHMIR'          => 'JK',
            'JAMMUKASHMIR'             => 'JK',
            'JHARKHAND'                => 'JH',
            'KARNATAKA'                => 'KA',
            'KERALA'                   => 'KL',
            'LAKSHADWEEP'              => 'LD',
            'LAKSHADEEP'               => 'LD',
            'LADAKH'                   => 'LA',
            'MADHYAPRADESH'            => 'MP',
            'MAHARASHTRA'              => 'MH',
            'MANIPUR'                  => 'MN',
            'MEGHALAYA'                => 'ML',
            'MIZORAM'                  => 'MZ',
            'NAGALAND'                 => 'NL',
            'ODISHA'                   => 'OR',
            'PONDICHERRY'              => 'PY',
            'PUNJAB'                   => 'PB',
            'RAJASTHAN'                => 'RJ',
            'SIKKIM'                   => 'SK',
            'TAMILNADU'                => 'TN',
            'TRIPURA'                  => 'TR',
            'TELANGANA'                => 'TS',
            'UTTARPRADESH'             => 'UP',
            'UTTARAKHAND'              => 'UK',
            'WESTBENGAL'               => 'WB',
        ];

        $shopifyStateCode = isset($stateCodeMap[$stateName]) ? $stateCodeMap[$stateName] : $stateName;

        return $shopifyStateCode;
    }

    //Fetching the state code on Shopify using the state name for Malaysia
    function getShopifyStateCodeFromNameMY($stateName)
    {
        $stateCodeMap = [
            'Malacca'   => 'MLK',
        ];

        $shopifyStateCode = isset($stateCodeMap[$stateName]) ? $stateCodeMap[$stateName] : $stateName;

        return $shopifyStateCode;
    }

    /**
     * Mapped the pincode to state code to handle the mismatch in google and shopify pincode mapping
     *
    */
    function getPincodeMappedStateCode($pinCode)
    {
        $pinCodeMap = [
            '194101' => 'LA',
            '194104' => 'LA',
            '194105' => 'LA',
            '194106' => 'LA',
            '194201' => 'LA',
            '194401' => 'LA',
            '194402' => 'LA',
            '194404' => 'LA',
            '140133' => 'PB',
            '605100' => 'TN',
            '605101' => 'TN',
            '140603' => 'PB'
        ];

        $shopifyStateCode = $pinCodeMap[$pinCode] ?? null;

        return $shopifyStateCode;
    }

}
