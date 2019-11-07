<?php

namespace RZP\Models\Merchant\Detail;

class Constants
{
    // input params for pan verifier
    const PAN_NUMBER = 'pan_number';

    // pan verifier response types
    const INCORRECT_DETAILS = 'incorrect_details';
    const SUCCESS           = 'success';
    const FAILURE           = 'failure';

    const SIGNED_URL        = 'signed_url';
    const PASSPORT_FRONT    = 'passport_front';
    const AADHAR_FRONT      = 'aadhar_front';
    const VOTER_ID_FRONT    = 'voter_id_front';

    // penny testing constants
    const MERCHANT_ID     = 'merchant_id';
    const ACCOUNT_STATUS  = 'account_status';
    const REGISTERED_NAME = 'registered_name';
}
