<?php

use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use OpenCensus\Trace\Tracer;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



Route::get('/onboard', function () {
    // Creates a detached span
    $span = Tracer::startSpan(['name' => 'onboard-customer']);
    $scope = Tracer::withSpan($span);
    try {
        createUser($scope);
        incrementRedisCount($scope);
        pushMessageSQS($scope);
        makeClientCall($scope);

    } finally {
        // Closes the scope (ends the span)
        $scope->close();
    }
    makeAsyncClientCall();
    return '';
});


function pushMessageSQS($scope)
{
    Tracer::inSpan(['name' => 'SQS:FirstBroadcastCreatedUser'], function () {

        $client = new SqsClient([
            'region' => 'us-east-1',
            'version' => '2012-11-05',
            'credentials' => [
                'key'    => '',
                'secret' => '',
            ]
        ]);

        $params = [
            'DelaySeconds' => 10,
            'MessageAttributes' => [
                "Title" => [
                    'DataType' => "String",
                    'StringValue' => "The Hitchhiker's Guide to the Galaxy"
                ],
                "Author" => [
                    'DataType' => "String",
                    'StringValue' => "Douglas Adams."
                ],
                "WeeksOn" => [
                    'DataType' => "Number",
                    'StringValue' => "6"
                ]
            ],
            'MessageBody' => "Information about current NY Times fiction bestseller for week of 12/11/2016.",
            'QueueUrl' => 'http://localhost:4566/000000000000/test'
        ];

        try {
            $result = $client->sendMessage($params);
            var_dump($result);
        } catch (AwsException $e) {
            // output error message if fails
            error_log($e->getMessage());
        }


    });

    $span = Tracer::startSpan(['name' => 'SQS:SecondBroadcastCreatedUser']);
    $scope = Tracer::withSpan($span);
    try {

        $client = new SqsClient([
            'region' => 'us-east-1',
            'version' => '2012-11-05',
            'credentials' => [
                'key'    => '',
                'secret' => '',
            ]
        ]);

        $params = [
            'DelaySeconds' => 11,
            'MessageAttributes' => [
                "Title" => [
                    'DataType' => "String",
                    'StringValue' => "The Next book"
                ],
                "Author" => [
                    'DataType' => "String",
                    'StringValue' => "John Doe"
                ],
                "WeeksOn" => [
                    'DataType' => "Number",
                    'StringValue' => "3"
                ]
            ],
            'MessageBody' => "Information",
            'QueueUrl' => 'http://localhost:4566/000000000000/test'
        ];

        try {
            $result = $client->sendMessage($params);
            var_dump($result);
        } catch (AwsException $e) {
            // output error message if fails
            error_log($e->getMessage());
        }

    } finally {
        // Closes the scope (ends the span)
        $scope->close();
    }


}

function makeClientCall($span)
{
    $span = Tracer::startSpan(['name' => 'Sync:Guzzle:GETRepository']);
    $scope = Tracer::withSpan($span);
    $client = new \GuzzleHttp\Client();
    $response = $client->request('GET', 'https://google.com');
    $response->getStatusCode();
}

function incrementRedisCount($scope)
{

    $client =  new \Predis\Client([
        'host'   => 'localhost',
        'port'   => 6379,
    ]);

    $span = Tracer::startSpan(['name' => 'increment:liveUserCount']);
    $scope = Tracer::withSpan($span);
    try {
        $client -> incr('visits');
        Tracer::inSpan(['name' => 'Async:GETHelper'], function () {

            $client = new \GuzzleHttp\Client();
            $client->requestAsync('GET', 'https://google.com');
        });
    } finally {
        // Closes the scope (ends the span)
        $scope->close();
    }
}

function makeAsyncClientCall()
{
    $span = Tracer::inSpan(['name' => 'Async:Guzzle:GETRepository'], function () {
        $client = new \GuzzleHttp\Client();
        $client->requestAsync('GET', 'https://api.github.com/repos/guzzle/guzzle');
    });
}


function createUser($scope)
{
    $span = Tracer::startSpan(['name' => 'db:create:user']);
// Opens a scope that attaches the span to the current context
    $scope = Tracer::withSpan($span);
    try {
        DB::table('users')->insert([
            'name' => 'user1'.sha1(time()),
            'email' => 'user1'.sha1(time()).'1@email.com',
            'password' => bcrypt('password'),
        ]);
    } finally {
        // Closes the scope (ends the span)
        $scope->close();
    }
}
