<?php

return [
    // Background:
    // 1. We use query cache to fetch pricing plans from redis instead of mysql
    //    The query to redis is in the pattern:
    //      live:<hash>:10:{pricing_<plan_id>_}:<hash>
    // 2. Given that a single merchant id has the same plan and during high traffic from specific merchants, (eg: dream11 during ipl),
    // we repeatedly call GET on the same redis key
    // 3. This causes a higher load on the node where the key is stored
    // 4. To avoid this, we want to distribute the load amongst few nodes.
    // 5. To do this, we will be adding a random prefix to the GET key in the hope that load is distributed amongst the nodes
    'query_cache_distribution' => [
        'factor'              => (int)env("PRICING_QUERY_CACHE_LOAD_DISTRIBUTE_FACTOR", 10), // Generate prefix from [1-><distribution_factor>] inclusive
        'merchant_ids'        => explode(',', env("PRICING_QUERY_CACHE_LOAD_DISTRIBUTE_MERCHANT_IDS", '')) ?? [],
    ],

    'cod'  => [
        'default_rule_id' => env('COD_DEFAULT_RULE_ID', 'I6L2fFrSUiyfZD'),
    ],

    'IntlBankTransfer' => [
        'default_rule_id' => env('INTLTRANSFER_DEFAULT_RULE_ID','IntbnkTrnsfrId')
    ]
];
