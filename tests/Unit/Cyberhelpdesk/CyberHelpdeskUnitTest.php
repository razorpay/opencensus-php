<?php

namespace Unit\Cyberhelpdesk;

use RZP\Tests\Functional\TestCase;
use RZP\Models\CyberCrimeHelpDesk\Service;

class CyberHelpdeskUnitTest extends TestCase
{
    public function testGetOutboundEmailForMerchant()
    {

        $cyberHelpdeskService = new Service();

        $testCases = [
            [
                'request'  => [
                    'merchant_emails' =>
                        [
                            'type'  => 'chargeback',
                            'email' => 'test@razorpay.com',
                        ],

                ],
                'response' => [
                    'cc_emails' => null,
                    'email'     => 'test@razorpay.com'
                ]
            ],
            [
                'request'  => [
                    'merchant_emails' =>
                        [
                            'type'  => 'chargeback',
                            'email' => 'test1@razorpay.com,test2@razorpay.com',
                        ],

                ],
                'response' => [
                    'cc_emails' => ['test2@razorpay.com'],
                    'email'     => 'test1@razorpay.com'
                ]
            ],
            [
                'request'  => [
                    'merchant_emails' =>
                        null

                ],
                'response' => [
                    'cc_emails' => null,
                    'email'     => null
                ]
            ],
        ];

        foreach ($testCases as $testCase)
        {
            $merchant = $this->fixtures->merchant->create();

            if (empty($testCase['request']['merchant_emails']) === false)
            {
                $testCase['request']['merchant_emails']['merchant_id'] = $merchant->getId();

                $this->fixtures->create('merchant_email', $testCase['request']['merchant_emails']);
            }

            $response = $this->invokeMethod($cyberHelpdeskService, 'getOutboundEmailBody', [$merchant, []]);

            if (empty($testCase['response']['cc_emails']) === true)
            {
                $this->assertNull($response['cc_emails']);
            }
            else
            {
                $this->assertEquals($testCase['response']['cc_emails'], $response['cc_emails']);
            }

            if (empty($testCase['response']['email']) === true)
            {
                $this->assertEquals($merchant->getEmail(), $response['email']);
            }
            else
            {
                $this->assertEquals($testCase['response']['email'], $response['email']);
            }

        }

    }

    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method     = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
