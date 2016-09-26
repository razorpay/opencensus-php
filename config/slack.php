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

    'channel' => '#transactions',

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

    'channels'  =>  [
        'low'            => '#transactions',
        'high'           => '#transactions_high',
        'high_4'         => '#transactions_risk_4',
        'risky'          => '#transactions_risky',
        'reconciliation' => '#reconciliation',
        'highrisk'       => '#transactions_highrisk',
        'tech_logs'      => '#tech_logs',
    ],

    'is_slack_enabled' => env('SLACK_MOCK') === true ? false : true
);
