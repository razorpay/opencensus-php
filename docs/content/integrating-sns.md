---
title: "Integrating OpenCensus with sns"
date: "2022-04-06"
type: page
menu:
  main:
    parent: "Integrations"
---

Integration with Aws sns using the following methods will:

## sns

To add OpenCensus support for sns Load in OpencensusProvider.php file

```php
<?php

            \OpenCensus\Trace\Integrations\Sns::load();

```

To add Spans

```php
<?php
use Aws\Sqs\SqsClient;


        ...
        Tracer::inSpan(['name' => 'SNS:example'], function () {
        ...
      $sdk = new \Aws\Sdk();

        $client = $sdk->createClient('sns', $args);

        $result = $client->publish(
            [
                'Message' => $push_json,
                'TargetArn' => 'arn:aws:sns:us-east-1:000000000000:test',
                'TopicArn' => "arn:aws:sns:us-east-1:000000000000:test"
            ])->toArray();

    });
```
