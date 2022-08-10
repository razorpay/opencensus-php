<?php

namespace Unit\Models\Merchant\Website;

use DB;
use Mail;
use Hash;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Services\RazorXClient;
use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Exception\ExtraFieldsException;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Website\Constants as WConstant;
use RZP\Exception\BadRequestValidationFailureException;

class TncActivationTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function mockRazorxTreatment()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');
    }

    public function testTncNotApplicableWFExecutedMerchantActivated()
    {
        $this->mockRazorxTreatment();

        $this->app['rzp.mode'] = 'test';

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'       => 4,
            'business_website'    => 'https://razorpay.com',
            'submitted'           => 1,
            'bank_account_name'   => 'Test',
            'bank_account_number' => '111000',
            'bank_branch_ifsc'    => 'SBIN0007105',
        ]);

        $merchant = $merchantDetail->merchant;

        $this->app['basicauth']->setMerchant($merchant);

        (new Merchant\Activate)->activate($merchant);

        $merchant = $this->getDbLastEntity('merchant');

        $this->assertTrue($merchant->isActivated());
    }

    /**
     * Scenario:
     *  - merchant hasn't filled website details
     *  - merchant is of Axis org
     *  - merchant hasn't filled TnC details
     * Expectation:
     *  - merchant should get activated even if tnc is not filled
     */
    public function testTncApplicableNotGeneratedWFExecutedMerchantNotActivated()
    {
        $this->mockRazorxTreatment();

        $this->app['rzp.mode'] = 'test';

        $org = $this->fixtures->create('org', [
            'id' => OrgEntity::AXIS_ORG_ID
        ]);

        $this->fixtures->create('org_hostname', [
            'org_id'   => OrgEntity::AXIS_ORG_ID,
            'hostname' => 'hdfcbank.in'
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'    => 4,
            'submitted'        => 1,
            'business_website' => '',
        ]);

        $merchant = $merchantDetail->merchant;
        $merchant->setAttribute('org_id', $org->getId());

        $this->app['basicauth']->setMerchant($merchant);

        (new Merchant\Activate)->activate($merchant);

        $merchant = $this->getDbLastEntity('merchant');

        $this->assertTrue($merchant->isActivated());
    }

    public function testTncApplicableAndGeneratedWFExecutedMerchantActivated()
    {
        $this->mockRazorxTreatment();

        $this->app['rzp.mode'] = 'test';

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'    => 4,
            'submitted'        => 1,
            'business_website' => ''
        ]);

        $this->fixtures->create('merchant_website', [
            'merchant_id'           => $merchantDetail->getId(),
            'deliverable_type'      => 'services',
            'shipping_period'       => '2 hours',
            'refund_request_period' => '29 days',
            'refund_process_period' => '1 day',
        ]);

        $merchant = $merchantDetail->merchant;

        $this->app['basicauth']->setMerchant($merchant);

        (new Merchant\Activate)->activate($merchant);

        $merchant = $this->getDbLastEntity('merchant');

        $this->assertTrue($merchant->isActivated());
    }

    /*public function testTncApplicableGenerationExecutedWFExistsMerchantActivated()
    {
        $this->mockRazorxTreatment();

        $this->app['rzp.mode'] = 'live';

        $org = $this->fixtures->create('org', [
            'id' => OrgEntity::AXIS_ORG_ID
        ]);

        $this->fixtures->create('org_hostname', [
            'org_id'   => OrgEntity::AXIS_ORG_ID,
            'hostname' => 'hdfcbank.in'
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'    => 4,
            'submitted'        => 1,
            'business_website' => ''
        ]);

        $workflow = $this->fixtures->connection('live')->create('workflow', [
            'org_id' => OrgEntity::AXIS_ORG_ID,
            'name'   => "TnC Workflow"
        ]);

        $perm = $this->fixtures->connection('live')->create('permission', [
            'name' => Permission\Name::EDIT_ACTIVATE_MERCHANT
        ]);

        // Attaching create_payout permission to the workflow
        DB::connection('live')->table('workflow_permissions')->insert([
                                                                          'workflow_id'   => $workflow->getId(),
                                                                          'permission_id' => $perm->getId()
                                                                      ]);

        DB::connection('live')->table('permission_map')->insert([
                                                                    'entity_id'     => OrgEntity::AXIS_ORG_ID,
                                                                    'entity_type'   => 'org',
                                                                    'permission_id' => $perm->getId(),
                                                                ]);

        $this->fixtures->create('workflow_action', [
            'entity_id'     => $merchantDetail->getId(),
            'entity_name'   => 'merchant_detail',
            'approved'      => 1,
            'permission_id' => $perm->getId(),
            'workflow_id'   => $workflow->getId()
        ]);

        $merchant = $merchantDetail->merchant;
        $merchant->setAttribute('org_id', $org->getId());
        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            'merchant_id'           => $merchantDetail->getId(),
            'deliverable_type'      => 'services',
            'shipping_period'       => '2 hours',
            'refund_request_period' => '29 days',
            'refund_process_period' => '1 day',
            'support_email'         => 'boom@example.com'
        ];

        (new Merchant\Website\Service)->saveMerchantTnc($input);

        $merchant = $this->getDbLastEntity('merchant');

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertTrue($merchant->isActivated());

        $this->assertEquals('activated', $merchantDetail->getActivationStatus());
    }


    // save merchant website section

    private function createMerchant($input)
    {
        $this->app['rzp.mode'] = 'live';

        $perm = $this->fixtures->create('permission', [
            'name' => Permission\Name::EDIT_ACTIVATE_MERCHANT
        ]);

        DB::table('permission_map')->insert([
                                                'entity_id'     => OrgEntity::RAZORPAY_ORG_ID,
                                                'entity_type'   => 'org',
                                                'permission_id' => $perm->getId(),
                                            ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $merchant = $merchantDetail->merchant;

        $ufhService = \Mockery::mock('RZP\Services\UfhService')->makePartial();

        $this->app->instance('ufh.service', $ufhService);

        $ufhService->shouldReceive('getSignedUrl')->andReturn([
                                                                  'signed_url' => 'firs/' . $merchantDetail['merchant_id'] . '/' . date('Y') . '/' . date('m') . '/' . "random_name.pdf"
                                                              ]);

        return $merchant;
    }

    private function createWebsiteDetails($input)
    {

        $merchantWebsite = $this->fixtures->on('live')->create('merchant_website', $input);

        $input["id"] = $merchantWebsite->getId();

        $merchantWebsite = $this->fixtures->on('test')->create('merchant_website', $input);

        return $merchantWebsite;
    }

    public function createUserMerchantMapping(array $attributes, $mode = 'test')
    {
        $userId = $attributes['user_id'];

        $merchantId = $attributes['merchant_id'];

        $role = $attributes['role'];

        $product = $attributes['product'] ?? 'primary';

        DB::connection($mode)->table('merchant_users')
          ->insert([
                       'merchant_id' => $merchantId,
                       'user_id'     => $userId,
                       'role'        => $role,
                       'product'     => $product,
                       'created_at'  => Carbon::now()->getTimestamp(),
                       'updated_at'  => Carbon::now()->getTimestamp(),
                   ]);
    }

    public function testMerchantSaveSectionDetailsWithDifferentWebsite()
    {

        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => "1",
                    "website"        => [
                        "http://hello.com" => [
                            "url" => "http://hello.co.in/contact_us"
                        ]
                    ],
                    "appstore_url"   => [
                        "https://apps.apple.com/lol12345.com" => [
                        ]
                    ]
                ]
            ]];

        try
        {
            (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);

        }
    }

    public function testMerchantSaveSectionDetailsWithAppstoreFileStoreId()
    {

        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => "1",
                    "website"        => [
                        "http://hello.com" => [
                            "url" => "http://hello.co.in/contact_us"
                        ]
                    ],
                    "appstore_url"   => [
                        "https://apps.apple.com/lol12345.com" => [
                            "file_store_id" => "DGwFIqo2nHqyqn"
                        ]
                    ]
                ]
            ]];

        try
        {
            (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);

        }
    }

    public function testMerchantSaveSectionDetailsInvalidData()
    {

        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => "4"
                ]
            ]];

        try
        {
            (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);

        }
    }

    public function testMerchantSaveSectionDetailsInvalidDataStructure()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            "merchant_website_details" => [
                "section_status" => "1",
                "website"        => [
                    "https://hello.com" => [
                        "url" => "https://hello.co.in/contact_us"
                    ]
                ]
            ]
        ];

        try
        {
            (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);

        }
    }

    public function testMerchantSaveSectionDetailsInvalidKey()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            "merchant_website_details" => [
                "section_status" => "1",
                "website"        => [
                    "https://hello.com" => [
                        "url"      => "https://hello.co.in/contact_us",
                        "comments" => "comments"
                    ]
                ]
            ]
        ];

        try
        {
            (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);

        }
    }

    public function testMerchantCreateWebsiteSectionDetailsSectionStatus1()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => "1",
                    "website"        => [
                        "https://hello.com" => [
                            "url" => "https://hello.co.in/contact_us"
                        ]
                    ]
                ]
            ]];

        $websiteDetail = (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);

        $this->assertArraySubset([
                                     "merchant_website_details" => [
                                         "contact_us" => [
                                             "section_status" => "1",
                                             "status"         => "submitted",
                                             "website"        => [
                                                 "https://hello.com" => [
                                                     "url" => "https://hello.co.in/contact_us"
                                                 ]
                                             ]
                                         ]
                                     ]], $websiteDetail);

        $this->assertArrayNotHasKey('admin_website_details', $websiteDetail);

    }

    public function testMerchantCreateWebsiteSectionDetailsSectionStatus2()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            "shipping_period"             => "3-5 days",
            "refund_request_period"       => "3-5 days",
            "refund_process_period"       => "3-5 days",
            "additional_data"             => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ], "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => "2",
                    "website"        => [
                        "https://hello.com" => [
                            "url" => "https://hello.co.in/contact_us"
                        ]
                    ]
                ]
            ]];

        $websiteDetail = (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);

        $this->assertArraySubset(["shipping_period"          => "3-5 days",
                                  "refund_request_period"    => "3-5 days",
                                  "refund_process_period"    => "3-5 days",
                                  "additional_data"          => [
                                      "support_contact_number" => "9980004017",
                                      "support_email"          => "kakarla.vasanthi@razorpay.com"
                                  ],
                                  "merchant_website_details" => [
                                      "contact_us" => [
                                          "section_status" => "2",
                                          "website"        => [
                                              "https://hello.com" => [
                                                  "url" => "https://hello.co.in/contact_us"
                                              ]
                                          ]
                                      ]
                                  ]], $websiteDetail);

        $this->assertArrayNotHasKey('admin_website_details', $websiteDetail);
        $this->assertArrayHasKey('id', $websiteDetail);

    }

    public function testMerchantCreateWebsiteSectionDetailsSectionStatus3()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            "shipping_period"             => "3-5 days",
            "refund_request_period"       => "3-5 days",
            "refund_process_period"       => "3-5 days",
            "additional_data"             => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ], "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => "3",
                ]
            ]];

        $websiteDetail = (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);

        $this->assertArraySubset(["shipping_period"          => "3-5 days",
                                  "refund_request_period"    => "3-5 days",
                                  "refund_process_period"    => "3-5 days",
                                  "additional_data"          => [
                                      "support_contact_number" => "9980004017",
                                      "support_email"          => "kakarla.vasanthi@razorpay.com"
                                  ],
                                  "merchant_website_details" => [
                                      "contact_us" => [
                                          "section_status" => "3",
                                      ]
                                  ]], $websiteDetail);

        $this->assertArrayNotHasKey('admin_website_details', $websiteDetail);
        $this->assertArrayHasKey('id', $websiteDetail);

    }

    public function testMerchantCreateWebsiteSectionDetailsSectionEdit()
    {

        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $this->createWebsiteDetails([
                                        'merchant_id'              => $merchant->getId(),
                                        "merchant_website_details" => [
                                            "terms" => [
                                                "section_status" => "1",
                                                "website"        => [
                                                    "https://hello.com" => [
                                                        "url" => "https://hello.co.in/terms"
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            "shipping_period"             => "3-5 days",
            "refund_request_period"       => "3-5 days",
            "refund_process_period"       => "3-5 days",
            "additional_data"             => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ], "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => "3",
                ]
            ]];

        $websiteDetail = (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);

        $this->assertArraySubset(["shipping_period"          => "3-5 days",
                                  "refund_request_period"    => "3-5 days",
                                  "refund_process_period"    => "3-5 days",
                                  "additional_data"          => [
                                      "support_contact_number" => "9980004017",
                                      "support_email"          => "kakarla.vasanthi@razorpay.com"
                                  ],
                                  "merchant_website_details" => [
                                      "terms"      => [
                                          "section_status" => "1",
                                          "website"        => [
                                              "https://hello.com" => [
                                                  "url" => "https://hello.co.in/terms"
                                              ]
                                          ]
                                      ],
                                      "contact_us" => [
                                          "section_status" => "3",
                                      ]
                                  ]], $websiteDetail);

        $this->assertArrayNotHasKey('admin_website_details', $websiteDetail);
        $this->assertArrayHasKey('id', $websiteDetail);
    }

    public function testMerchantCreateWebsiteSectionDetailsSectionEditSame()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $merchantWebsite = $this->createWebsiteDetails([
                                                           'merchant_id'              => $merchant->getId(),
                                                           "merchant_website_details" => [
                                                               "terms" => [
                                                                   "section_status" => "1",
                                                                   "website"        => [
                                                                       "https://hello.com" => [
                                                                           "url" => "https://hello.co.in/terms"
                                                                       ]
                                                                   ]
                                                               ]
                                                           ]
                                                       ]);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            "shipping_period"             => "3-5 days",
            "refund_request_period"       => "3-5 days",
            "refund_process_period"       => "3-5 days",
            "additional_data"             => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ], "merchant_website_details" => [
                "terms" => [
                    "section_status" => "3",
                ]
            ]];

        $websiteDetail = (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);

        $this->assertArraySubset(["shipping_period"          => "3-5 days",
                                  "refund_request_period"    => "3-5 days",
                                  "refund_process_period"    => "3-5 days",
                                  "additional_data"          => [
                                      "support_contact_number" => "9980004017",
                                      "support_email"          => "kakarla.vasanthi@razorpay.com"
                                  ],
                                  "merchant_website_details" => [
                                      "terms" => [
                                          "section_status" => "3",
                                          "website"        => [
                                              "https://hello.com" => [
                                                  "url" => "https://hello.co.in/terms"
                                              ]
                                          ]
                                      ]
                                  ]], $websiteDetail);

        $this->assertArrayNotHasKey('admin_website_details', $websiteDetail);
        $this->assertArrayHasKey('id', $websiteDetail);
    }

    public function testMerchantSaveAdminSectionDetails()
    {

        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            "admin_website_details" => [
                "website" => [
                    "contact_us" => [
                        "url" => "https://hello.co.in/contact_us"
                    ]
                ]
            ]
        ];

        try
        {
            (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, ExtraFieldsException::class);

        }
    }

    public function testMerchantWebsiteSectionActionSectionStatus2InvalidInput()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $merchantWebsite = $this->createWebsiteDetails(['merchant_id'              => $merchant->getId(),
                                                        "shipping_period"          => "3-5 days",
                                                        "refund_request_period"    => "3-5 days",
                                                        "refund_process_period"    => "3-5 days",
                                                        "additional_data"          => [
                                                            "support_contact_number" => "9980004017",
                                                            "support_email"          => "kakarla.vasanthi@razorpay.com"
                                                        ],
                                                        "merchant_website_details" => [
                                                            "contact_us" => [
                                                                "section_status" => "2",
                                                                "website"        => [
                                                                    "https://hello.com" => [
                                                                        "url" => "https://hello.co.in/contact_us"
                                                                    ]
                                                                ]
                                                            ]
                                                        ]]);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            "section_name"     => "contact_us1",
            "action"           => "publish",
            "merchant_consent" => true
        ];

        try
        {
            (new Merchant\Website\Service)->postWebsiteSectionAction($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);

        }
    }

    public function testMerchantWebsiteSectionActionSectionStatus2ExtraFields()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $merchantWebsite = $this->createWebsiteDetails(['merchant_id'              => $merchant->getId(),
                                                        "shipping_period"          => "3-5 days",
                                                        "refund_request_period"    => "3-5 days",
                                                        "refund_process_period"    => "3-5 days",
                                                        "additional_data"          => [
                                                            "support_contact_number" => "9980004017",
                                                            "support_email"          => "kakarla.vasanthi@razorpay.com"
                                                        ],
                                                        "merchant_website_details" => [
                                                            "contact_us" => [
                                                                "section_status" => "2",
                                                                "website"        => [
                                                                    "https://hello.com" => [
                                                                        "url" => "https://hello.co.in/contact_us"
                                                                    ]
                                                                ]
                                                            ]
                                                        ]]);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            "section_name"     => "contact_us",
            "action"           => "publish",
            "merchant_consent" => true,
            "admin_website_details" => [
                "website" => [
                    "contact_us" => [
                        "url" => "https://hello.co.in/contact_us"
                    ]
                ]
            ]
        ];

        try
        {
            (new Merchant\Website\Service)->postWebsiteSectionAction($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, ExtraFieldsException::class);

        }
    }

    public function testMerchantWebsiteSectionActionSectionStatus3Download()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $merchantWebsite = $this->createWebsiteDetails(['merchant_id'              => $merchant->getId(),
                                                        "shipping_period"          => "3-5 days",
                                                        "refund_request_period"    => "3-5 days",
                                                        "refund_process_period"    => "3-5 days",
                                                        "additional_data"          => [
                                                            "support_contact_number" => "9980004017",
                                                            "support_email"          => "kakarla.vasanthi@razorpay.com"
                                                        ],
                                                        "merchant_website_details" => [
                                                            "contact_us" => [
                                                                "section_status" => "2",
                                                                "website"        => [
                                                                    "https://hello.com" => [
                                                                        "url" => "https://hello.co.in/contact_us"
                                                                    ]
                                                                ]
                                                            ]
                                                        ]]);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            "section_name"     => "contact_us",
            "action"           => "publish",
            "merchant_consent" => true
        ];

        try
        {
            (new Merchant\Website\Service)->postWebsiteSectionAction($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);

        }
    }

    public function testMerchantWebsiteSectionActionSectionStatus2Publish()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $merchantWebsite = $this->createWebsiteDetails(['merchant_id'              => $merchant->getId(),
                                                        "shipping_period"          => "3-5 days",
                                                        "refund_request_period"    => "3-5 days",
                                                        "refund_process_period"    => "3-5 days",
                                                        "additional_data"          => [
                                                            "support_contact_number" => "9980004017",
                                                            "support_email"          => "kakarla.vasanthi@razorpay.com"
                                                        ],
                                                        "merchant_website_details" => [
                                                            "contact_us" => [
                                                                "section_status" => "2",
                                                                "website"        => [
                                                                    "https://hello.com" => [
                                                                        "url" => "https://hello.co.in/contact_us"
                                                                    ]
                                                                ]
                                                            ]
                                                        ]]);

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            WConstant::SECTION_NAME     => WConstant::CONTACT_US,
            WConstant::ACTION           => WConstant::PUBLISH,
            WConstant::MERCHANT_CONSENT => true
        ];

        try
        {
            (new Merchant\Website\Service)->postWebsiteSectionAction($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);

        }
    }

    public function testMerchantWebsiteSectionActionSectionStatus3()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $merchantWebsite = $this->createWebsiteDetails(['merchant_id'              => $merchant->getId(),
                                                        "shipping_period"          => "3-5 days",
                                                        "refund_request_period"    => "3-5 days",
                                                        "refund_process_period"    => "3-5 days",
                                                        "additional_data"          => [
                                                            "support_contact_number" => "9980004017",
                                                            "support_email"          => "kakarla.vasanthi@razorpay.com"
                                                        ],
                                                        "merchant_website_details" => [
                                                            "contact_us" => [
                                                                "section_status" => 3
                                                            ]
                                                        ]]);

        $user = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->createUserMerchantMapping($mappingData, 'test');
        $this->createUserMerchantMapping($mappingData, 'live');

        $this->app['basicauth']->setMerchant($merchant);
        $this->app['basicauth']->setUser($user);

        $consentURL = "https://razorpay.com/terms/";

        $input = [
            "section_name"     => "contact_us",
            "action"           => "publish",
            "merchant_consent" => true
        ];

        $websiteDetail = (new Merchant\Website\Service)->postWebsiteSectionAction($input);

        $this->assertArraySubset(['merchant_id'              => $merchant->getId(),
                                  "shipping_period"          => "3-5 days",
                                  "refund_request_period"    => "3-5 days",
                                  "refund_process_period"    => "3-5 days",
                                  "additional_data"          => [
                                      "support_contact_number" => "9980004017",
                                      "support_email"          => "kakarla.vasanthi@razorpay.com"
                                  ],
                                  "merchant_website_details" => [
                                      "contact_us" => [
                                          "section_status" => 3,
                                          "status"         => "submitted",
                                          "published_url"  => env(WConstant::MERCHANT_POLICIES_SUBDOMAIN).'/policy/contact_us/'.$merchantWebsite->getId()
                                      ]
                                  ]], $websiteDetail);

        $merchantConsent = $this->getDbLastEntity('merchant_consents');

        $this->assertEquals($merchant->getId(), $merchantConsent->getMerchantId());
        $this->assertEquals("contact_us", $merchantConsent->getConsentFor());

        $merchantConsentDetails = $this->getDbLastEntity('merchant_consent_details');

        $this->assertNull($merchantConsentDetails);
    }

    public function testMerchantWebsiteSectionActionSectionStatus2()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $merchantWebsite = $this->createWebsiteDetails(['merchant_id'              => $merchant->getId(),
                                                        "shipping_period"          => "3-5 days",
                                                        "refund_request_period"    => "3-5 days",
                                                        "refund_process_period"    => "3-5 days",
                                                        "additional_data"          => [
                                                            "support_contact_number" => "9980004017",
                                                            "support_email"          => "kakarla.vasanthi@razorpay.com"
                                                        ],
                                                        "merchant_website_details" => [
                                                            "contact_us" => [
                                                                "section_status" => 2
                                                            ]
                                                        ]]);

        $user = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->createUserMerchantMapping($mappingData, 'test');
        $this->createUserMerchantMapping($mappingData, 'live');

        $this->app['basicauth']->setMerchant($merchant);
        $this->app['basicauth']->setUser($user);

        $consentURL = "https://razorpay.com/terms/";

        $input = [
            "section_name"     => "contact_us",
            "action"           => "download",
            "merchant_consent" => true
        ];

        $websiteDetail = (new Merchant\Website\Service)->postWebsiteSectionAction($input);

        $this->assertArraySubset(['merchant_id'              => $merchant->getId(),
                                  "shipping_period"          => "3-5 days",
                                  "refund_request_period"    => "3-5 days",
                                  "refund_process_period"    => "3-5 days",
                                  "additional_data"          => [
                                      "support_contact_number" => "9980004017",
                                      "support_email"          => "kakarla.vasanthi@razorpay.com"
                                  ],
                                  "merchant_website_details" => [
                                      "contact_us" => [
                                          "section_status" => 2,
                                          "status"         => "submitted"
                                      ]
                                  ]], $websiteDetail);

        $merchantConsent = $this->getDbLastEntity('merchant_consents');

        $this->assertEquals($merchant->getId(), $merchantConsent->getMerchantId());
        $this->assertEquals("contact_us", $merchantConsent->getConsentFor());

        $merchantConsentDetails = $this->getDbLastEntity('merchant_consent_details');

        $this->assertNull($merchantConsentDetails);
    }

    //admin dashboard
    public function testAdminCreateWebsiteSectionDetails()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $input = [
            "admin_website_details" => [
                "website" => [
                    "contact_us" => [
                        "url" => "https://hello.co.in/contact_us"
                    ]
                ]
            ]
        ];

        $websiteDetail = (new Merchant\Website\Service)->saveAdminWebsiteSection($merchant, $input);

        $this->assertArraySubset([
                                     "admin_website_details" => [
                                         "website" => [
                                             "contact_us" => [
                                                 "url" => "https://hello.co.in/contact_us"
                                             ]
                                         ]
                                     ]
                                 ], $websiteDetail);

        $this->assertArrayHasKey('admin_website_details', $websiteDetail);
        $this->assertArrayHasKey('merchant_website_details', $websiteDetail);
    }

    public function testAdminCreateWebsiteSectionComments()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $input = [
            "admin_website_details" => [
                "website" => [
                    "comments" => "hello world"
                ]
            ]
        ];

        $websiteDetail = (new Merchant\Website\Service)->saveAdminWebsiteSection($merchant, $input);

        $this->assertArraySubset([
                                     "admin_website_details" => [
                                         "website" => [
                                             "comments" => "hello world"
                                         ]
                                     ]
                                 ], $websiteDetail);

        $this->assertArrayHasKey('admin_website_details', $websiteDetail);
        $this->assertArrayHasKey('merchant_website_details', $websiteDetail);
    }*/
}
