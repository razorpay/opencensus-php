<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

/**
 * State name and state code mapping
 */
class StateMap
{
    /**
     * Fetching the corresponding state code on Shopify
     *
     */
    function getShopifyStateCode($stateCode)
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
    
    //Fetching the state code on Shopify using the state name
    function getShopifyStateCodeFromName($stateName)
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
}
