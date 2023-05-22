<?php

namespace RZP\Services\Aws;

use Aws;

use RZP\Services\Aws\Credentials;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;

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

        $this->trace = $app['trace'];

        // See queue.php file for details.
        $args = [
            'credentials' => new Credentials\FileCache,
            'timeout'     => 3.0,
            'http'        => [
                'timeout' => 1.0,
            ],
        ];

        //change the sns endpoint to localstack when the app mode is devserve
        // the bvt settings added for settlement service with parameter USE_LOCALSTACK
        if (env('APP_MODE') === 'devserve' || env('USE_LOCALSTACK') === true)
        {
            $args['endpoint'] = 'https://localstack-services.dev.razorpay.in';
        }

        $this->client = $sdk->createClient('sns', $args);
    }

    public function publish($message, $messageTarget = 'sms')
    {
        $arn = $this->awsConfig['sns_target_arn'][$messageTarget];

        $mode = app('rzp.mode') ?? Mode::LIVE;

        $result = $this->client->publish(
            [
                'Message'   => $message,
                'TargetArn' => $arn[$mode],
            ])->toArray();

        if (isset($result['@metadata']['statusCode']) === true)
        {
            if (intval($result['@metadata']['statusCode']) != 200)
            {
                $this->trace->info(TraceCode::AWS_SNS_PUBLISH_RESPONSE, $result);
            }
        }

        return $result;
    }
}
