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
    const EBS_RAZORPAY_TERMINAL             = '100000EbsTrmnl';
    const HDFC_RAZORPAY_TERMINAL            = '1000HdfcShared';
    const MOBIKWIK_RAZORPAY_TERMINAL        = '1000MobiKwikTl';
    const NETBANKING_HDFC_TERMINAL          = '100NbHdfcTrmnl';
    const NETBANKING_KOTAK_TERMINAL         = '100NbKotakTmnl';
    const OLAMONEY_RAZORPAY_TERMINAL        = '1000OlamoneyTl';
    const PAYTM_RAZORPAY_TERMINAL           = '1000PaytmTrmnl';
    const PAYZAPP_RAZORPAY_TERMINAL         = '100PayzappTmnl';
    const PAYUMONEY_RAZORPAY_TERMINAL       = '100PayumnyTmnl';
    const FREECHARGE_RAZORPAY_TERMINAL      = '100FrchrgeTmnl';
    const SHARP_RAZORPAY_TERMINAL           = '1000SharpTrmnl';
    const CYBERSOURCE_HDFC_TERMINAL         = '1000CybrsTrmnl';
    const CYBERSOURCE_AXIS_TERMINAL         = '1000CybAxTrmnl';
    const FIRST_DATA_RAZORPAY_TERMINAL      = '1000FrstDataTl';
    const UPI_ICICI_RAZORPAY_TERMINAL       = '100UPIICICITml';
    const AIRTELMONEY_RAZORPAY_TERMINAL     = '100ArtlMnyTmnl';

    protected static $shared = array(
        self::ATOM_RAZORPAY_TERMINAL,
        self::AXIS_MIGS_RAZORPAY_TERMINAL,
        self::AXIS_GENIUS_RAZORPAY_TERMINAL,
        self::BILLDESK_RAZORPAY_TERMINAL,
        self::EBS_RAZORPAY_TERMINAL,
        self::HDFC_RAZORPAY_TERMINAL,
        self::MOBIKWIK_RAZORPAY_TERMINAL,
        self::OLAMONEY_RAZORPAY_TERMINAL,
        self::PAYTM_RAZORPAY_TERMINAL,
        self::NETBANKING_HDFC_TERMINAL,
        self::NETBANKING_KOTAK_TERMINAL,
        self::PAYZAPP_RAZORPAY_TERMINAL,
        self::PAYUMONEY_RAZORPAY_TERMINAL,
        self::FREECHARGE_RAZORPAY_TERMINAL,
        self::SHARP_RAZORPAY_TERMINAL,
        self::CYBERSOURCE_HDFC_TERMINAL,
        self::CYBERSOURCE_AXIS_TERMINAL,
        self::FIRST_DATA_RAZORPAY_TERMINAL,
        self::UPI_ICICI_RAZORPAY_TERMINAL,
        self::AIRTELMONEY_RAZORPAY_TERMINAL,
    );

    // NOTE: No two shared terminal should be present for same gateway
    // See getSharedTerminalForGateway() for the reason
    protected static $map = array(
        self::AMEX_RAZORPAY_TERMINAL        => Gateway::AMEX,
        self::ATOM_RAZORPAY_TERMINAL        => Gateway::ATOM,
        self::AXIS_GENIUS_RAZORPAY_TERMINAL => Gateway::AXIS_GENIUS,
        self::AXIS_MIGS_RAZORPAY_TERMINAL   => Gateway::AXIS_MIGS,
        self::BILLDESK_RAZORPAY_TERMINAL    => Gateway::BILLDESK,
        self::EBS_RAZORPAY_TERMINAL         => Gateway::EBS,
        self::CYBERSOURCE_HDFC_TERMINAL     => Gateway::CYBERSOURCE,
        self::HDFC_RAZORPAY_TERMINAL        => Gateway::HDFC,
        self::MOBIKWIK_RAZORPAY_TERMINAL    => Gateway::MOBIKWIK,
        self::NETBANKING_HDFC_TERMINAL      => Gateway::NETBANKING_HDFC,
        self::NETBANKING_KOTAK_TERMINAL     => Gateway::NETBANKING_KOTAK,
        self::OLAMONEY_RAZORPAY_TERMINAL    => Gateway::WALLET_OLAMONEY,
        self::PAYTM_RAZORPAY_TERMINAL       => Gateway::PAYTM,
        self::PAYZAPP_RAZORPAY_TERMINAL     => Gateway::WALLET_PAYZAPP,
        self::PAYUMONEY_RAZORPAY_TERMINAL   => Gateway::WALLET_PAYUMONEY,
        self::AIRTELMONEY_RAZORPAY_TERMINAL => Gateway::WALLET_AIRTELMONEY,
        self::FREECHARGE_RAZORPAY_TERMINAL  => Gateway::WALLET_FREECHARGE,
        self::SHARP_RAZORPAY_TERMINAL       => Gateway::SHARP,
        self::FIRST_DATA_RAZORPAY_TERMINAL  => Gateway::FIRST_DATA,
        self::UPI_ICICI_RAZORPAY_TERMINAL   => Gateway::UPI_ICICI,
    );

    public static function isSharedTerminal($terminal)
    {
        $id = $terminal->getId();

        return in_array($id, self::$shared);
    }

    public static function isPaymentOnSharedTerminal($payment)
    {
        $terminal = $payment->terminal;

        return self::isSharedTerminal($terminal);
    }

    public static function getSharedTerminalMapping()
    {
        return self::$map;
    }

    public static function getGatewayForTerminal($terminal)
    {
        return self::$map[$terminal];
    }

    public static function getSharedTerminalForGateway($gateway)
    {
        $map = array_flip(self::$map);

        return $map[$gateway];
    }
}
