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
        'high'                   => 'C0KRNK0AF',
        // transactions_risk_4
        'high_4'                 => 'C2CD9RXKR',
        // transactions_highrisk
        'highrisk'               => 'C1NBL61NE',
        // transactions
        'low'                    => 'C04260LMZ',
        // transactions_lt_10
        'lt_10'                  => 'C2R9JBVED',
        // operations_log
        'operations_log'         => 'C0KUX9WSE',
        // reconciliation
        'reconciliation'         => 'C1GNPHC07',
        // reconciliation 2
        'reconciliation2'        => 'C847BUR61',
        // reconciliation_info
        'reconciliation_info'    => 'CAP0K6S5U',
        //recon_alerts
        'recon_alerts'           => 'CGXKVCMAL',
        // metrics-payments-recon
        'metrics-payments-recon' => 'CNKQG82K1',
        // transactions_risky
        'risky'                  => 'C0RL2C917',
        // settlements
        'settlements'            => 'C02LBK2D7',
        // settlement_alerts
        'settlement_alerts'      => 'C015MHZFY49',
        // tech_logs_verify
        'tech_logs_verify'       => 'C3AJ9V9EY',
        // subscriptions
        'subscriptions'          => 'C77PAU3JM',
        // virtual_accounts
        'virtual_accounts'       => 'C44FHBKC1',
        'virtual_accounts_log'   => 'C809AQYUC',
        'upi_transfer_logs'      => 'CVA3MH8NT',
        // BharatQR
        'bharatqr_logs'          => 'CHYL0H2DR',
        // tech_logs_mail
        'tech_logs_mail'         => 'C50JZ3S5T',
        // risk
        'risk'                   => 'C0SG9Q7TM',
        // operations
        'operations'             => 'C0KUX9WSE',
        // Fund Account Validation logs
        'fav_logs'               => 'CFQFNH3S7',
        //FTS logs
        'fts_alerts'             => 'CGJA83JUW',
        // tech_alerts channel
        'tech_alerts'            => 'C5FD7THSP',
        // pgob_alerts channel
        'pgob_alerts'            => 'CL985FWUX',
        // fta alerts
        'fta_alerts'             => 'CMLR1R6FJ',
        // irctc alerts
        'ops_irctc'              => 'C971JT8JC',
        // critical amount loading amount in Virtual account alert
        'x_finops'               => 'CSR546JHW',

        'payout_links_alerts'    => 'CT0D3HTBR',

        //rbl alerts
        'rbl_alerts'             => 'CUT37PDUY',
        //ivr_alerts channel
        'ivr_alerts'             => 'C010H5E2XCL',

        //rbl_ca_alerts
        'rx_ca_rbl_alerts'       => 'C010ZL4J9V3',

        //tech_payments_cards_alerts
        'card_payments_alert'    => 'CTB3BPENR',

        'rx_rbl_recon_alerts'    => 'C019AKLLQAH',

        //payout alerts
        'x-payouts-core-alerts'  => 'C01B8T2HUM7',

        // x alerts channel
        'x-alerts'               => 'CJD6RKF5Z',

        // ledger alerts channel
        'platform-ledger-alerts' => 'C01FW2MTBMZ',

        // slack alert channel for coupon expiry alerts
        'coupon_expiry_alerts'   => 'C034U2MAVCY',
        // p0_pp_alert
        'p0_pp_alerts'           => 'C02661NA20G',

        // growth alert channel
        'platform_growth_alerts' => 'C029XUXDE6S',

        'cb_invoice_verification_alerts' => 'C05BBKBHA67'
    ],

    'is_slack_enabled' => env('SLACK_MOCK') === true ? false : true,

    // Use non-default connection for slack,
    // if set to null, it will use the default connection
    'queue' => env('SLACK_QUEUE_DRIVER', null),
);
