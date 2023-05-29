<?php

namespace RZP\Models\Pricing;

use RZP\Models\Card;
use RZP\Models\Emi;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Payment\Method;

class BuyPricing
{
    const PAYMENT   = 'payment';
    const MERCHANT  = 'merchant';
    const TERMINALS = 'terminals';
    const PLAN_ID   = 'plan_id';
    const ISSUER    = 'issuer';

    const HDFC                  = 'hdfc';
    const AXIS                  = 'axis';
    const CITI                  = 'citi';
    const YESB                  = 'yesb';
    const ICICI                 = 'icici';
    const AXIS_MIGS             = 'axis_migs';
    const AXIS_GENIUS           = 'axis_genius';
    const FIRST_DATA            = 'first_data';
    const FULCRUM               = 'fulcrum';
    const KOTAK                 = 'kotak';
    const SHARP                 = 'sharp';
    const CARD_FSS              = 'card_fss';
    const ISG                   = 'isg';
    const EBS                   = 'ebs';
    const AMEX                  = 'amex';
    const PAYU                  = 'payu';
    const MPGS                  = 'mpgs';
    const PAYTM                 = 'paytm';
    const PAYSECURE             = 'paysecure';
    const MANDATEHQ             = 'mandate_hq';
    const WORLDLINE             = 'worldline';
    const CASHFREE              = 'cashfree';
    const ZAAKPAY               = 'zaakpay';
    const CCAVENUE              = 'ccavenue';
    const MPI_BLADE             = 'mpi_blade';
    const MPI_ENSTAGE           = 'mpi_enstage';
    const CYBERSOURCE           = 'cybersource';
    const CHECKOUTDOTCOM        = 'checkout_dot_com';
    const CRED                  = 'cred';
    const TWID                  = 'twid';
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
    const NETBANKING_BOB_V2     = 'netbanking_bob_v2';
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
    const NETBANKING_JKB        = 'netbanking_jkb';
    const NETBANKING_IOB        = 'netbanking_iob';
    const NETBANKING_FSB        = 'netbanking_fsb';
    const NETBANKING_AUSF       = 'netbanking_ausf';
    const NETBANKING_NSDL       = 'netbanking_nsdl';
    const NETBANKING_DLB        = 'netbanking_dlb';
    const NETBANKING_BDBL       = 'netbanking_bdbl';
    const NETBANKING_UBI        = 'netbanking_ubi';
    const ATOM                  = 'atom';
    const BILLDESK              = 'billdesk';
    const BILLDESK_SIHUB        = 'billdesk_sihub';
    const UPI                   = 'upi';
    const UPI_MINDGATE          = 'upi_mindgate';
    const UPI_AXIS              = 'upi_axis';
    const UPI_ICICI             = 'upi_icici';
    const UPI_HULK              = 'upi_hulk';
    const UPI_RBL               = 'upi_rbl';
    const UPI_YESB              = 'upi_yesbank';
    const UPI_AIRTEL            = 'upi_airtel';
    const UPI_CITI              = 'upi_citi';
    const UPI_JUSPAY            = 'upi_juspay';
    const P2P_UPI_AXIS          = 'p2p_upi_axis';
    const P2P_UPI_SHARP         = 'p2p_upi_sharp';
    const UPI_SBI               = 'upi_sbi';
    const GOOGLEPAY             = 'google_pay';
    const HDFC_DEBIT_EMI        = 'hdfc_debit_emi';
    const BAJAJ_FINSERV         = 'bajajfinserv';
    const WALLET_AIRTELMONEY    = 'wallet_airtelmoney';
    const WALLET_AMAZONPAY      = 'wallet_amazonpay';
    const WALLET_FREECHARGE     = 'wallet_freecharge';
    const FREECHARGE            = 'freecharge';
    const WALLET_JOIMONEY       = 'wallet_jiomoney';
    const WALLET_SBIBUDDY       = 'wallet_sbibuddy';
    const WALLET_MPESA          = 'wallet_mpesa';
    const MOBIKWIK              = 'mobikwik';
    const WALLET_PHONEPE        = 'wallet_phonepe';
    const WALLET_OLAMONEY       = 'wallet_olamoney';
    const WALLET_PAYUMONEY      = 'wallet_payumoney';
    const PAYUMONEY             = 'payumoney';
    const WALLET_PAYZAPP        = 'wallet_payzapp';
    const WALLET_PHONEPESWITCH  = 'wallet_phonepeswitch';
    const WALLET_OPENWALLET     = 'wallet_openwallet';
    const WALLET_PAYPAL         = 'wallet_paypal';
    const CARDLESS_EMI          = 'cardless_emi';
    const WALNUT369             = 'walnut369';
    const ZESTMONEY             = 'zestmoney';
    const FLEXMONEY             = 'flexmoney';
    const EARLYSALARY           = 'earlysalary';
    const EPAYLATER             = 'epaylater';
    const GETSIMPL              = 'getsimpl';
    const NACH_ICICI            = 'nach_icici';
    const NACH_CITI             = 'nach_citi';
    const HITACHI               = 'hitachi';

