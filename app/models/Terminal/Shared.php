<?php

namespace Models\Terminal;

class Shared
{
    const ATOM_RAZORPAY_TERMINAL = '2015AtomShared';

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
}
