<?php

namespace Models\Terminal;

use Models\Payment\Gateway;
use Models\Terminal;

class Shared
{
    const AMEX_RAZORPAY_TERMINAL        = '1000AmexShared';
    const ATOM_RAZORPAY_TERMINAL        = '1000AtomShared';
    const AXIS_MIGS_RAZORPAY_TERMINAL   = '1000AxisMigsTl';
    const AXIS_GENIUS_RAZORPAY_TERMINAL = '1000AxisGenius';
    const BILLDESK_RAZORPAY_TERMINAL    = '1000BdeskTrmnl';
    const HDFC_RAZORPAY_TERMINAL        = '1000HdfcShared';
    const KOTAK_RAZORPAY_TERMINAL       = '1000KotakTrmnl';
    const MOBIKWIK_RAZORPAY_TERMINAL    = '1000MobiKwikTl';
    const PAYTM_RAZORPAY_TERMINAL       = '1000PaytmTrmnl';
    const NETBANKING_HDFC_TERMINAL      = '100NbHdfcTrmnl';
    const SHARP_RAZORPAY_TERMINAL       = '1000SharpTrmnl';

    protected static $shared = array(
        self::ATOM_RAZORPAY_TERMINAL,
        self::AXIS_MIGS_RAZORPAY_TERMINAL,
        self::AXIS_GENIUS_RAZORPAY_TERMINAL,
        self::BILLDESK_RAZORPAY_TERMINAL,
        self::HDFC_RAZORPAY_TERMINAL,
        self::KOTAK_RAZORPAY_TERMINAL,
        self::MOBIKWIK_RAZORPAY_TERMINAL,
        self::PAYTM_RAZORPAY_TERMINAL,
        self::NETBANKING_HDFC_TERMINAL,
        self::SHARP_RAZORPAY_TERMINAL,
    );

    protected static $map = array(
        self::AMEX_RAZORPAY_TERMINAL         =>  Gateway::AMEX,
        self::ATOM_RAZORPAY_TERMINAL         =>  Gateway::ATOM,
        self::AXIS_GENIUS_RAZORPAY_TERMINAL  =>  Gateway::AXIS_GENIUS,
        self::AXIS_MIGS_RAZORPAY_TERMINAL    =>  Gateway::AXIS_MIGS,
        self::BILLDESK_RAZORPAY_TERMINAL     =>  Gateway::BILLDESK,
        self::HDFC_RAZORPAY_TERMINAL         =>  Gateway::HDFC,
        self::KOTAK_RAZORPAY_TERMINAL        =>  Gateway::KOTAK,
        self::MOBIKWIK_RAZORPAY_TERMINAL     =>  Gateway::MOBIKWIK,
        self::PAYTM_RAZORPAY_TERMINAL        =>  Gateway::PAYTM,
        self::NETBANKING_HDFC_TERMINAL       =>  Gateway::NETBANKING_HDFC,
        self::SHARP_RAZORPAY_TERMINAL        =>  Gateway::SHARP,
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
}
