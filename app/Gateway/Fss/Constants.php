<?php

namespace RZP\Gateway\Fss;

class Constants
{
    // Currency Code for FSS Transactions.
    const CURRENCY_CODE         = '356';

    const PURCHASE              = 'PURCHASE';

    // Credit card type is sent as C and debit as D respectively.
    const CREDIT_CARD_TYPE      = 'C';
    const DEBIT_CARD_TYPE       = 'D';

    // Actions have there own representation as per fss.
    const ACTION_PURCHASE       = '1';
    const ACTION_REFUND         = '2';
    const ACTION_INQUIRY        = '8';

    const LANGUAGE_USA          = 'USA';

    const TRACK_ID              = 'TrackID';

    const ERROR_MESSAGE_START   = 'IPAY';
}
