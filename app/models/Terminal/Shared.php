<?php

namespace Models\Terminal;

use Models\Terminal;

class Shared
{
    const ATOM_RAZORPAY_TERMINAL        = '1000AtomShared';
    const AXIS_MIGS_RAZORPAY_TERMINAL   = '1000AxisMigsTl';
    const AXIS_GENIUS_RAZORPAY_TERMINAL = '1000AxisGenius';
    const KOTAK_RAZORPAY_TERMINAL       = '1000KotakTrmnl';
    const PAYTM_RAZORPAY_TERMINAL       = '1000PaytmTrmnl';

    protected static $shared = array(
        self::ATOM_RAZORPAY_TERMINAL,
        self::AXIS_MIGS_RAZORPAY_TERMINAL,
        self::AXIS_GENIUS_RAZORPAY_TERMINAL,
        self::KOTAK_RAZORPAY_TERMINAL,
        self::PAYTM_RAZORPAY_TERMINAL,
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
        $terminal = null;

        $repo = new Repository;

        if ($method === 'card')
        {
            $terminal = $repo->find(self::AXIS_MIGS_RAZORPAY_TERMINAL);

            if ($terminal !== null)
            {
                return $terminal;
            }

            $terminal = $repo->find(self::AXIS_GENIUS_RAZORPAY_TERMINAL);

            if ($terminal !== null)
            {
                return $terminal;
            }

            $terminal = $repo->find(self::KOTAK_RAZORPAY_TERMINAL);

            if ($terminal !== null)
            {
                return $terminal;
            }
        }

            $terminal = $repo->find(self::PAYTM_RAZORPAY_TERMINAL);

            if ($terminal !== null)
            {
                return $terminal;
            }

        {
            $terminal = $repo->findOrFail(self::ATOM_RAZORPAY_TERMINAL);
        }

        return $terminal;
    }
}
