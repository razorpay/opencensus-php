<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use RZP\Models\Merchant\Metric;
use RZP\Trace\TraceCode;
use RZP\Models\Base;

/**
 * RTO reasons mapping with the labels
 */
class RtoReasons extends Base\Core
{
    protected $monitoring;

    public function __construct()
    {
        parent::__construct();

        $this->monitoring = new Monitoring();
    }

    /**
     * Fetching the corresponding label for RTO reasons
     *
     */
    function getRtoReasons($rto)
    {

        $rtoReasonsMap = [
            'HighPhoneRTOMerchant' => 'High RTO phone on your store',
            'HighEmailRTOMerchant' => 'High RTO email on your store',
            'HighIpRTOMerchant' => 'HighIpRTOMerchant',
            'HighDeviceRTOMerchant' => 'High RTO device on your store',
            'HighPhoneRTOGlobal' => 'High RTO phone across all stores',
            'HighEmailRTOGlobal' => 'High RTO email across all stores',
            'HighIpRTOGlobal' => 'High RTO IP across all stores',
            'HighDeviceRTOGlobal' => 'High RTO device across all stores',
            'HighZipcodeRTOMerchant' => 'High RTO zipcode on your store',
            'HighZipcodeRTOGlobal' => 'High RTO zipcode across all stores',
            'ShortShippingAddress' => 'Short shipping address',
            'AddressMonkeyTyped' => 'Gibberish detected in address',
            'EmailTempDomain' => 'Invalid email domain',
            'ShortZipcode' => 'Incomplete zipcode',
            'TestInAddress' => 'Dummy address detected',
            'TestInName' => 'Dummy name detected',
            'InvalidEmail' => 'Invalid email address',
            'MonkeyTypedEmail' => 'Gibberish detected in email',
            'InvalidPhone' => 'Invalid phone',
            'InvalidZipcode' => 'Invalid zipcode',
            'ZipcodeAddressStateMismatch' => 'Wrong state entered',
            'IncompleteShippingAddress' => 'Incomplete shipping address',
            'addressstaticfeatures_hasdigits' => 'Address contains no digits',
            'addressstaticfeatures_countdigits' => 'Address has very few digits',
            'addressstaticfeatures_lengthtodigitsratio' => 'Address has very few digits',
            'addressstaticfeatures_hassecondfield' => 'Address Line 2 missing',
            'addressstaticfeatures_countsymbols' => 'Address contains too many symbols',
            'addressstaticfeatures_countwords' => 'Address is less detailed',
            'addressstaticfeatures_countcommas' => 'Address does not contain commas',
            'addressstaticfeatures_hashousefield' => 'Address does not contain house name/number',
            'addressstaticfeatures_hasbuildingfield' => 'Address does not contain building name',
            'addressstaticfeatures_haslocalityfield' => 'Address does not contain locality name',
            'addressstaticfeatures_isdigitinshipaddress' => 'Address contains no digits',
            'addressstaticfeatures_counttokens' => 'Address is less detailed',
            'addressstaticfeatures_iszipcodenotgood' => 'Incorrect zipcode entered',
            'addressstaticfeatures_isunitnumericinsociety' => 'Address is less detailed',
            'addressstaticfeatures_isinformative' => 'Address is less detailed',
            'addressstaticfeatures_iscorrect' => 'Mismatch between zipcode and city',
            'citystaticfeatures_citytier' => 'Order belongs to High RTO area',
            'addressstaticfeatures_iscomplete' => 'Address incomplete',
            'addressstaticfeatures_maxwordratio' => 'Address does not contain standard fields',
            'addressstaticfeatures_maxwordsuccess' => 'Address does not contain standard fields',
            'addressstaticfeatures_maxseqbiwordcount' => 'Address does not contain standard fields',
            'addressstaticfeatures_maxseqbiwordratio' => 'Address does not contain standard fields',
            'addressstaticfeatures_maxbiwordcount' => 'Address does not contain standard fields',
            'addressstaticfeatures_maxbiwordratio' => 'Address does not contain standard fields',
            'addressstaticfeatures_mergescore' => 'Address does not contain standard fields',
            'emailstaticfeatures_length' => 'Email is too short',
            'emailstaticfeatures_countdigits' => 'Email has too many digits',
            'generic_dayofmonth' => 'Order placed during start/end of the month',
            'emailmerchantfeatures_countcodorders' => 'High COD orders from this email in your store',
            'emailmerchantfeatures_countprepaidorders' => 'Few prepaid orders seen from email in your store',
            'emailglobalfeatures_rtopercent' => 'High RTO customer across all stores',
            'emailglobalfeatures_countprepaidorders' => 'Low prepaid order customer',
            'emailglobalfeatures_countcodorders' => 'High COD customer across all stores',
            'phoneglobalfeatures_countcodorders' => 'High COD customer across all stores',
            'phoneglobalfeatures_rtopercent' => 'High RTO customer across all stores',
            'phoneglobalfeatures_deliveredpercent' => 'Low delivered orders for customer',
            'deviceidglobalfeatures_countcodorders' => 'High COD customer across all stores',
            'deviceidglobalfeatures_deliveredpercent' => 'Low delivered orders for customer across all stores',
            'zipcodeglobalfeatures_rtopercent' => 'High RTO zipcode across all stores',
            'zipcodeglobalfeatures_deliveredpercent' => 'Low delivered orders on zipcode across all stores',
            'zipcodeglobalfeatures_returnpercent' => 'High return zipcode across all stores',
            'zipcodemerchantfeatures_deliveredpercent' => 'Low delivered orders on zipcode on your store',
            'zipcodemerchantfeatures_rtopercent' => 'High RTO zipcode on your store',
            'zipcodeglobalfeatures_countrtoitems' => 'High RTO zipcode across all stores',
            'zipcodeglobalfeatures_countreturnitems' => 'High return zipcode across all stores',
            'zipcodemerchantfeatures_countdelivereditems' => 'Low delivered orders on zipcode on your store',
            'zipcodemerchantfeatures_countrtoitems' => 'High RTO zipcode on your store',
            'zipcodeglobalfeatures_countprepaidorders' => 'Low prepaid order customer',
            'zipcodeglobalfeatures_countcodorders' => 'High COD customer on your store',
            'citymerchantfeatures_deliveredpercent' => 'Low delivered orders in city on your store',
            'citymerchantfeatures_rtopercent' => 'High RTO city on your store',
            'cityglobalfeatures_countrtoitems' => 'High RTO city across all stores',
            'cityglobalfeatures_countreturnitems' => 'High returns city across all stores',
            'citymerchantfeatures_countdelivereditems' => 'Low delivered orders in city on your store',
            'citymerchantfeatures_countrtoitems' => 'High RTO city on your store',
            'cityglobalfeatures_countcodorders' => 'High COD city across all stores',
            'cityglobalfeatures_rtopercent' => 'High RTO city across all stores',
            'cityglobalfeatures_deliveredpercent' => 'Low delivered orders across city',
            'cityglobalfeatures_returnpercent' => 'High returns city across all stores',
            'statemerchantfeatures_countreturnitems' => 'High returns state across all stores',
            'emailmerchantfeatures_sumpurchaseamountinonehour' => 'High purchase amount in one hour',
            'emailmerchantfeatures_sumpurchaseamountinoneday' => 'High purchase amount in one day',
            'generic_createaccounttimebucket' => 'New customer on your store',
            'phonemerchantfeatures_sumpurchaseamountinoneday' => 'High purchase amount in one day',
        ];

        $rtoReason = isset($rtoReasonsMap[$rto]) ? $rtoReasonsMap[$rto] : '';

        if(empty($rtoReasonsMap[$rto]))
        {
            $dimensions = [
                'rto' => $rto,
            ];
            
            $this->monitoring->addTraceCount(Metric::SHOPIFY_1CC_RTO_LABEL_MISSING_COUNT, $dimensions);

            $this->trace->info(
                TraceCode::SHOPIFY_1CC_RTO_LABEL_MISSING,
                [
                    'type'    => 'rto_label_not_found',
                    'key'     => $rto,
                ]
            );
        }

        return $rtoReason;
    }
    
}
