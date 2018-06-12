<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

use RZP\Gateway\Netbanking\Airtel;

// Domain for test/live and routes for authorize, verify and refund are same as Netbanking/Airtel
// So extending Netbanking/Airtel/Url class
class Url extends Airtel\Url
{

}
