<?php

namespace RZP\Modules\Acs\Comparator;

class MerchantBusinessDetailComparator extends Base
{
    protected $excludedKeys = [
        'created_at' => true,
        'updated_at' => true,
        'website_details' => true,
        'audit_id' => true,
        'blacklisted_products_category' => true,
        'plugin_details' => true,
        'onboarding_source' => true,
        'lead_score_components' => true,
        'pg_use_case' => true,
        'id' => true,
        'gst_details' => true,
        'appsflyer_id' => true
    ];

    function __construct()
    {
        parent::__construct();
    }
}
