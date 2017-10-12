<?php

namespace RZP\Models\Terminal;

use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal;

class Shared
{
    const AMEX_RAZORPAY_TERMINAL            = '1000AmexShared';
    const ATOM_RAZORPAY_TERMINAL            = '1000AtomShared';
    const AXIS_GENIUS_RAZORPAY_TERMINAL     = '1000AxisGenius';
    const AXIS_MIGS_RAZORPAY_TERMINAL       = '1000AxisMigsTl';
    const BILLDESK_RAZORPAY_TERMINAL        = '1000BdeskTrmnl';
    const BLADE_RAZORPAY_TERMINAL           = '1000BladeTrmnl';
    const EBS_RAZORPAY_TERMINAL             = '100000EbsTrmnl';
    const HDFC_RAZORPAY_TERMINAL            = '1000HdfcShared';
    const MOBIKWIK_RAZORPAY_TERMINAL        = '1000MobiKwikTl';
    const NETBANKING_HDFC_TERMINAL          = '100NbHdfcTrmnl';
    const NETBANKING_CORPORATION_TERMINAL   = '100NbCorpTrmnl';
    const NETBANKING_KOTAK_TERMINAL         = '100NbKotakTmnl';
    const NETBANKING_ICICI_TERMINAL         = '100NbIciciTmnl';
    const NETBANKING_ICICI_TPV_TERMINAL     = '100NbIcicTpvTl';
    const NETBANKING_ICICI_REC_TERMINAL     = '100NbIcicRecTl';
    const NETBANKING_HDFC_REC_TERMINAL      = '100NbHdfcRecTl';
    const NETBANKING_AIRTEL_TERMINAL        = '100NbAirtlTmnl';
    const NETBANKING_AXIS_TERMINAL          = '100NbAxisTrmnl';
    const NETBANKING_AXIS_TPV_TERMINAL      = '100NbAxisTpvTl';
    const NETBANKING_AXIS_REC_TERMINAL      = '100NbAxisRecTl';
    const NETBANKING_FEDERAL_TERMINAL       = '100NbFdrlTrmnl';
    const NETBANKING_FEDERAL_TPV_TERMINAL   = '100NbFdrlTpvTl';
    const NETBANKING_RBL_TERMINAL           = '100NbRblTermnl';
    const NETBANKING_RBL_TPV_TERMINAL       = '100NbRblTpvTml';
    const NETBANKING_INDUSIND_TERMINAL      = '100NbIndnTrmnl';
    const NETBANKING_INDUSIND_TPV_TERMINAL  = '100NbIndnTpvTl';
    const NETBANKING_PNB_TERMINAL           = '100NbPunbTrmnl';
    const OLAMONEY_RAZORPAY_TERMINAL        = '1000OlamoneyTl';
    const PAYTM_RAZORPAY_TERMINAL           = '1000PaytmTrmnl';
    const PAYZAPP_RAZORPAY_TERMINAL         = '100PayzappTmnl';
    const PAYUMONEY_RAZORPAY_TERMINAL       = '100PayumnyTmnl';
    const FREECHARGE_RAZORPAY_TERMINAL      = '100FrchrgeTmnl';
    const SHARP_RAZORPAY_TERMINAL           = '1000SharpTrmnl';
    const CYBERSOURCE_HDFC_TERMINAL         = '1000CybrsTrmnl';
    const CYBERSOURCE_AXIS_TERMINAL         = '1000CybAxTrmnl';
    const HITACHI_TERMINAL                  = '100HitachiTmnl';
    const FIRST_DATA_RAZORPAY_TERMINAL      = '1000FrstDataTl';
    const UPI_MINDGATE_RAZORPAY_TERMINAL    = '100UPIMindgate';
    const UPI_MINDGATE_SBI_RAZORPAY_TERMINAL = '100UPIMgateSbi';
    const UPI_ICICI_RAZORPAY_TERMINAL       = '100UPIICICITml';
    const AEPS_ICICI_RAZORPAY_TERMINAL      = '1000AepsShared';
    const AIRTELMONEY_RAZORPAY_TERMINAL     = '100ArtlMnyTmnl';
    const JIOMONEY_RAZORPAY_TERMINAL        = '1000JioMnyTmnl';
    const SBIBUDDY_RAZORPAY_TERMINAL        = '1000SbibdyTmnl';
    const OPENWALLET_RAZORPAY_TERMINAL      = '100OpenwalltTl';
    const MPESA_RAZORPAY_TERMINAL           = '100VodaMpesaTl';

