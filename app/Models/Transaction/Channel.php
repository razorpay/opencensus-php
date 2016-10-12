<?php

namespace RZP\Models\Transaction;

use RZP\Exception\LogicException;
use RZP\Models\Payment;
use RZP\Models\Terminal\Shared;

class Channel
{
    const KOTAK = 'kotak';
    const ATOM = 'atom';

    public static $gateways = array(
        self::KOTAK => array(
            Payment\Gateway::AMEX,
            Payment\Gateway::AXIS_GENIUS,
            Payment\Gateway::AXIS_MIGS,
            Payment\Gateway::BILLDESK,
            Payment\Gateway::HDFC,
            Payment\Gateway::KOTAK,
            Payment\Gateway::MOBIKWIK,
            Payment\Gateway::PAYTM,
            Payment\Gateway::NETBANKING_HDFC,
            Payment\Gateway::WALLET_PAYZAPP,
        ),

        self::ATOM => array(
            Payment\Gateway::ATOM
        ),
    );

    public static function getChannels()
    {
        return [self::KOTAK, self::ATOM];
    }

    public static function getGateways($channel)
    {
        return self::$gateways[$channel];
    }

    public static function decideChannelForTransaction($txn)
    {
        $type = $txn->getType();

        $channel = null;
        $gateway = null;

        $entity = $txn->source;

        $payment = null;

        switch ($type)
        {
            case Type::PAYMENT:
                $payment = $entity;
                $gateway = $entity->getGateway();
                break;

            case Type::REFUND:
                $payment = $entity->payment;
                $gateway = $entity->payment->getGateway();
                break;

            case Type::SETTLEMENT:
            case Type::ADJUSTMENT:
                $channel = $entity->getChannel();
                break;

            default:
                throw new LogicException('Invalid type: ' . $type);
        }

        if ($channel === null)
        {
            $channel = Payment\Gateway::getChannel($gateway);
        }

        if ($gateway === Payment\Gateway::ATOM)
        {
            //
            // Here we check for special atom terminal
            // whether that has been used.
            // If yes, then settlement channel in this case
            // will be kotak instead of atom.
            //

            $terminal = $payment->terminal;

            if (Shared::isSharedTerminal($terminal))
            {
                $channel = Channel::KOTAK;
            }
        }

        return $channel;
    }
}
