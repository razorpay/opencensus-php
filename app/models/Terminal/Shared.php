<?php

namespace Models\Terminal;

use Models\Terminal;

class Shared
{
    const ATOM_RAZORPAY_TERMINAL        = '1000AtomShared';
    const AXIS_MIGS_RAZORPAY_TERMINAL   = '1000AxisMigsTl';
    const AXIS_GENIUS_RAZORPAY_TERMINAL = '1000AxisGenius';
    const BILLDESK_RAZORPAY_TERMINAL    = '1000BdeskTrmnl';
    const HDFC_RAZORPAY_TERMINAL        = '1000HdfcShared';
    const KOTAK_RAZORPAY_TERMINAL       = '1000KotakTrmnl';
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
        self::PAYTM_RAZORPAY_TERMINAL,
        self::NETBANKING_HDFC_TERMINAL,
        self::SHARP_RAZORPAY_TERMINAL,
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
}
