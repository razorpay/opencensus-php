<?php

namespace RZP\Models\Merchant\Transformers;

use RZP\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Core;
use Selective\Transformer\ArrayTransformer;
use RZP\Models\Merchant\TLDExtract;

class WebsiteDetailTransformer extends Base\Transformer
{
    //Format of Each item in the rules array
    // input column name  1   => [
    //            [
    //                "column"    => output column name 1,
    //                'condition' => [ //used for row to column mapping - all are and conditions if met we will choose output column name 1 for input column name  1
    //                    input column name 1   => value 1,
    //                    input column name 2   => value 2,
    //             ],
    //                'function' => function name 1 // this is used for data conversion
    //            ]

    protected $rules = [
        'merchant_id'                  => [
            [
                "column" => 'id'
            ]
        ],
        'business_website'                      => [
            [
                "column" => 'website'
            ]
        ],
        'metadata.whitelisted_domains' => [
            [
                "column" => 'whitelisted_domains',
                "function" => 'transformWhitelistedDomain'
            ]
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    protected function registerFilters(ArrayTransformer $transformer)
    {
        $transformer->registerFilter(
            'transformWhitelistedDomain',
            function($value) {
                $this->trace->info(TraceCode::PGOS_DUAL_WRITE_REQUEST, [
                    'transformWhitelistedDomain' => 'merchant_whitelisted_domain',
                    'value' => $value,
                ]);
                $whitelistedDomains = [];
                if (empty($value) === false) {
                    foreach($value as $whitelistedDomain)
                    {
                        $whitelistedDomainModified = (new TLDExtract)->getEffectiveTLDPlusOne($whitelistedDomain);
                        $whitelistedDomains[] = $whitelistedDomainModified;
                    }
                }
                return $whitelistedDomains;
            }
        );
    }
}
