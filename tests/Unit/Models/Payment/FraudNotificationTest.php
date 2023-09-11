<?php

namespace Unit\Models\Payment;

use RZP\Models\Payment\Fraud;
use RZP\Models\Dispute\Service;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Merchant\RiskMobileSignupHelper;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;
use RZP\Models\Payment\Analytics\Entity as PaymentAnalyticsEntity;


class FraudNotificationTest extends TestCase
{
    use MocksSplitz;

    use FreshdeskTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function setUpSalesforceMock(): void
    {
        $this->salesforceMock = \Mockery::mock('RZP\Services\SalesForceClient', $this->app)->makePartial();

        $this->salesforceMock->shouldAllowMockingProtectedMethods();

        $this->app['salesforce'] = $this->salesforceMock;
    }

    protected function mockSalesforceRequestforSalesPOC($expectedMerchantId, $expectedResponse,$times=1): void
    {
        $this->salesforceMock->shouldReceive('getSalesPOCForMerchantID')
            ->times($times)
            ->with(\Mockery::on(function($actualMerchantId) use ($expectedMerchantId) {
                return $actualMerchantId == $expectedMerchantId;
            }))
            ->andReturnUsing(function() use ($expectedResponse) {
                return $expectedResponse;
            });

    }

    public function testNotifyMerchantForRandomErrorCode()
    {
        $merchant = $this->fixtures->create('merchant', [
            'activated' => 1,
            'live'      => 1,
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant['id'],
        ]);

        $payment = $this->fixtures->create('payment', [
            'merchant_id' => $merchant['id'],
        ]);

        $response = (new Fraud\Notify())->notifyMerchantIfNeeded($merchant, $payment, 'RANDOM_ERROR_CODE');

        $this->assertNull($response);
    }

    public function testNotifyMerchantForUrlMismatchFraudAndUnreisteredMerchant()
    {
        $merchant = $this->fixtures->create('merchant', [
            'activated' => 1,
            'live'      => 1,
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant['id'],
            'business_type'     => '2',
        ]);

        $payment = $this->fixtures->create('payment', [
            'merchant_id' => $merchant['id'],
        ]);

        $response = (new Fraud\Notify())->notifyMerchantIfNeeded($merchant, $payment, 'RANDOM_ERROR_CODE');

        $this->assertNull($response);
    }

    //email signup - DomainMismatch - channel email
    public function testNotifyMerchantForUrlMismatchExperimentEnabledExistingTicketInWocStatusReplyOnExistingTicket()
    {
        $merchant = $this->fixtures->create('merchant', [
            'activated' => 1,
            'live'      => 1,
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant['id'],
        ]);

        $payment = $this->fixtures->create('payment', [
            'merchant_id' => $merchant['id'],
        ]);

        $paymentAnalytics = $this->fixtures->create('payment_analytics', [
            'payment_id' => $payment['id'],
            'referer'    => 'http://www.abc.com'
        ]);

        $payment->setMetadataKey('payment_analytics', $paymentAnalytics);

        $this->setUpSalesforceMock();

        $this->setUpFreshdeskClientMock();

        $this->mockSalesforceRequestforSalesPOC($merchant['id'], 'abc@rzp.com');

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3A%27' . $merchant['id'] . '%27+AND+custom_string%3A%27www.abc.com%27+AND+custom_string%3A%27Website+Mismatch%27%22&page=1',
            'get',
            [],
            [
                'total'   => 1,
                'results' => [
                    [
                        'id'     => '12',
                        'body'   => 'some random body 12',
                        'status' => 6,
                        'type'   => 'Service request',
                        'tags'   => ['website_mismatch'],
                        'custom_fields' =>  [
                            'cf_ticket_queue'           => 'Merchant',
                            'cf_category'               => 'Risk Report_Merchant',
                            'cf_subcategory'            => 'Website Mismatch',
                            'cf_product'                => 'Payment Gateway',
                            'cf_merchant_id'            => $merchant['id'],
                            'cf_website_url'            => 'www.abc.com',
                            'cf_new_requester_category' => 'Razorpay',
                        ],
                    ],
                ],
            ]
        );

