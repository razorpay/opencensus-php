<?php

namespace RZP\Gateway\Fss;

class Fields
{
    const CARD              = 'card';

    const CVV               = 'cvv2';

    const CURRENCY_CODE     = 'currencycode';

    const EXPIRY_YEAR       = 'expyear';

    const EXPIRY_MONTH      = 'expmonth';

    // Transaction type Debit/Credit
    const TYPE              = 'type';

    // Card holders name.
    const MEMBER            = 'member';

    const AMOUNT            = 'amt';

    // Purchase, Credit, etc,
    const ACTION            = 'action';

    const TRACK_ID          = 'trackid';

    const ERROR_URL         = 'errorURL';

    const RESPONSE_URL      = 'responseURL';

    const ID                = 'id';

    const PASSWORD          = 'password';

    const REQUEST           = 'request';

    const UDF5              = 'udf5';
}