    protected static $shared = array(
        self::ATOM_RAZORPAY_TERMINAL,
        self::AXIS_MIGS_RAZORPAY_TERMINAL,
        self::AXIS_GENIUS_RAZORPAY_TERMINAL,
        self::BILLDESK_RAZORPAY_TERMINAL,
        self::BLADE_RAZORPAY_TERMINAL,
        self::EBS_RAZORPAY_TERMINAL,
        self::HDFC_RAZORPAY_TERMINAL,
        self::MOBIKWIK_RAZORPAY_TERMINAL,
        self::OLAMONEY_RAZORPAY_TERMINAL,
        self::PAYTM_RAZORPAY_TERMINAL,
        self::NETBANKING_HDFC_TERMINAL,
        self::NETBANKING_KOTAK_TERMINAL,
        self::NETBANKING_ICICI_TERMINAL,
        self::NETBANKING_AIRTEL_TERMINAL,
        self::NETBANKING_AXIS_TERMINAL,
        self::NETBANKING_FEDERAL_TERMINAL,
        self::NETBANKING_RBL_TERMINAL,
        self::NETBANKING_INDUSIND_TERMINAL,
        self::NETBANKING_PNB_TERMINAL,
        self::PAYZAPP_RAZORPAY_TERMINAL,
        self::PAYUMONEY_RAZORPAY_TERMINAL,
        self::FREECHARGE_RAZORPAY_TERMINAL,
        self::SHARP_RAZORPAY_TERMINAL,
        self::CYBERSOURCE_HDFC_TERMINAL,
        self::CYBERSOURCE_AXIS_TERMINAL,
        self::HITACHI_TERMINAL,
        self::FIRST_DATA_RAZORPAY_TERMINAL,
        self::UPI_MINDGATE_RAZORPAY_TERMINAL,
        self::UPI_ICICI_RAZORPAY_TERMINAL,
        self::UPI_MINDGATE_SBI_RAZORPAY_TERMINAL,
        self::AEPS_ICICI_RAZORPAY_TERMINAL,
        self::AIRTELMONEY_RAZORPAY_TERMINAL,
        self::JIOMONEY_RAZORPAY_TERMINAL,
        self::SBIBUDDY_RAZORPAY_TERMINAL,
        self::OPENWALLET_RAZORPAY_TERMINAL,
        self::MPESA_RAZORPAY_TERMINAL,
    );

    // NOTE: No two shared terminal should be present for same gateway
    // See getSharedTerminalForGateway() for the reason
    protected static $map = [
        self::AMEX_RAZORPAY_TERMINAL             => Gateway::AMEX,
        self::BLADE_RAZORPAY_TERMINAL            => Gateway::BLADE,
        self::ATOM_RAZORPAY_TERMINAL             => Gateway::ATOM,
        self::AXIS_GENIUS_RAZORPAY_TERMINAL      => Gateway::AXIS_GENIUS,
        self::AXIS_MIGS_RAZORPAY_TERMINAL        => Gateway::AXIS_MIGS,
        self::BILLDESK_RAZORPAY_TERMINAL         => Gateway::BILLDESK,
        self::EBS_RAZORPAY_TERMINAL              => Gateway::EBS,
        self::CYBERSOURCE_HDFC_TERMINAL          => Gateway::CYBERSOURCE,
        self::HDFC_RAZORPAY_TERMINAL             => Gateway::HDFC,
        self::HITACHI_TERMINAL                   => Gateway::HITACHI,
        self::MOBIKWIK_RAZORPAY_TERMINAL         => Gateway::MOBIKWIK,
        self::NETBANKING_HDFC_TERMINAL           => Gateway::NETBANKING_HDFC,
        self::NETBANKING_CORPORATION_TERMINAL    => Gateway::NETBANKING_CORPORATION,
        self::NETBANKING_KOTAK_TERMINAL          => Gateway::NETBANKING_KOTAK,
        self::NETBANKING_ICICI_TERMINAL          => Gateway::NETBANKING_ICICI,
        self::NETBANKING_AIRTEL_TERMINAL         => Gateway::NETBANKING_AIRTEL,
        self::NETBANKING_AXIS_TERMINAL           => Gateway::NETBANKING_AXIS,
        self::NETBANKING_FEDERAL_TERMINAL        => Gateway::NETBANKING_FEDERAL,
        self::NETBANKING_RBL_TERMINAL            => Gateway::NETBANKING_RBL,
        self::NETBANKING_INDUSIND_TERMINAL       => Gateway::NETBANKING_INDUSIND,
        self::NETBANKING_PNB_TERMINAL            => Gateway::NETBANKING_PNB_TERMINAL,
        self::OLAMONEY_RAZORPAY_TERMINAL         => Gateway::WALLET_OLAMONEY,
        self::PAYTM_RAZORPAY_TERMINAL            => Gateway::PAYTM,
        self::PAYZAPP_RAZORPAY_TERMINAL          => Gateway::WALLET_PAYZAPP,
        self::PAYUMONEY_RAZORPAY_TERMINAL        => Gateway::WALLET_PAYUMONEY,
        self::AIRTELMONEY_RAZORPAY_TERMINAL      => Gateway::WALLET_AIRTELMONEY,
        self::FREECHARGE_RAZORPAY_TERMINAL       => Gateway::WALLET_FREECHARGE,
        self::JIOMONEY_RAZORPAY_TERMINAL         => Gateway::WALLET_JIOMONEY,
        self::SBIBUDDY_RAZORPAY_TERMINAL         => Gateway::WALLET_SBIBUDDY,
        self::SHARP_RAZORPAY_TERMINAL            => Gateway::SHARP,
        self::FIRST_DATA_RAZORPAY_TERMINAL       => Gateway::FIRST_DATA,
        self::AEPS_ICICI_RAZORPAY_TERMINAL       => Gateway::AEPS_ICICI,
        self::UPI_MINDGATE_RAZORPAY_TERMINAL     => Gateway::UPI_MINDGATE,
        self::UPI_ICICI_RAZORPAY_TERMINAL        => Gateway::UPI_ICICI,
        self::UPI_MINDGATE_SBI_RAZORPAY_TERMINAL => Gateway::UPI_MINDGATE_SBI,
        self::OPENWALLET_RAZORPAY_TERMINAL       => Gateway::WALLET_OPENWALLET,
        self::MPESA_RAZORPAY_TERMINAL            => Gateway::WALLET_MPESA,
    ];

    public static function getSharedTerminalMapping()
    {
        return self::$map;
    }

    public static function getGatewayForTerminal($terminal)
    {
        return self::$map[$terminal];
    }
}
