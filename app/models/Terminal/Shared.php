<?php

namespace Models\Terminal;

use Models\Terminal;

class Shared
{
    const ATOM_RAZORPAY_TERMINAL = '1000AtomShared';

    protected static $shared = array(
        self::ATOM_RAZORPAY_TERMINAL,
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

    public static function getSharedTerminal()
    {
        $terminal = (new Repository)->findOrFail(self::ATOM_RAZORPAY_TERMINAL);

        return $terminal;
    }
}
