<?php

namespace RZP\Services\Aws;

use Aws;

use RZP\Services\Aws\Credentials;

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
        $this->awsConfig = $app->config->get('aws');

        $sdk = new Aws\Sdk($this->awsConfig);

        // See queue.php file for details.
        $args = [
            'credentials' => new Credentials\FileCache,
            'timeout'     => 3.0,
        ];

        $this->client = $sdk->createClient('sns', $args);
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
