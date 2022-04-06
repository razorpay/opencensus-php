<?php

namespace OpenCensus\Trace\Integrations;

use Aws\Command;
use Aws\Sdk;
use Aws\Sns\SnsClient;
use PHPUnit\Framework\TestCase;

class SnsTest extends TestCase
{

    protected $args = array();

    public function &__get($name) {
        return $this->$name;
    }

    public function testHandleExecute()
    {
        $args = [
            'name' => 'Publish',
            'profile' => 'default',
            'region' => 'us-east-1',
            'version' => '2010-03-31',
            'endpoint' => "http://localhost:4566",
            'topic_arns'=> ["arn:aws:sns:us-east-1:000000000000:test"],
            'TopicArn' => 'arn:aws:sns:us-east-1:000000000000:test',
            'data' => [
                'Message'    => '',
                'TopicArn' => 'arn:aws:sns:us-east-1:000000000000:test',
            ]
        ];
        $cmd = new Command("Publish", ["TopicArn"=>"arn:aws:sns:us-east-1:000000000000:test"]);

        $sdk = new Sdk();

        $client = $sdk->createClient('sns', $args);

        $span = Sns::handleExecute($client, $cmd);

        $expected = array (
            'attributes' =>
                array (
                    'command' => 'Publish',
                    'span.kind' => 'client',
                    'sns.TargetArn' => '',
                    'sns.TopicArn' => 'arn:aws:sns:us-east-1:000000000000:test',
                    'sns.region' => 'us-east-1',
                    'sns.apiVersion' => '2010-03-31',
                    'sns.host' => 'localhost',
                ),
            'kind' => 'client',
            'name' => 'Sns',
            'sameProcessAsParentSpan' => false,
        );
        $this->assertEquals($expected, $span);

    }
}
