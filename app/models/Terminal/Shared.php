<?php

namespace Models\Terminal;

use Models\Terminal;

class Shared
{
    const ATOM_RAZORPAY_TERMINAL        = '1000AtomShared';
    const AXIS_MIGS_RAZORPAY_TERMINAL   = '1000AxisMigsTl';

    protected static $shared = array(
        self::ATOM_RAZORPAY_TERMINAL,
        self::AXIS_MIGS_RAZORPAY_TERMINAL,
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

    public static function getSharedTerminal($method)
    {
        if ($method === 'card')
        {
            $terminal = (new Repository)->find(self::AXIS_MIGS_RAZORPAY_TERMINAL);

            if ($terminal !== null)
            {
                return $terminal;
            }
        }

        {
            $terminal = (new Repository)->findOrFail(self::ATOM_RAZORPAY_TERMINAL);
        }

        return $terminal;
    }
}
