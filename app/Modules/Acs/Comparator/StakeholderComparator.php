<?php

namespace RZP\Modules\Acs\Comparator;

class StakeholderComparator extends Base {
    protected $excludedKeys = [
        "poi_status" => true,
        "poa_status" => true,
        "pan_doc_status" => true,
        "aadhaar_esign_status" => true,
        "aadhaar_verification_with_pan_status" => true,
        "bvs_probe_id" => true,
        "created_at" => true,
        "updated_at" => true
    ];

    function __construct()
    {
        parent::__construct();
    }
}
