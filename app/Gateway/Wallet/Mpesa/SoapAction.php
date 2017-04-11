<?php

namespace RZP\Gateway\Wallet\Mpesa;

class SoapAction
{
    const QUERY_API                 = "<pay:queryPaymentTransaction />";
    const CUSTOMER_API              = "<pay:validateCustomer />";
}