        $emailPayload = [
            'payment' => [
                'id'             => $payment->getId(),
                'referer_url'    => 'http://www.abc.com',
                'referer_domain' => 'www.abc.com'
            ],
            'merchant' => [
                'id'    => $merchant->getId(),
                'name'  => $merchant->getName(),
                'email' => $merchant->getEmail()
            ]
        ];

        $mailBody = \View::make('emails.payment.fraud.domain_mismatch', $emailPayload)->render();

        $this->expectFreshdeskRequestAndRespondWith('tickets/12/reply',
            'post',
            [
                'body'      => $mailBody,
                'cc_emails' => ['abc@rzp.com']
            ],
            [
                'id'        => 123,
                'ticket_id' => '12',
            ]
        );

        $splitzInput = [
            'experiment_id' => 'MYGLSjyh1SkQNa',
            'id'            => $merchant['id'],
        ];

        $splitzOutput = [
            'response' => [
                'variant' => [
                    'name' => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        (new Fraud\Notify())->notifyMerchantIfNeeded($merchant, $payment, 'BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_WEBSITE_MISMATCH');
    }

    //email signup - DomainMismatch - channel email
    public function testNotifyMerchantForUrlMismatchExperimentEnabledNoExistingTicketCreateNewTicket()
    {
        $merchant = $this->fixtures->create('merchant', [
            'activated' => 1,
            'live'      => 1,
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant['id'],
        ]);

        $payment = $this->fixtures->create('payment', [
            'merchant_id' => $merchant['id'],
        ]);

        $paymentAnalytics = $this->fixtures->create('payment_analytics', [
            'payment_id' => $payment['id'],
            'referer'    => 'http://www.abc.com'
        ]);

        $payment->setMetadataKey('payment_analytics', $paymentAnalytics);

        $this->setUpSalesforceMock();

        $this->setUpFreshdeskClientMock();

        $this->mockSalesforceRequestforSalesPOC($merchant['id'], 'abc@rzp.com');

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3A%27' . $merchant['id'] . '%27+AND+custom_string%3A%27www.abc.com%27+AND+custom_string%3A%27Website+Mismatch%27%22&page=1',
            'get',
            [],
            [
                'total'   => 0,
                'results' => []
            ]
        );

        $emailPayload = [
            'payment' => [
                'id'             => $payment->getId(),
                'referer_url'    => 'http://www.abc.com',
                'referer_domain' => 'www.abc.com'
            ],
            'merchant' => [
                'id'    => $merchant->getId(),
                'name'  => $merchant->getName(),
                'email' => $merchant->getEmail()
            ]
        ];

        $mailBody = \View::make('emails.payment.fraud.domain_mismatch', $emailPayload)->render();

        $mailSubject = '[IMP] Payment failed due to  attempts from unregistered website for MID - ' . $merchant['id'];

        $expectedContent = [
            'description'     => $mailBody,
            'subject'         => $mailSubject,
            'group_id'        => 82000147768,
            'email_config_id' => 82000098661,
            'tags'            => ['website_mismatch'],
            'priority'        => 1,
            'status'          => 6,
            'type'            => 'Service request',
            'email'           => $merchant->getEmail(),
            'custom_fields'   => [
                'cf_new_requester_category'     => 'Razorpay',
                'cf_ticket_queue'               => 'Merchant',
                'cf_category'                   => 'Risk Report_Merchant',
                'cf_subcategory'                => 'Website Mismatch',
                'cf_product'                    => 'Payment Gateway',
                'cf_merchant_id'                => $merchant['id'],
                'cf_website_url'                => 'www.abc.com',
            ],
            'cc_emails' => [
                'abc@rzp.com'
            ],
        ];

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email',
            'post',
            $expectedContent,
            [
                'id'     => '12',
                'body'   => 'some random body 12',
                'status' => 6,
                'type'   => 'Service request',
                'tags'   => ['website_mismatch'],
                'custom_fields' =>  [
                    'cf_ticket_queue'           => 'Merchant',
                    'cf_category'               => 'Risk Report_Merchant',
                    'cf_subcategory'            => 'Website Mismatch',
                    'cf_product'                => 'Payment Gateway',
                    'cf_merchant_id'            => $merchant['id'],
                    'cf_website_url'            => 'www.abc.com',
                    'cf_new_requester_category' => 'Razorpay',
                ],
            ]
        );

        $splitzInput = [
            'experiment_id' => 'MYGLSjyh1SkQNa',
            'id'            => $merchant['id'],
        ];

        $splitzOutput = [
            'response' => [
                'variant' => [
                    'name' => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        (new Fraud\Notify())->notifyMerchantIfNeeded($merchant, $payment, 'BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_WEBSITE_MISMATCH');
    }

    //email signup - DomainMismatch - channel email
    public function testNotifyMerchantForUrlMismatchExperimentEnabledExistingTicketInClosedStatusCreateNewTicket()
    {
        $merchant = $this->fixtures->create('merchant', [
            'activated' => 1,
            'live'      => 1,
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant['id'],
        ]);

        $payment = $this->fixtures->create('payment', [
            'merchant_id' => $merchant['id'],
        ]);

        $paymentAnalytics = $this->fixtures->create('payment_analytics', [
            'payment_id' => $payment['id'],
            'referer'    => 'http://www.abc.com'
        ]);

        $payment->setMetadataKey('payment_analytics', $paymentAnalytics);

        $this->setUpSalesforceMock();

        $this->setUpFreshdeskClientMock();

        $this->mockSalesforceRequestforSalesPOC($merchant['id'], 'abc@rzp.com');

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3A%27' . $merchant['id'] . '%27+AND+custom_string%3A%27www.abc.com%27+AND+custom_string%3A%27Website+Mismatch%27%22&page=1',
            'get',
            [],
            [
                'total'   => 1,
                'results' => [
                    [
                        'id'     => '12',
                        'body'   => 'some random body 12',
                        'status' => 5,
                        'type'   => 'Service request',
                        'tags'   => ['website_mismatch'],
                        'custom_fields' =>  [
                            'cf_ticket_queue'           => 'Merchant',
                            'cf_category'               => 'Risk Report_Merchant',
                            'cf_subcategory'            => 'Website Mismatch',
                            'cf_product'                => 'Payment Gateway',
                            'cf_merchant_id'            => $merchant['id'],
                            'cf_website_url'            => 'www.abc.com',
                            'cf_new_requester_category' => 'Razorpay',
                        ],
                    ],
                ],
            ]
        );

        $emailPayload = [
            'payment' => [
                'id'             => $payment->getId(),
                'referer_url'    => 'http://www.abc.com',
                'referer_domain' => 'www.abc.com'
            ],
            'merchant' => [
                'id'    => $merchant->getId(),
                'name'  => $merchant->getName(),
                'email' => $merchant->getEmail()
            ]
        ];

        $mailBody = \View::make('emails.payment.fraud.domain_mismatch', $emailPayload)->render();

        $mailSubject = '[IMP] Payment failed due to  attempts from unregistered website for MID - ' . $merchant['id'];

        $expectedContent = [
            'description'     => $mailBody,
            'subject'         => $mailSubject,
            'group_id'        => 82000147768,
            'email_config_id' => 82000098661,
            'tags'            => ['website_mismatch'],
            'priority'        => 1,
            'status'          => 6,
            'type'            => 'Service request',
            'email'           => $merchant->getEmail(),
            'custom_fields'   => [
                'cf_new_requester_category'     => 'Razorpay',
                'cf_ticket_queue'               => 'Merchant',
                'cf_category'                   => 'Risk Report_Merchant',
                'cf_subcategory'                => 'Website Mismatch',
                'cf_product'                    => 'Payment Gateway',
                'cf_merchant_id'                => $merchant['id'],
                'cf_website_url'                => 'www.abc.com',
            ],
            'cc_emails' => [
                'abc@rzp.com'
            ],
        ];

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email',
            'post',
            $expectedContent,
            [
                'id'     => '12',
                'body'   => 'some random body 12',
                'status' => 6,
                'type'   => 'Service request',
                'tags'   => ['website_mismatch'],
                'custom_fields' =>  [
                    'cf_ticket_queue'           => 'Merchant',
                    'cf_category'               => 'Risk Report_Merchant',
                    'cf_subcategory'            => 'Website Mismatch',
                    'cf_product'                => 'Payment Gateway',
                    'cf_merchant_id'            => $merchant['id'],
                    'cf_website_url'            => 'www.abc.com',
                    'cf_new_requester_category' => 'Razorpay',
                ],
            ]
        );

        $splitzInput = [
            'experiment_id' => 'MYGLSjyh1SkQNa',
            'id'            => $merchant['id'],
        ];

        $splitzOutput = [
            'response' => [
                'variant' => [
                    'name' => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        (new Fraud\Notify())->notifyMerchantIfNeeded($merchant, $payment, 'BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_WEBSITE_MISMATCH');
    }

    //email signup - DomainMismatch - channel email
    public function testNotifyMerchantForUrlMismatchExperimentDisabledCreateNewTicket()
    {
        $merchant = $this->fixtures->create('merchant', [
            'activated' => 1,
            'live'      => 1
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant['id'],
        ]);

        $payment = $this->fixtures->create('payment', [
            'merchant_id' => $merchant['id'],
        ]);

        $paymentAnalytics = $this->fixtures->create('payment_analytics', [
            'payment_id' => $payment['id'],
            'referer'    => 'http://www.abc.com'
        ]);

        $payment->setMetadataKey('payment_analytics', $paymentAnalytics);

        $this->setUpSalesforceMock();

        $this->setUpFreshdeskClientMock();

        $this->mockSalesforceRequestforSalesPOC($merchant['id'], 'abc@rzp.com');

        $emailPayload = [
            'payment' => [
                'id'             => $payment->getId(),
                'referer_url'    => 'http://www.abc.com',
                'referer_domain' => 'www.abc.com'
            ],
            'merchant' => [
                'id'    => $merchant->getId(),
                'name'  => $merchant->getName(),
                'email' => $merchant->getEmail()
            ]
        ];

        $mailBody = \View::make('emails.payment.fraud.domain_mismatch', $emailPayload)->render();

        $mailSubject = '[IMP] Payment failed due to  attempts from unregistered website for MID - ' . $merchant['id'];

        $expectedContent = [
            'description'     => $mailBody,
            'subject'         => $mailSubject,
            'group_id'        => 82000147768,
            'email_config_id' => 82000098661,
            'tags'            => ['website_mismatch'],
            'priority'        => 1,
            'status'          => 6,
            'type'            => 'Service request',
            'email'           => $merchant->getEmail(),
            'custom_fields'   => [
                'cf_new_requester_category'     => 'Razorpay',
                'cf_ticket_queue'               => 'Merchant',
                'cf_category'                   => 'Risk Report_Merchant',
                'cf_subcategory'                => 'Website Mismatch',
                'cf_product'                    => 'Payment Gateway',
                'cf_merchant_id'                => $merchant['id'],
                'cf_website_url'                => 'www.abc.com',
            ],
            'cc_emails' => [
                'abc@rzp.com'
            ],
        ];

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email',
            'post',
            $expectedContent,
            [
                'id'     => '12',
                'body'   => 'some random body 12',
                'status' => 6,
                'type'   => 'Service request',
                'tags'   => ['website_mismatch'],
                'custom_fields' =>  [
                    'cf_ticket_queue'           => 'Merchant',
                    'cf_category'               => 'Risk Report_Merchant',
                    'cf_subcategory'            => 'Website Mismatch',
                    'cf_product'                => 'Payment Gateway',
                    'cf_merchant_id'            => $merchant['id'],
                    'cf_website_url'            => 'www.abc.com',
                    'cf_new_requester_category' => 'Razorpay',
                ],
            ]
        );

        $splitzInput = [
            'experiment_id' => 'MYGLSjyh1SkQNa',
            'id'            => $merchant['id'],
        ];

        $splitzOutput = [
            'response' => []
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        (new Fraud\Notify())->notifyMerchantIfNeeded($merchant, $payment, 'BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_WEBSITE_MISMATCH');
    }

    //mobile signup - DomainMismatchMobileSignup - channel freshdesk ticket
    public function testNotifyMerchantForUrlMismatchMobileSignupMerchantExperimentEnabledExistingTicketInWocStatusReplyOnExistingTicket()
    {
        $merchant = $this->fixtures->create('merchant', [
            'activated'        => 1,
            'live'             => 1,
            'signup_via_email' => 0,
            'email'            => null,

        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id'    => $merchant['id'],
            'contact_mobile' => '+919999999999',
        ]);

        $payment = $this->fixtures->create('payment', [
            'merchant_id' => $merchant['id'],
        ]);

        $paymentAnalytics = $this->fixtures->create('payment_analytics', [
            'payment_id' => $payment['id'],
            'referer'    => 'http://www.abc.com'
        ]);

        $payment->setMetadataKey('payment_analytics', $paymentAnalytics);

        $this->setUpSalesforceMock();

        $this->setUpFreshdeskClientMock();

        $this->mockSalesforceRequestforSalesPOC($merchant['id'], 'abc@rzp.com');

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3A%27' . $merchant['id'] . '%27+AND+custom_string%3A%27www.abc.com%27+AND+custom_string%3A%27Website+Mismatch%27%22&page=1',
            'get',
            [],
            [
                'total'   => 1,
                'results' => [
                    [
                        'id'     => '12',
                        'body'   => 'some random body 12',
                        'status' => 6,
                        'type'   => 'Service request',
                        'tags'   => ['website_mismatch'],
                        'custom_fields' =>  [
                            'cf_ticket_queue'           => 'Merchant',
                            'cf_category'               => 'Risk Report_Merchant',
                            'cf_subcategory'            => 'Website Mismatch',
                            'cf_product'                => 'Payment Gateway',
                            'cf_merchant_id'            => $merchant['id'],
                            'cf_website_url'            => 'www.abc.com',
                            'cf_new_requester_category' => 'Razorpay',
                        ],
                    ],
                ],
            ]
        );

        $emailPayload = [
            'payment' => [
                'id'             => $payment->getId(),
                'referer_url'    => 'http://www.abc.com',
                'referer_domain' => 'www.abc.com'
            ],
            'merchant' => [
                'id'    => $merchant->getId(),
                'name'  => $merchant->getName(),
                'email' => $merchant->getEmail()
            ]
        ];

        $mailBody = \View::make('emails.payment.fraud.domain_mismatch', $emailPayload)->render();

        $this->expectFreshdeskRequestAndRespondWith('tickets/12/reply',
            'post',
            [
                'body'      => $mailBody,
                'cc_emails' => ['abc@rzp.com']
            ],
            [
                'id'        => 123,
                'ticket_id' => '12',
            ]
        );

        $splitzInput = [
            'experiment_id' => 'MYGLSjyh1SkQNa',
            'id'            => $merchant['id'],
        ];

        $splitzOutput = [
            'response' => [
                'variant' => [
                    'name' => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        (new Fraud\Notify())->notifyMerchantIfNeeded($merchant, $payment, 'BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_WEBSITE_MISMATCH');
    }

    //mobile signup - DomainMismatchMobileSignup - channel freshdesk ticket
    public function testNotifyMerchantForUrlMismatchMobileSignupMerchantExperimentEnabledNoExistingTicketCreateNewTicket()
    {
        $merchant = $this->fixtures->create('merchant', [
            'activated'        => 1,
            'live'             => 1,
            'signup_via_email' => 0,
            'email'            => null,

        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id'    => $merchant['id'],
            'contact_mobile' => '+919999999999',
        ]);

        $payment = $this->fixtures->create('payment', [
            'merchant_id' => $merchant['id'],
        ]);

        $paymentAnalytics = $this->fixtures->create('payment_analytics', [
            'payment_id' => $payment['id'],
            'referer'    => 'http://www.abc.com'
        ]);

        $payment->setMetadataKey('payment_analytics', $paymentAnalytics);

        $this->setUpSalesforceMock();

        $this->setUpFreshdeskClientMock();

        $this->mockSalesforceRequestforSalesPOC($merchant['id'], 'abc@rzp.com');

        $this->expectFreshdeskRequestAndRespondWith('search/tickets?query=%22custom_string%3A%27' . $merchant['id'] . '%27+AND+custom_string%3A%27www.abc.com%27+AND+custom_string%3A%27Website+Mismatch%27%22&page=1',
            'get',
            [],
            [
                'total'   => 0,
                'results' => []
            ]
        );

        $emailPayload = [
            'payment' => [
                'id'             => $payment->getId(),
                'referer_url'    => 'http://www.abc.com',
                'referer_domain' => 'www.abc.com'
            ],
            'merchant' => [
                'id'    => $merchant->getId(),
                'name'  => $merchant->getName(),
                'email' => $merchant->getEmail()
            ]
        ];

        $mailBody = \View::make('emails.payment.fraud.domain_mismatch', $emailPayload)->render();

        $mailSubject = '[IMP] Payment failed due to  attempts from unregistered website for MID - ' . $merchant['id'];

        $expectedContent = [
            'description'     => $mailBody,
            'subject'         => $mailSubject,
            'phone'           => '+919999999999',
            'group_id'        => 82000147768,
            'tags'            => ['website_mismatch'],
            'priority'        => 1,
            'status'          => 6,
            'type'            => 'Service request',
            'name'            => $merchant->getName(),
            'custom_fields'   => [
                'cf_ticket_queue'               => 'Merchant',
                'cf_category'                   => 'Risk Report_Merchant',
                'cf_subcategory'                => 'Website Mismatch',
                'cf_product'                    => 'Payment Gateway',
                'cf_created_by'                 => 'agent',
                'cf_website_url'                => 'www.abc.com',
                'cf_merchant_id'                => $merchant['id'],
                'cf_new_requester_category'     => 'Razorpay',
                'cf_merchant_id_dashboard'      => 'merchant_dashboard_' . $merchant['id'],
                'cf_merchant_activation_status' => 'undefined',
            ],
            'cc_emails' => [
                'abc@rzp.com'
            ],
        ];

        $this->expectFreshdeskRequestAndRespondWith('tickets',
            'post',
            $expectedContent,
            [
                'id'     => '12',
                'body'   => 'some random body 12',
                'status' => 6,
                'type'   => 'Service request',
                'tags'   => ['website_mismatch'],
                'custom_fields' =>  [
                    'cf_ticket_queue'           => 'Merchant',
                    'cf_category'               => 'Risk Report_Merchant',
                    'cf_subcategory'            => 'Website Mismatch',
                    'cf_product'                => 'Payment Gateway',
                    'cf_merchant_id'            => $merchant['id'],
                    'cf_website_url'            => 'www.abc.com',
                    'cf_new_requester_category' => 'Razorpay',
                ],
            ]
        );

        $splitzInput = [
            'experiment_id' => 'MYGLSjyh1SkQNa',
            'id'            => $merchant['id'],
        ];

        $splitzOutput = [
            'response' => [
                'variant' => [
                    'name' => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        (new Fraud\Notify())->notifyMerchantIfNeeded($merchant, $payment, 'BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_WEBSITE_MISMATCH');
    }

    //mobile signup - DomainMismatchMobileSignup - channel freshdesk ticket
    public function testNotifyMerchantForUrlMismatchMobileSignupMerchantExperimentEnabledExistingTicketInClosedStatusCreateNewTicket()
    {
        $merchant = $this->fixtures->create('merchant', [
            'activated'        => 1,
            'live'             => 1,
            'signup_via_email' => 0,
            'email'            => null,

        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id'    => $merchant['id'],
            'contact_mobile' => '+919999999999',
        ]);

        $payment = $this->fixtures->create('payment', [
            'merchant_id' => $merchant['id'],
        ]);

        $paymentAnalytics = $this->fixtures->create('payment_analytics', [
            'payment_id' => $payment['id'],
            'referer'    => 'http://www.abc.com'
        ]);

        $payment->setMetadataKey('payment_analytics', $paymentAnalytics);

        $this->setUpSalesforceMock();

        $this->setUpFreshdeskClientMock();

        $this->mockSalesforceRequestforSalesPOC($merchant['id'], 'abc@rzp.com');

        $emailPayload = [
            'payment' => [
                'id'             => $payment->getId(),
                'referer_url'    => 'http://www.abc.com',
                'referer_domain' => 'www.abc.com'
            ],
            'merchant' => [
                'id'    => $merchant->getId(),
                'name'  => $merchant->getName(),
                'email' => $merchant->getEmail()
            ]
        ];

        $mailBody = \View::make('emails.payment.fraud.domain_mismatch', $emailPayload)->render();

        $mailSubject = '[IMP] Payment failed due to  attempts from unregistered website for MID - ' . $merchant['id'];

        $expectedContent = [
            'description'     => $mailBody,
            'subject'         => $mailSubject,
            'phone'           => '+919999999999',
            'group_id'        => 82000147768,
            'tags'            => ['website_mismatch'],
            'priority'        => 1,
            'status'          => 6,
            'type'            => 'Service request',
            'name'            => $merchant->getName(),
            'custom_fields'   => [
                'cf_ticket_queue'               => 'Merchant',
                'cf_category'                   => 'Risk Report_Merchant',
                'cf_subcategory'                => 'Website Mismatch',
                'cf_product'                    => 'Payment Gateway',
                'cf_created_by'                 => 'agent',
                'cf_website_url'                => 'www.abc.com',
                'cf_merchant_id'                => $merchant['id'],
                'cf_new_requester_category'     => 'Razorpay',
                'cf_merchant_id_dashboard'      => 'merchant_dashboard_' . $merchant['id'],
                'cf_merchant_activation_status' => 'undefined',
            ],
            'cc_emails' => [
                'abc@rzp.com'
            ],
        ];

        $this->expectFreshdeskRequestAndRespondWith('tickets',
            'post',
            $expectedContent,
            [
                'id'     => '12',
                'body'   => 'some random body 12',
                'status' => 6,
                'type'   => 'Service request',
                'tags'   => ['website_mismatch'],
                'custom_fields' =>  [
                    'cf_ticket_queue'           => 'Merchant',
                    'cf_category'               => 'Risk Report_Merchant',
                    'cf_subcategory'            => 'Website Mismatch',
                    'cf_product'                => 'Payment Gateway',
                    'cf_merchant_id'            => $merchant['id'],
                    'cf_website_url'            => 'www.abc.com',
                    'cf_new_requester_category' => 'Razorpay',
                ],
            ]
        );

        $splitzInput = [
            'experiment_id' => 'MYGLSjyh1SkQNa',
            'id'            => $merchant['id'],
        ];

        $splitzOutput = [
            'response' => [
                'variant' => [
                    'name' => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        (new Fraud\Notify())->notifyMerchantIfNeeded($merchant, $payment, 'BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_WEBSITE_MISMATCH');
    }

    //mobile signup - DomainMismatchMobileSignup - channel freshdesk ticket
    public function testNotifyMerchantForUrlMismatchMobileSignupMerchantExperimentDisabledCreateNewTicket()
    {
        $merchant = $this->fixtures->create('merchant', [
            'activated'        => 1,
            'live'             => 1,
            'signup_via_email' => 0,
            'email'            => null,
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id'    => $merchant['id'],
            'contact_mobile' => '+919999999999',
        ]);

        $payment = $this->fixtures->create('payment', [
            'merchant_id' => $merchant['id'],
        ]);

        $paymentAnalytics = $this->fixtures->create('payment_analytics', [
            'payment_id' => $payment['id'],
            'referer'    => 'http://www.abc.com'
        ]);

        $payment->setMetadataKey('payment_analytics', $paymentAnalytics);

        $this->setUpSalesforceMock();

        $this->setUpFreshdeskClientMock();

        $this->mockSalesforceRequestforSalesPOC($merchant['id'], 'abc@rzp.com');

        $emailPayload = [
            'payment' => [
                'id'             => $payment->getId(),
                'referer_url'    => 'http://www.abc.com',
                'referer_domain' => 'www.abc.com'
            ],
            'merchant' => [
                'id'    => $merchant->getId(),
                'name'  => $merchant->getName(),
                'email' => $merchant->getEmail()
            ]
        ];

        $mailBody = \View::make('emails.payment.fraud.domain_mismatch', $emailPayload)->render();

        $mailSubject = '[IMP] Payment failed due to  attempts from unregistered website for MID - ' . $merchant['id'];

        $expectedContent = [
            'description'     => $mailBody,
            'subject'         => $mailSubject,
            'phone'           => '+919999999999',
            'group_id'        => 82000147768,
            'tags'            => ['website_mismatch'],
            'priority'        => 1,
            'status'          => 6,
            'type'            => 'Service request',
            'name'            => $merchant->getName(),
            'custom_fields'   => [
                'cf_ticket_queue'               => 'Merchant',
                'cf_category'                   => 'Risk Report_Merchant',
                'cf_subcategory'                => 'Website Mismatch',
                'cf_product'                    => 'Payment Gateway',
                'cf_created_by'                 => 'agent',
                'cf_website_url'                => 'www.abc.com',
                'cf_merchant_id'                => $merchant['id'],
                'cf_new_requester_category'     => 'Razorpay',
                'cf_merchant_id_dashboard'      => 'merchant_dashboard_' . $merchant['id'],
                'cf_merchant_activation_status' => 'undefined',
            ],
            'cc_emails' => [
                'abc@rzp.com'
            ],
        ];

        $this->expectFreshdeskRequestAndRespondWith('tickets',
            'post',
            $expectedContent,
            [
                'id'     => '12',
                'body'   => 'some random body 12',
                'status' => 6,
                'type'   => 'Service request',
                'tags'   => ['website_mismatch'],
                'custom_fields' =>  [
                    'cf_ticket_queue'           => 'Merchant',
                    'cf_category'               => 'Risk Report_Merchant',
                    'cf_subcategory'            => 'Website Mismatch',
                    'cf_product'                => 'Payment Gateway',
                    'cf_merchant_id'            => $merchant['id'],
                    'cf_website_url'            => 'www.abc.com',
                    'cf_new_requester_category' => 'Razorpay',
                ],
            ]
        );

        $splitzInput = [
            'experiment_id' => 'MYGLSjyh1SkQNa',
            'id'            => $merchant['id'],
        ];

        $splitzOutput = [
            'response' => []
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        (new Fraud\Notify())->notifyMerchantIfNeeded($merchant, $payment, 'BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_WEBSITE_MISMATCH');
    }
}