    const NETBANKING_CORPORATION        = 'netbanking_corporation';
    const ENACH_NPCI_NETBANKING         = 'enach_npci_netbanking';
    const ENACH_RBL                     = 'enach_rbl';
    const PAYLATER                      = 'paylater';
    const PAYLATER_ICICI                = 'paylater_icici';

    const TRUSTLY                       = 'trustly';
    const POLI                          = 'poli';

    const BPCL_TEST_MERCHANT_ID              = 'GfjiTEOfQJJBBX';
    const BPCL_MERCHANT_ID              = 'IB52daWxMCAW3Q';
    const BPCL_MERCHANT_ID2              = 'IF5xd1DuOFTPMS';
    const BPCL_MERCHANT_ID3              = 'IF60p3c8Dz4zdu';

    protected static $cardGateways = [
        self::HDFC,
        self::AXIS_MIGS,
        self::FIRST_DATA,
        self::CARD_FSS,
        self::ISG,
        self::AMEX,
        self::PAYU,
        self::CASHFREE,
        self::CYBERSOURCE,
        self::CCAVENUE,
        self::AXIS_GENIUS,
        self::MPI_BLADE,
        self::MPI_ENSTAGE,
        self::CHECKOUTDOTCOM,
        self::FULCRUM,
        self::PAYTM,
        self::PAYSECURE,
        self::WORLDLINE,
        self::BILLDESK_SIHUB,
        self::MANDATEHQ,
        self::MPGS,
        self::KOTAK,
        self::SHARP,
        self::HITACHI
    ];

    protected static $netbankingGateways = [
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
        self::BILLDESK,
        self::ATOM,
        self::PAYU,
        self::CASHFREE,
        self::ZAAKPAY,
        self::CCAVENUE,
        self::EBS,
        self::PAYTM,
        self::NETBANKING_JKB,
        self::NETBANKING_IOB,
        self::NETBANKING_FSB,
        self::NETBANKING_AUSF,
        self::NETBANKING_NSDL,
        self::NETBANKING_DLB,
        self::NETBANKING_BDBL,
        self::NETBANKING_UBI,
        self::NETBANKING_BOB_V2,
        self::SHARP,
    ];

    protected static $emiGateways = [
        self::AMEX,
        self::AXIS_MIGS,
        self::FIRST_DATA,
        self::HDFC,
        self::HDFC_DEBIT_EMI,
        self::BAJAJ_FINSERV,
        self::SHARP,
    ];

    protected static $cardlessEmiGateways = [
        self::ZESTMONEY,
        self::FLEXMONEY,
        self::EARLYSALARY,
        self::EPAYLATER,
        self::WALNUT369,
        self::CARDLESS_EMI,
        self::SHARP,
    ];

