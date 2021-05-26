<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;


class Bank_account extends BaseConfig
{
    protected $enrichment = [
        'online_provider' => [
            'required_fields' => [
                'account_number',
                'ifsc',
                'account_holder_names',
            ],
        ],
    ];

    protected $enrichmentDetails = [
        "online_provider.details.account_holder_names",
        "online_provider.details.account_status.value",
    ];
}
