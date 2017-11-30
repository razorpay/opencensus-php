<?php

namespace RZP\Services\Aws;

use Config;
use Aws;

class Sns
{
    /**
     * @var Aws\AwsClientInterface
     */
    protected $client;

    /**
     * @var array
     */
    protected $awsConfig;

    public function __construct($app)
    {
        $this->awsConfig = Config::get('aws');

        $awsClient = new Aws\Sdk($this->awsConfig);

        $this->client = $awsClient->createClient('sns');
    }

    public function publish($message, $messageTarget = 'sms')
    {
        $this->client->publish(
            [
                'Message'   => $message,
                'TargetArn' => $this->awsConfig['sns_target_arn'][$messageTarget],
            ]);
    }
}
