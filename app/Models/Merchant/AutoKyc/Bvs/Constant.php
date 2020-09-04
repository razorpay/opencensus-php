<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

class Constant
{
    //
    // Request fields
    //
    const TYPE        = 'type';
    const DETAILS     = 'details';
    const IDENTIFIER  = 'identifier';
    const PAN_NUMBER  = 'pan_number';
    const NAME        = 'name';
    const ARTEFACT    = 'artefact';
    const ENRICHMENTS = 'enrichments';
    const RULES       = 'rules';
    const PLATFORM    = 'platform';
    const OWNER_ID    = 'owner_id';
    const OWNER_TYPE  = 'owner_type';
    const PROOFS      = 'proofs';
    const NOTES       = 'notes';

    //
    // Response fields
    //
    const VALIDATION_ID = 'validation_id';
    const STATUS        = 'status';

    //
    // Artefact types in BVS
    //
    const PERSONAL_PAN = 'personal_pan';
    const AADHAAR      = 'aadhaar';

    const PG       = 'pg';
    const MERCHANT = 'merchant';

    const ARTEFACT_TYPE = 'aretafct_type';

    const SUCCESS = 'success';
    const FAILURE = 'failure';
}
