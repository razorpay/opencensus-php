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
}
