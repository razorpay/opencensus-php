<?php

namespace RZP\Gateway\Card\Fss;

use RZP\Exception;
use RZP\Models\Card\Type;

class CardType
{
    // Credit card type is sent as C and debit as D respectively.
    public static $cardType = [
        Acquirer::BOB => [
            Type::CREDIT    => 'C',
            Type::DEBIT     => 'D',
            Type::UNKNOWN   => 'C',
            Type::PREPAID   => 'C',
        ],
        Acquirer::FSS   => [
            Type::CREDIT    => 'CP',
            Type::DEBIT     => 'DP',
            Type::UNKNOWN   => 'CP',
            Type::PREPAID   => 'C',
        ],
        Acquirer::SBI => [
            Type::CREDIT    => 'C',
            Type::DEBIT     => 'D',
            Type::UNKNOWN   => 'C',
            Type::PREPAID   => 'C',
        ],
    ];

    /**
     * Get Card types and it's values by acquirer
     * @param $acquirer
     *
     * @return mixed
     * @throws \RZP\Exception\LogicException
     */
    public static function getCardTypesByAcquirer($acquirer)
    {
        if (empty(self::$cardType[$acquirer]) === true)
        {
            throw new Exception\LogicException(
                'Unsupported acquirer for the gateway',
                null,
                [
                    'acquirer' => $acquirer,
                ]);
        }

        return self::$cardType[$acquirer];
    }
}
