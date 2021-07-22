<?php

namespace RZP\Models\Pricing;

use RZP\Models\Payment\Method;

class BuyPricing
{
    const HDFC                  = 'hdfc';
    const AXIS                  = 'axis';
    const CITI                  = 'citi';
    const YESB                  = 'yesb';
    const ICICI                 = 'icici';
    const AXIS_MIGS             = 'axis_migs';
    const FIRST_DATA            = 'first_data';
    const CARD_FSS_SBI          = 'card_fss_sbin_acquirer';
    const CARD_FSS_BARB         = 'card_fss_barb_acquirer';
    const AMEX                  = 'amex';
    const CYBERSOURCE           = 'cybersource';
    const CRED                  = 'cred';
    const NETBANKING_HDFC       = 'netbanking_hdfc';
    const SBIN                  = 'SBIN';
    const NETBANKING_CIUB       = 'netbanking_ciub';
    const NETBANKING_SBI        = 'netbanking_sbi';
    const NETBANKING_AXIS       = 'netbanking_axis';
    const NETBANKING_ICICI      = 'netbanking_icici';
    const NETBANKING_CUB        = 'netbanking_cub';
    const NETBANKING_AIRTEL     = 'netbanking_airtel';
    const NETBANKING_ALLAHABAD  = 'netbanking_allahabad';
    const NETBANKING_BOB        = 'netbanking_bob';
    const NETBANKING_CANARA     = 'netbanking_canara';
    const NETBANKING_CSB        = 'netbanking_csb';
    const NETBANKING_CBI        = 'netbanking_cbi';
    const NETBANKING_DCB        = 'netbanking_dcb';
    const NETBANKING_EQUITAS    = 'netbanking_equitas';
    const NETBANKING_FEDERAL    = 'netbanking_federal';
    const NETBANKING_IDBI       = 'netbanking_idbi';
    const NETBANKING_VIJAYA     = 'netbanking_vijaya';
    const NETBANKING_IDFC       = 'netbanking_idfc';
    const NETBANKING_IBK        = 'netbanking_ibk';
    const NETBANKING_INDUSIND   = 'netbanking_indusind';
    const NETBANKING_JSB        = 'netbanking_jsb';
    const NETBANKING_KVB        = 'netbanking_kvb';
    const NETBANKING_KOTAK      = 'netbanking_kotak';
    const NETBANKING_OBC        = 'netbanking_obc';
    const NETBANKING_PNB        = 'netbanking_pnb';
    const NETBANKING_RBL        = 'netbanking_rbl';
    const NETBANKING_SVC        = 'netbanking_svc';
    const NETBANKING_SIB        = 'netbanking_sib';
    const NETBANKING_YESB       = 'netbanking_yesb';
    const NETBANKING_SCB        = 'netbanking_scb';
    const ATOM                  = 'atom';
    const BILLDESK              = 'billdesk';
    const UPI                   = 'upi';
    const UPI_MINDGATE          = 'upi_mindgate';
    const UPI_AXIS              = 'upi_axis';
    const UPI_ICICI             = 'upi_icici';
    const UPI_SBI               = 'upi_sbi';
    const GOOGLEPAY             = 'google_pay';
    const HDFC_DEBIT_EMI        = 'hdfc_debit_emi';
    const BAJAJ_FINSERV         = 'bajajfinserv';
    const WALLET_AIRTELMONEY    = 'wallet_airtelmoney';
    const WALLET_AMAZONPAY      = 'wallet_amazonpay';
    const WALLET_FREECHARGE     = 'wallet_freecharge';
    const WALLET_JOIMONEY       = 'wallet_jiomoney';
    const WALLET_SBIBUDDY       = 'wallet_sbibuddy';
    const WALLET_MPESA          = 'wallet_mpesa';
    const MOBIKWIK              = 'mobikwik';
    const WALLET_PHONEPE        = 'wallet_phonepe';
    const WALLET_OLAMONEY       = 'wallet_olamoney';
    const WALLET_PAYUMONEY      = 'wallet_payumoney';
    const WALLET_PAYZAPP        = 'wallet_payzapp';
    const WALLET_PHONEPESWITCH  = 'wallet_phonepeswitch';
    const ZESTMONEY             = 'zestmoney';
    const FLEXMONEY             = 'flexmoney';
    const EARLYSALARY           = 'earlysalary';
    const EPAYLATER             = 'epaylater';
    const GETSIMPL              = 'getsimpl';
    const NACH_ICICI            = 'nach_icici';

    const NETBANKING_CORPORATION        = 'netbanking_corporation';
    const NETBANKING_ICICI_CORPORATE    = 'netbanking_icici_corporate';
    const NETBANKING_AXIS_CORPORATE     = 'netbanking_axis_corporate';
    const NETBANKING_KOTAK_CORPORATE    = 'netbanking_kotak_corporate';
    const ENACH_NPCI_NETBANKING         = 'enach_npci_netbanking';
    const EPAYLATER_ACQUIRER            = 'paylater_epaylater_acquirer';
    const GETSIMPL_ACQUIRER             = 'paylater_getsimpl_acquirer';

    protected static $cardIssuers = [
        self::HDFC,
        self::AXIS_MIGS,
        self::FIRST_DATA,
        self::CARD_FSS_SBI,
        self::CARD_FSS_BARB,
        self::AMEX,
        self::CYBERSOURCE,
    ];

