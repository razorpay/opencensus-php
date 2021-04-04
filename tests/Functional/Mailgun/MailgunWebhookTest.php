<?php

namespace RZP\Tests\Functional\Mailgun;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Constants\MailTags;
use RZP\Constants\HashAlgo;
use Config;

class MailgunWebhookTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/MailgunWebhookTestData.php';

        parent::setUp();

        $this->ba->directAuth();
    }

    public function testValidEmailTag()
    {
        $testDataReplace = $this->getRequestVariableData(Config::get('applications.mailgun.key'));

        $this->startTest($testDataReplace);
    }

    public function testNoEmailTag()
    {
        $testDataReplace = $this->getRequestVariableData(Config::get('applications.mailgun.key'));

        $this->startTest($testDataReplace);
    }

    public function testEmailTagOutOfWebhookScope()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->assertNotContains($testData['request']['content'][MailTags::HEADER], MailTags::$setlNotifyTags);

        $testDataReplace = $this->getRequestVariableData(Config::get('applications.mailgun.key'));

        $this->startTest($testDataReplace);
    }

    public function testEmailBounce()
    {
        $testDataReplace = $this->getRequestVariableData(Config::get('applications.mailgun.key'));

        $this->startTest($testDataReplace);
    }

    public function testInvalidSignature()
    {
        $testData = $this->testData[__FUNCTION__];

        $testDataReplace = $this->getRequestVariableData('random_invalid_mailgun_key');

        $this->replaceValuesRecursively($testData, $testDataReplace);

        $this->runRequestResponseFlow($testData);
    }

    protected function getRequestVariableData($apiKey)
    {
        $timestamp = time();

        $token = '504f13d1b14cd999ca73f3019c4b0c938733768dc1011da105';

        $testData['request']['content'] = [
            'token'     => $token,
            'timestamp' => $timestamp,
            'signature' => hash_hmac(HashAlgo::SHA256, $timestamp . $token, $apiKey)
        ];

        return $testData;
    }
}
