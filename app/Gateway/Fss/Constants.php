<?php

namespace RZP\Gateway\Fss;

use RZP\Models\Card\Type as CardType;

class Constants
{
    const PURCHASE              = 'PURCHASE';

    // Credit card type is sent as C and debit as D respectively.
    public static $cardType = [
        Acquirer::BOB => [
            CardType::CREDIT    => 'C',
            CardType::DEBIT     => 'D',
            CardType::UNKNOWN   => 'C',
        ],
        Acquirer::FSS   => [
            CardType::CREDIT    => 'CP',
            CardType::DEBIT     => 'DP',
            CardType::UNKNOWN   => 'CP',
        ],
    ];

    // Actions have there own representation as per fss.
    const ACTION_PURCHASE       = '1';
    const ACTION_REFUND         = '2';
    const ACTION_INQUIRY        = '8';

    const LANGUAGE_USA          = 'USA';

    const TRACK_ID              = 'TrackID';

    const ERROR_MESSAGE_START   = 'IPAY';
}