    protected static $netbankingIssuers = [
        self::NETBANKING_HDFC,
        self::SBIN,
        self::NETBANKING_CIUB,
        self::NETBANKING_SBI,
        self::NETBANKING_AXIS,
        self::NETBANKING_ICICI,
        self::NETBANKING_CUB,
        self::NETBANKING_AIRTEL,
        self::NETBANKING_ALLAHABAD,
        self::NETBANKING_BOB,
        self::NETBANKING_CANARA,
        self::NETBANKING_CSB,
        self::NETBANKING_CBI,
        self::NETBANKING_DCB,
        self::NETBANKING_EQUITAS,
        self::NETBANKING_FEDERAL,
        self::NETBANKING_IDBI,
        self::NETBANKING_VIJAYA,
        self::NETBANKING_IDFC,
        self::NETBANKING_IBK,
        self::NETBANKING_INDUSIND,
        self::NETBANKING_JSB,
        self::NETBANKING_KVB,
        self::NETBANKING_KOTAK,
        self::NETBANKING_OBC,
        self::NETBANKING_PNB,
        self::NETBANKING_RBL,
        self::NETBANKING_SVC,
        self::NETBANKING_SIB,
        self::NETBANKING_CORPORATION,
        self::NETBANKING_YESB,
        self::NETBANKING_SCB,
        self::NETBANKING_ICICI_CORPORATE,
        self::NETBANKING_AXIS_CORPORATE,
        self::NETBANKING_KOTAK_CORPORATE,
        self::BILLDESK,
        self::ATOM,
    ];

    protected static $emiIssuers = [
        self::HDFC_DEBIT_EMI,
        self::BAJAJ_FINSERV,
    ];

    protected static $cardlessEmiIssuers = [
        self::ZESTMONEY,
        self::FLEXMONEY,
        self::EARLYSALARY,
    ];

    protected static $paylaterIssuers = [
        self::EPAYLATER_ACQUIRER,
        self::GETSIMPL_ACQUIRER,
    ];

    public static $cardlessEmiNetworksNames = [
        'ZestMoney'    =>  self::ZESTMONEY,
        'FlexMoney'    =>  self::FLEXMONEY,
        'EarlySalary'  =>  self::EARLYSALARY,
    ];

    public static $paylaterNetworksNames = [
        'Epaylater'  => self::EPAYLATER,
        'GetSimpl'   => self::GETSIMPL,
    ];

    protected static $walletIssuers = [
        self::WALLET_PHONEPE,
        self::WALLET_AIRTELMONEY,
        self::WALLET_PHONEPESWITCH,
        self::WALLET_AMAZONPAY,
        self::WALLET_FREECHARGE,
        self::WALLET_JOIMONEY,
        self::WALLET_SBIBUDDY,
        self::WALLET_MPESA,
        self::WALLET_OLAMONEY,
        self::WALLET_PAYUMONEY,
        self::WALLET_PAYZAPP,
        self::MOBIKWIK,
    ];

    protected static $upiIssuers = [
        self::UPI_MINDGATE,
        self::UPI_AXIS,
        self::UPI_SBI,
        self::UPI_ICICI,
        self::GOOGLEPAY,
    ];

    protected static $emandateIssuers = [
        self::NETBANKING_HDFC,
        self::NETBANKING_AXIS,
        self::NETBANKING_ICICI,
        self::NETBANKING_SBI,
        self::ENACH_NPCI_NETBANKING,
    ];

    protected static $nachIssuers = [
        self::NACH_ICICI,
    ];

    protected static $appIssuers = [
        self::CRED,
    ];

    public static $upiNetworksNames = [
        'Upi'       => self::UPI,
        'GooglePay' => self::GOOGLEPAY,
    ];

    public static $emiNetworksNames = [
        'Hdfc'         => self::HDFC,
        'BajajFinserv' => self::BAJAJ_FINSERV,
    ];

    public static $nachNetworksNames = [
        'ICICI' => self::ICICI,
    ];

    public static function issuerForMethod($method)
    {
        switch ($method)
        {
            case $method === Method::CARD:       return self::$cardIssuers;
            case $method === Method::NETBANKING: return self::$netbankingIssuers;
            case $method === Method::WALLET:     return self::$walletIssuers;
            case $method === Method::EMI:        return self::$emiIssuers;
            case $method === Method::UPI:        return self::$upiIssuers;
            case $method === Method::EMANDATE:   return self::$emandateIssuers;
            case $method === Method::NACH:       return self::$nachIssuers;
            case $method === Method::CARDLESS_EMI: return self::$cardlessEmiIssuers;
            case $method === Method::PAYLATER:   return self::$paylaterIssuers;
            case $method === Method::APP:        return self::$appIssuers;
            default:                             return [];
        }
    }

    protected static function networksForMethod($method)
    {
        switch ($method)
        {
            case $method === Method::UPI:      return array_values(self::$upiNetworksNames);
            case $method === Method::EMI:      return array_values(self::$emiNetworksNames);
            case $method === Method::NACH:     return array_values(self::$nachNetworksNames);
            case $method === Method::PAYLATER: return array_values(self::$paylaterNetworksNames);
            case $method === Method::CARDLESS_EMI: return array_values(self::$cardlessEmiNetworksNames);
            default:                           return [];
        }
    }

    public static function isValidBuyPricingNetwork($method, $network)
    {
        return in_array($network, self::networksForMethod($method));
    }

    public static function isValidBuyPricingIssuer($method, $issuer)
    {
        return in_array($issuer, self::issuerForMethod($method));
    }
}
