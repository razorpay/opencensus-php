<?php

namespace OpenCensus\Trace\Integrations;

use Aws\Command;
use Aws\Sqs\SqsClient;
use PHPUnit\Framework\TestCase;

class SqsTest extends TestCase
{

    public function testHandleConstruct()
    {
        $command = new SqsClient([
            'region' => 'us-east-1',
            'version' => '2012-11-05',
            'credentials' => [
                'key'    => '',
                'secret' => '',
            ]
        ]);
        $args = [

                'region' => 'us-east-1',
                'version' => '2012-11-05',
                'credentials' =>
                     [
                        'key' => '',
                        'secret' => '',
                    ],

            ];

        $span = Sqs::handleConstruct($command, $args);

        $expected = array (
            'attributes' =>
                array (
                    'sqs.region' => 'us-east-1',
                    'sqs.apiVersion' => '2012-11-05',
                    'sqs.host' => 'sqs.us-east-1.amazonaws.com',
                    'service.name' => 'sqs',
                ),
            'kind' => 'client',
            'name' => 'Sqs:Constructor',
            'sameProcessAsParentSpan' => false,
        );

        $this->assertEquals($expected, $span);

    }

    public function testHandleExecute()
    {

        $command = new SqsClient([
            'region' => 'us-east-1',
            'version' => '2012-11-05',
            'credentials' => [
                'key'    => '',
                'secret' => '',
            ]
        ]);
        $args = [
            'DelaySeconds' => 10,
            'QueueUrl' => "http://localhost:4566/000000000000/test"

        ];
        $argss = new Command("SendMessage", $args);
        $span = Sqs::handleExecute($command, $argss);

        $expected = array (
            'attributes' =>
                array (
                    'command' => 'SendMessage',
                    'sqs.QueueUrl' => 'http://localhost:4566/000000000000/test',
                    'sqs.DelaySeconds' => 10,
                    'sqs.region' => 'us-east-1',
                    'sqs.apiVersion' => '2012-11-05',
                    'sqs.host' => 'sqs.us-east-1.amazonaws.com',
                    'service.name' => 'sqs',
                    'span.kind' => 'client',
                ),
            'kind' => 'client',
            'name' => 'Sqs SendMessage',
            'sameProcessAsParentSpan' => false,
        );

        $this->assertEquals($expected, $span);

    }

}
