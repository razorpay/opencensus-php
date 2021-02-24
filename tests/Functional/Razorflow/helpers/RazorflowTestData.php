<?php

return [
    'testPostSlashCommandSuccess' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/razorflow',
            'content' => [
                'token'       => 'm6MAwf5O1oXqUJlugjveTJiT',
                'team_id'     => 'T017414GB09',
                'team_domain' => 'testbotworkspace',
                'channel_id'  => 'C016JL3HU86',
                'channel_name'=> 'project',
                'user_id'     => 'U016JL1EU1L',
                'user_name'   => 'test.user',
                'command'     => '/razorflow',
                'text'        => 'refund FMaxt4st5IyoiH',
                'response_url'=> 'https://hooks.slack.com/commands/T017414GB09/1314322752016/cb6reBxfOfNlqhXwwEzpFMvW',
                'trigger_id'  => '1289110708501.1242038555009.a78098c6241d6acc980db382534edf66'
            ],
        ],
    ],

    'testPostSlashCommandReplayFailure' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/razorflow',
            'content' => [
                'token'       => 'm6MAwf5O1oXqUJlugjveTJiT',
                'team_id'     => 'T017414GB09',
                'team_domain' => 'testbotworkspace',
                'channel_id'  => 'C016JL3HU86',
                'channel_name'=> 'project',
                'user_id'     => 'U016JL1EU1L',
                'user_name'   => 'test.user',
                'command'     => '/razorflow',
                'text'        => 'refund FMaxt4st5IyoiH',
                'response_url'=> 'https://hooks.slack.com/commands/T017414GB09/1314322752016/cb6reBxfOfNlqhXwwEzpFMvW',
                'trigger_id'  => '1289110708501.1242038555009.a78098c6241d6acc980db382534edf66'
            ],
        ],
    ],

    'testPostSlashCommandMissingSignature' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/razorflow',
            'content' => [
                'token'       => 'm6MAwf5O1oXqUJlugjveTJiT',
                'team_id'     => 'T017414GB09',
                'team_domain' => 'testbotworkspace',
                'channel_id'  => 'C016JL3HU86',
                'channel_name'=> 'project',
                'user_id'     => 'U016JL1EU1L',
                'user_name'   => 'test.user',
                'command'     => '/razorflow',
                'text'        => 'refund FMaxt4st5IyoiH',
                'response_url'=> 'https://hooks.slack.com/commands/T017414GB09/1314322752016/cb6reBxfOfNlqhXwwEzpFMvW',
                'trigger_id'  => '1289110708501.1242038555009.a78098c6241d6acc980db382534edf66'
            ],
        ],
    ],

    'testPostSlashCommandMissingTimestamp' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/razorflow',
            'content' => [
                'token'       => 'm6MAwf5O1oXqUJlugjveTJiT',
                'team_id'     => 'T017414GB09',
                'team_domain' => 'testbotworkspace',
                'channel_id'  => 'C016JL3HU86',
                'channel_name'=> 'project',
                'user_id'     => 'U016JL1EU1L',
                'user_name'   => 'test.user',
                'command'     => '/razorflow',
                'text'        => 'refund FMaxt4st5IyoiH',
                'response_url'=> 'https://hooks.slack.com/commands/T017414GB09/1314322752016/cb6reBxfOfNlqhXwwEzpFMvW',
                'trigger_id'  => '1289110708501.1242038555009.a78098c6241d6acc980db382534edf66'
            ],
        ],
    ],

    'testPostSlashCommandMissingInvalidSignature' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/razorflow',
            'content' => [
                'token'       => 'm6MAwf5O1oXqUJlugjveTJiT',
                'team_id'     => 'T017414GB09',
                'team_domain' => 'testbotworkspace',
                'channel_id'  => 'C016JL3HU86',
                'channel_name'=> 'project',
                'user_id'     => 'U016JL1EU1L',
                'user_name'   => 'test.user',
                'command'     => '/razorflow',
                'text'        => 'refund FMaxt4st5IyoiH',
                'response_url'=> 'https://hooks.slack.com/commands/T017414GB09/1314322752016/cb6reBxfOfNlqhXwwEzpFMvW',
                'trigger_id'  => '1289110708501.1242038555009.a78098c6241d6acc980db382534edf66'
            ],
        ],
    ],

    'testPostSlashCommandSuccessCustomEndpoint' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/razorflow/jira',
            'content' => [
                'token'       => 'm6MAwf5O1oXqUJlugjveTJiT',
                'team_id'     => 'T017414GB09',
                'team_domain' => 'testbotworkspace',
                'channel_id'  => 'C016JL3HU86',
                'channel_name'=> 'project',
                'user_id'     => 'U016JL1EU1L',
                'user_name'   => 'test.user',
                'command'     => '/razorflow',
                'text'        => 'refund FMaxt4st5IyoiH',
                'response_url'=> 'https://hooks.slack.com/commands/T017414GB09/1314322752016/cb6reBxfOfNlqhXwwEzpFMvW',
                'trigger_id'  => '1289110708501.1242038555009.a78098c6241d6acc980db382534edf66',
                'custom_endpoint' => 'jira'
            ],
        ],
    ],
];
