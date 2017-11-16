<?php

return array(
    /*
    |-------------------------------------------------------------
    | Incoming webhook endpoint
    |-------------------------------------------------------------
    |
    | The endpoint which Slack generates when creating a
    | new incoming webhook. It will look something like
    | https://hooks.slack.com/services/XXXXXXXX/XXXXXXXX/XXXXXXXXXXXXXX
    |
    */

    'endpoint' => 'https://hooks.slack.com/services/T0276T56F/B02LD3J64/'.env('SLACK_TOKEN'),

    /*
    |-------------------------------------------------------------
    | Default channel
    |-------------------------------------------------------------
    |
    | The default channel we should post to. The channel can either be a
    | channel like #general, a private #group, or a @username. Set to
    | null to use the default set on the Slack webhook
    |
    */

    // #transactions
    'channel' => 'C04260LMZ',

    /*
    |-------------------------------------------------------------
    | Default username
    |-------------------------------------------------------------
    |
    | The default username we should post as. Set to null to use
    | the default set on the Slack webhook
    |
    */

    'username' => 'transactions',

    /*
    |-------------------------------------------------------------
    | Default icon
    |-------------------------------------------------------------
    |
    | The default icon to use. This can either be a URL to an image or Slack
    | emoji like :ghost: or :heart_eyes:. Set to null to use the default
    | set on the Slack webhook
    |
    */

    'icon' => ':moneybag:',

    /*
    |-------------------------------------------------------------
    | Link names
    |-------------------------------------------------------------
    |
    | Whether names like @regan should be converted into links
    | by Slack
    |
    */

    'link_names' => true,

    /*
    |-------------------------------------------------------------
    | Unfurl links
    |-------------------------------------------------------------
    |
    | Whether Slack should unfurl links to text-based content
    |
    */

    'unfurl_links' => true,

    /*
    |-------------------------------------------------------------
    | Unfurl media
    |-------------------------------------------------------------
    |
    | Whether Slack should unfurl links to media content such
    | as images and YouTube videos
    |
    */

    'unfurl_media' => true,

    /*
    |-------------------------------------------------------------
    | Markdown in message text
    |-------------------------------------------------------------
    |
    | Whether message text should be interpreted in Slack's Markdown-like
    | language. For formatting options, see Slack's help article: http://goo.gl/r4fsdO
    |
    */

    'allow_markdown' => true,

    /*
    |-------------------------------------------------------------
    | Markdown in attachments
    |-------------------------------------------------------------
    |
    | Which attachment fields should be interpreted in Slack's Markdown-like
    | language. By default, Slack assumes that no fields in an attachment
    | should be formatted as Markdown.
    |
    */

    // 'markdown_in_attachments' => [],

    // Allow Markdown in just the text and title fields
    // 'markdown_in_attachments' => ['text', 'title']

    // Allow Markdown in all fields
    'markdown_in_attachments' => ['pretext', 'text', 'title', 'fields', 'fallback'],

    // Reference for Slack Channel IDs:
    // https://github.com/razorpay/api/wiki/Slack-Channel-IDs
    'channels' => [
        // transactions_high
        'high'                 => 'C0KRNK0AF',
        // transactions_risk_4
        'high_4'               => 'C2CD9RXKR',
        // transactions_highrisk
        'highrisk'             => 'C1NBL61NE',
        // transactions
        'low'                  => 'C04260LMZ',
        // transactions_lt_10
        'lt_10'                => 'C2R9JBVED',
        // operations_log
        'operations_log'       => 'C0KUX9WSE',
        // reconciliation
        'reconciliation'       => 'C1GNPHC07',
        // transactions_risky
        'risky'                => 'C0RL2C917',
        // settlements
        'settlements'          => 'C02LBK2D7',
        // tech_logs
        'tech_logs'            => 'C0E2Q6MJM',
        // tech_logs_verify
        'tech_logs_verify'     => 'C3AJ9V9EY',
        // subscriptions
        'subscriptions'        => 'C77PAU3JM',
        // virtual_accounts
        'virtual_accounts'     => 'C44FHBKC1',
        'virtual_accounts_log' => 'C809AQYUC',
        // tech_logs_mail
        'tech_logs_mail'       => 'C50JZ3S5T',
        // activations
        'activations'          => 'C17UC7DHS',
        // risk
        'risk'                 => 'C0SG9Q7TM',
        // operations
        'operations'           => 'C0KUX9WSE',
        // Product (Feature) onboarding requests
        'activations_prod_log' => 'C76P70Y7K'
    ],

    'is_slack_enabled' => env('SLACK_MOCK') === true ? false : true,

    // Use non-default connection for slack,
    // if set to null, it will use the default connection
    'queue' => env('SECONDARY_QUEUE_DRIVER', null),
);
