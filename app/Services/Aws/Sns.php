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
    * @var Razorpay Logger
    */
    protected $trace;

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

        $this->trace = $app['trace'];
    }

    public function publish($message, $messageTarget = 'sms')
    {
        $result = $this->client->publish(
            [
                'Message'   => $message,
                'TargetArn' => $this->awsConfig['sns_target_arn'][$messageTarget],
            ])->toArray();

        $this->trace->info(TraceCode::AWS_SNS_PUBLISH_RESPONSE, $result);

        return $result;
    }
}
