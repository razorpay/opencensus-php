<?php

namespace RZP\Tests\Functional\Razorflow;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class RazorflowTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RazorflowTestData.php';

        parent::setUp();
    }

    public function getSlackSignature($content, $timestamp, $signingSecret) : string
    {
        $signatureBaseString = 'v0:' . $timestamp . ':' . http_build_query($content);

        $mySignature = 'v0=' . hash_hmac('sha256', $signatureBaseString, $signingSecret);

        return $mySignature;
    }

    public function testPostSlashCommandSuccess()
    {
        $this->ba->directAuth();

        $currentTimestamp = Carbon::now()->getTimestamp();

        $slackSignature = $this->getSlackSignature(
            $this->testData[__FUNCTION__]['request']['content'],
            $currentTimestamp,
            'randomsigningsecret'
        );

        $this->testData[__FUNCTION__]['request']['headers'] = [
            'X-Slack-Request-Timestamp' => $currentTimestamp,
            'X-Slack-Signature' => $slackSignature
        ];

        $response = $this->sendRequest($this->testData[__FUNCTION__]['request']);

        $this->assertEquals('"Hello. Request accepted"', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostSlashCommandReplayFailure()
    {
        $this->ba->directAuth();

        $currentTimestamp = Carbon::now()->addSeconds(-301)->getTimestamp();

        $slackSignature = $this->getSlackSignature(
            $this->testData[__FUNCTION__]['request']['content'],
            $currentTimestamp,
            'randomsigningsecret'
        );

        $this->testData[__FUNCTION__]['request']['headers'] = [
            'X-Slack-Request-Timestamp' => $currentTimestamp,
            'X-Slack-Signature' => $slackSignature
        ];

        $response = $this->sendRequest($this->testData[__FUNCTION__]['request']);

        $this->assertEquals('"Invalid request"', $response->getContent());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPostSlashCommandMissingSignature()
    {
        $this->ba->directAuth();

        $currentTimestamp = Carbon::now()->getTimestamp();

        $slackSignature = $this->getSlackSignature(
            $this->testData[__FUNCTION__]['request']['content'],
            $currentTimestamp,
            'randomsigningsecret'
        );

        $this->testData[__FUNCTION__]['request']['headers'] = [
            'X-Slack-Request-Timestamp' => $currentTimestamp,
        ];

        $response = $this->sendRequest($this->testData[__FUNCTION__]['request']);

        $this->assertEquals('"Invalid request"', $response->getContent());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPostSlashCommandMissingTimestamp()
    {
        $this->ba->directAuth();

        $currentTimestamp = Carbon::now()->getTimestamp();

        $slackSignature = $this->getSlackSignature(
            $this->testData[__FUNCTION__]['request']['content'],
            $currentTimestamp,
            'randomsigningsecret'
        );

        $this->testData[__FUNCTION__]['request']['headers'] = [
            'X-Slack-Signature' => $slackSignature
        ];

        $response = $this->sendRequest($this->testData[__FUNCTION__]['request']);

        $this->assertEquals('"Invalid request"', $response->getContent());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPostSlashCommandMissingInvalidSignature()
    {
        $this->ba->directAuth();

        $currentTimestamp = Carbon::now()->getTimestamp();

        $slackSignature = $this->getSlackSignature(
            $this->testData[__FUNCTION__]['request']['content'],
            $currentTimestamp,
            'invalidsigningsecret'
        );

        $this->testData[__FUNCTION__]['request']['headers'] = [
            'X-Slack-Request-Timestamp' => $currentTimestamp,
            'X-Slack-Signature' => $slackSignature
        ];

        $response = $this->sendRequest($this->testData[__FUNCTION__]['request']);

        $this->assertEquals('"Invalid request"', $response->getContent());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPostSlashCommandSuccessCustomEndpoint()
    {
        $this->ba->directAuth();

        $currentTimestamp = Carbon::now()->getTimestamp();

        $slackSignature = $this->getSlackSignature(
            $this->testData[__FUNCTION__]['request']['content'],
            $currentTimestamp,
            'randomsigningsecret'
        );

        $this->testData[__FUNCTION__]['request']['headers'] = [
            'X-Slack-Request-Timestamp' => $currentTimestamp,
            'X-Slack-Signature' => $slackSignature
        ];

        $response = $this->sendRequest($this->testData[__FUNCTION__]['request']);

        $this->assertEquals('"Hello. Request accepted"', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }
}
