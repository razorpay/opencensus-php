<?php

namespace RZP\Tests\Functional\Mailgun;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Constants\MailTags;

class MailgunWebhookTest extends TestCase
{
    use RequestResponseFlowTrait;
    
    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/MailgunWebhookTestData.php';
        
        parent::setUp();
        
        $this->ba->appAuth();
    }
    
    public function testValidEmailTag()
    {
        $this->startTest();
    }
    
    public function testNoEmailTag()
    {
        $this->startTest();
    }
    
    public function testEmailTagOutOfWebhookScope()
    {
        $testData = $this->testData[__FUNCTION__];
        
        $this->assertNotContains($testData['request']['content']['X-Mailgun-Tag'], MailTags::$notifyTags);
        
        $this->startTest();
    }
}