    protected static $paylaterGateways = [
        self::PAYLATER,
        self::PAYLATER_ICICI,
        self::SHARP,
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

    protected static $walletGateways = [
        self::PAYU,
        self::CCAVENUE,
        self::MOBIKWIK,
        self::PAYTM,
        self::FREECHARGE,
        self::PAYUMONEY,
        self::SHARP,
        self::WALLET_PAYPAL,
        self::WALLET_OPENWALLET,
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

    protected static $upiGateways = [
        self::PAYU,
        self::CASHFREE,
        self::PAYTM,
        self::UPI_HULK,
        self::UPI_RBL,
        self::UPI_YESB,
        self::UPI_AIRTEL,
        self::UPI_CITI,
        self::UPI_JUSPAY,
        self::UPI_MINDGATE,
        self::UPI_AXIS,
        self::UPI_SBI,
        self::UPI_ICICI,
        self::GOOGLEPAY,
        self::P2P_UPI_AXIS,
        self::P2P_UPI_SHARP,
        self::SHARP,
    ];

    protected static $emandateGateways = [
        self::NETBANKING_HDFC,
        self::NETBANKING_AXIS,
        self::NETBANKING_ICICI,
        self::NETBANKING_SBI,
        self::ENACH_RBL,
        self::ENACH_NPCI_NETBANKING,
        self::SHARP,
    ];

    protected static $nachGateways = [
        self::NACH_ICICI,
        self::NACH_CITI,
        self::SHARP,
    ];

    protected static $appGateways = [
        self::CRED,
        self::TWID,
        self::TRUSTLY,
        self::POLI,
    ];

    public static $upiNetworksNames = [
        'Upi'       => self::UPI,
        'GooglePay' => self::GOOGLEPAY,
    ];

    public static $defaultEmiNetWorksNames = [
        'Hdfc'         => self::HDFC,
        'BajajFinserv' => Card\Network::BAJAJ,
        'Kotak'        => self::KOTAK,
    ];

    public static $emiNetworksNames = [
        'Hdfc'         => self::HDFC,
        'BajajFinserv' => self::BAJAJ_FINSERV,
    ];

    public static $nachNetworksNames = [
        'ICICI' => self::ICICI,
    ];

    public static function gatewayForMethod($method)
    {
        switch ($method)
        {
            case $method === Method::CARD:         return self::$cardGateways;
            case $method === Method::NETBANKING:   return self::$netbankingGateways;
            case $method === Method::WALLET:       return self::$walletGateways;
            case $method === Method::EMI:          return self::$emiGateways;
            case $method === Method::UPI:          return self::$upiGateways;
            case $method === Method::EMANDATE:     return self::$emandateGateways;
            case $method === Method::NACH:         return self::$nachGateways;
            case $method === Method::CARDLESS_EMI: return self::$cardlessEmiGateways;
            case $method === Method::PAYLATER:     return self::$paylaterGateways;
            case $method === Method::APP:          return self::$appGateways;
            default:                               return [];
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

    public static function isValidBuyPricingGateway($method, $issuer)
    {
        return in_array($issuer, self::gatewayForMethod($method));
    }

    public static function getPaymentFromBuyPricingCostInput($input)
    {
        $payment = new Payment\Entity;

        $payment->fill($input);

        $payment->merchant()->associate(new Merchant\Entity);

        $methodEntity = $input[$payment->getMethod()] ?? [];

        // associating method entities for rule filtering.
        if (in_array($payment->getMethod(), [Method::CARD, Method::EMI]))
        {
            $card = (new Card\Entity)->fill($methodEntity);

            $payment->card()->associate($card);
        }
        if ($payment->getMethod() === Method::EMI)
        {
            $emi = (new Emi\Entity)->fill($methodEntity);

            $payment->emi()->associate($emi);
        }

        return $payment;
    }
}
