<?php

namespace Unit\Cyberhelpdesk;

use RZP\Notifications\Dashboard\Constants as DashboardConstants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\CyberCrimeHelpDesk\Service;
use RZP\Models\CyberCrimeHelpDesk\Constants;

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
    public function testSendWhatsAppNotificationForMerchant()
    {
        $cyberHelpdeskService = new Service();

        $testCases = [
            [
                'request' => [
                    'whatsappTemplateName'  => Constants::CYBER_HELPDESK_WHATSAPP_TEMPLATE_NAME,
                    'whatsappTemplate'      => Constants::CYBER_HELPDESK_WHATSAPP_TEMPLATE_TEXT,
                    'params'                => ['order_id' => '12345'],
                    'attachmentData'        => [
                        DashboardConstants::PUBLIC_FILE_URL       => '',
                        DashboardConstants::DISPLAY_NAME          => Constants::CYBER_HELPDESK_WHATSAPP_TEMPLATE_HEADER,
                        DashboardConstants::EXTENSION             => Constants::PDF,
                        DashboardConstants::MSG_TYPE              => Constants::DOCUMENT,
                        DashboardConstants::IS_CTA_TEMPLATE       => true,
                        DashboardConstants::BUTTON_URL_PARAM      => '',
                    ],
                    'receiver'              => '1234567890',
                ],
                'response' => [
                    'status'  => 'success',
                    'message' => 'Message sent successfully',
                ],
            ],
            [
                'request' => [
                    'whatsappTemplateName' => 'error_notification',
                    'whatsappTemplate'     => null,
                    'params'               => [],
                    'attachmentData'       => [
                        DashboardConstants::PUBLIC_FILE_URL       => '',
                        DashboardConstants::DISPLAY_NAME          => Constants::CYBER_HELPDESK_WHATSAPP_TEMPLATE_HEADER,
                        DashboardConstants::EXTENSION             => Constants::PDF,
                        DashboardConstants::MSG_TYPE              => Constants::DOCUMENT,
                        DashboardConstants::IS_CTA_TEMPLATE       => true,
                        DashboardConstants::BUTTON_URL_PARAM      => '',
                    ],
                    'receiver'             => '1234567890',
                ],
                'response' => [
                    'status'  => 'error',
                    'message' => 'Message content cannot be empty',
                ],
            ],
            [
                'request' => [
                    'whatsappTemplateName' => 'order_update',
                    'whatsappTemplate'     => 'Your order has been shipped.',
                    'params'               => [],
                    'attachmentData'       => [
                        DashboardConstants::PUBLIC_FILE_URL       => '',
                        DashboardConstants::DISPLAY_NAME          => Constants::CYBER_HELPDESK_WHATSAPP_TEMPLATE_HEADER,
                        DashboardConstants::EXTENSION             => Constants::PDF,
                        DashboardConstants::MSG_TYPE              => Constants::DOCUMENT,
                        DashboardConstants::IS_CTA_TEMPLATE       => true,
                        DashboardConstants::BUTTON_URL_PARAM      => '',
                    ],
                    'receiver'             => '1234567890',
                ],
                'response' => [
                    'status'  => 'error',
                    'message' => 'Merchant ID is required',
                ],
            ],
        ];

        foreach ($testCases as $testCase)
        {
            $merchant = $this->fixtures->merchant->create();
            $dataForPDF = [
                $merchant = $merchant->getName(),
            ];
            if (!empty($testCase['request']['merchant_id'])) {
                $merchant = $this->fixtures->merchant->create($testCase['request']['merchant_id']);
            }

            $response = $this->invokeMethod(
                $whatsappService,
                'sendWhatsappMessageWithPDF',
                [
                    $merchant,
                    $testCase['request']['whatsappTemplateName'],
                    $testCase['request']['whatsappTemplate'],
                    $dataForPDF,
                    $testCase['request']['params'],
                    $testCase['request']['attachmentData'],
                    $testCase['request']['receiver'],
                ]
            );

            $this->assertEquals($testCase['response']['status'], $response['status']);
            $this->assertEquals($testCase['response']['message'], $response['message']);
        }
    }


}
