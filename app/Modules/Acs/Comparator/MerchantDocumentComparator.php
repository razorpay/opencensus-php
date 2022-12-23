<?php

namespace RZP\Modules\Acs\Comparator;

use RZP\Constants\Metric;
use RZP\Trace\TraceCode;

class MerchantDocumentComparator extends Base
{
    protected $excludedKeys = [
        'created_at' => true,
        'updated_at' => true,
        'source' => true,
        'validation_id' => true,
        'metadata' => true,
        'ocr_verify' => true,
        'document_date' => true
    ];

    function __construct()
    {
        parent::__construct();
    }

}
