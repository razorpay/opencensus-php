<?php

namespace RZP\Models\Merchant\Detail;

class Constants
{
    // input params for pan verifier
    const PAN_NUMBER = 'pan_number';

    const POI_STATUS                       = 'poi_status';
    const POA_STATUS                       = 'poa_status';
    const DOCUMENT_TYPE                    = 'document_type';
    const BANK_DETAILS_VERIFICATION_STATUS = 'bank_details_verification_status';
    const EXTERNAL_VERIFIER                = 'external_verifier';

    // pan verifier response types
    const INCORRECT_DETAILS = 'incorrect_details';
    const SUCCESS           = 'success';
    const FAILURE           = 'failure';

    const SIGNED_URL             = 'signed_url';
    const PASSPORT_FRONT         = 'passport_front';
    const AADHAR_FRONT           = 'aadhar_front';
    const VOTER_ID_FRONT         = 'voter_id_front';
    const AADHAAR_FRONT_COMPLETE = 'aadhaar_front_complete';

    // penny testing constants

    const MERCHANT_ID                                   = 'merchant_id';
    const ACCOUNT_STATUS                                = 'account_status';
    const REGISTERED_NAME                               = 'registered_name';
    const FUZZY_MATCH_PERCENTAGE_WITH_PAN               = 'fuzzy_match_percentage_with_pan';
    const FUZZY_MATCH_PERCENTAGE_WITH_BANK_ACCOUNT_NAME = 'fuzzy_match_percentage_with_bank_account_name';
    const BANK_VERIFICATION_THRESHOLD_FOR_PAN           = 'bank_detail_verification_threshold_for_pan';
    const BANK_VERIFICATION_THRESHOLD_FOR_BANK_ACCOUNT  = 'bank_detail_verification_threshold_for_bank_account';

    // merchant verification
    const VERIFICATION    = 'verification';
    const REQUIRED_FIELDS = 'required_fields';

    const DUMMY_ACTIVATION_FILE = '100000000Dummy';
}
