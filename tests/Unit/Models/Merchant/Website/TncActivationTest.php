<?php

namespace Unit\Models\Merchant\Website;

use DB;
use Hash;
use Config;
use Mockery;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Services\RazorXClient;
use RZP\Http\Response\Response;
use RZP\Models\Admin\Permission;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Exception\ExtraFieldsException;
use RZP\Tests\Functional\Authorization;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use Illuminate\Contracts\Routing\ResponseFactory;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Website\Constants as WConstant;
use RZP\Exception\BadRequestValidationFailureException;

use Illuminate\Support\Facades\Mail;

class TncActivationTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    use MocksSplitz;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.kafka.producer.mock', true);

        //PGOS proxy request mocking
        Config::set('pgos.proxy.request.mock', true);
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

        $this->app['repo']->transaction(function() use ($merchant) {
            (new Merchant\Activate)->activate($merchant);
        });

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
        Mail::fake();

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

        $this->app['repo']->transaction(function() use ($merchant) {
            (new Merchant\Activate)->activate($merchant);
        });

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

        $this->app['repo']->transaction(function() use ($merchant) {

            (new Merchant\Activate)->activate($merchant);
        });

        $merchant = $this->getDbLastEntity('merchant');

        $this->assertTrue($merchant->isActivated());
    }

    /*
    public function testTncApplicableGenerationExecutedWFExistsMerchantActivated()
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
        $merchant = $this->fixtures->create('merchant', [
            'org_id'        => OrgEntity::AXIS_ORG_ID
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'    => 4,
            'submitted'        => 1,
            'business_website' => '',
            'merchant_id' => $merchant->getId()
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
    */

    private function createMerchant($input,$mockSplitz=true)
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

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            'app_urls' => [
                'playstore_url' => 'https://play.google.com/store/apps/details?id=com.whatsapp',
            ]
        ]);

        if ($mockSplitz === true)
        {
            $splitzInput = [
                "experiment_id" => "K2hFpyVAkZomaH",
                "id"            => $merchant->getId(),
            ];

            $splitzOutput = [
                "response" => [
                    "variant" => [
                        "name" => 'enable',
                    ]
                ]
            ];

            $this->mockSplitzTreatment($splitzInput, $splitzOutput);
        }
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

    public function testMerchantSaveSectionDetailsErrors()
    {

        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        //1. website in the input is different from business website
        $input = [
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => 1,
                    "website"        => [
                        "http://hello.com" => [
                            "url" => "http://hello.co.in/contact_us"
                        ]
                    ]
                ]
            ]];

        try
        {
            $response=  (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);
        }
        catch (\Exception $e)
        {
            //$this->assertExceptionClass($e, BadRequestException::class);
        }

        //2. testMerchantSaveSectionDetailsWithAppstoreFileStoreId
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
            $response= (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);
        }

        //3. invalid section status input
        $input = [
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => "4"
                ]
            ]];

        try
        {
            $response= (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);
        }

        //4. invalid input structure no section name provided
        $input = [
            "merchant_website_details" => [
                "section_status" => 1,
                "website"        => [
                    "https://hello.com" => [
                        "url" => "https://hello.co.in/contact_us"
                    ]
                ]
            ]
        ];

        try
        {
            $response= (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);

        }

        //5. invalid input data - extra non required field
        $input = [
            "merchant_website_details" => [
                "section_status" => 1,
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
            $response= (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);

        }

        //6. invalid input data - extra non required field from merchant auth
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
            $response= (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, ExtraFieldsException::class);

        }

        //7. invalid edit input -  - extra non required field
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
                                                                "section_status" => 2,
                                                                "website"        => [
                                                                    "https://hello.com" => [
                                                                        "url" => "https://hello.co.in/contact_us"
                                                                    ]
                                                                ]
                                                            ]
                                                        ]]);
        $input = [
            "section_name"          => "contact_us",
            "action"                => "publish",
            "merchant_consent"      => true,
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
            $response= (new Merchant\Website\Service)->postWebsiteSectionAction($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, ExtraFieldsException::class);

        }

        //8. testMerchantWebsiteSection Save Status
        $input = [
            WConstant::STATUS     => WConstant::SUBMITTED
        ];

        try
        {
            (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);

        }

    }

    public function testMerchantCreateWebsiteSectionDetailsSectionStatus1()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com']);

        $input = [
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => 1,
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
                                             "section_status" => 1,
                                             "status"         => null,
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

        $input = [
            "shipping_period"             => "3-5 days",
            "refund_request_period"       => "3-5 days",
            "refund_process_period"       => "3-5 days",
            "additional_data"             => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ], "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => 2,
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
                                          "section_status" => 2,
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

        $input = [
            "shipping_period"             => "3-5 days",
            "refund_request_period"       => "3-5 days",
            "refund_process_period"       => "3-5 days",
            "additional_data"             => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ], "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => 3,
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
                                          "section_status" => 3,
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
                                                "section_status" => 1,
                                                "website"        => [
                                                    "https://hello.com" => [
                                                        "url" => "https://hello.co.in/terms"
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]);


        $input = [
            "shipping_period"             => "3-5 days",
            "refund_request_period"       => "3-5 days",
            "refund_process_period"       => "3-5 days",
            "additional_data"             => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ], "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => 3,
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
                                          "section_status" => 1,
                                          "website"        => [
                                              "https://hello.com" => [
                                                  "url" => "https://hello.co.in/terms"
                                              ]
                                          ]
                                      ],
                                      "contact_us" => [
                                          "section_status" => 3,
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
                                                                   "section_status" => 1,
                                                                   "website"        => [
                                                                       "https://hello.com" => [
                                                                           "url" => "https://hello.co.in/terms"
                                                                       ]
                                                                   ]
                                                               ]
                                                           ]
                                                       ]);

        $input = [
            "shipping_period"             => "3-5 days",
            "refund_request_period"       => "3-5 days",
            "refund_process_period"       => "3-5 days",
            "additional_data"             => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ], "merchant_website_details" => [
                "terms" => [
                    "section_status" => 3,
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
                                          "section_status" => 3,
                                          "website"        => null
                                      ]
                                  ]], $websiteDetail);

        $this->assertArrayNotHasKey('admin_website_details', $websiteDetail);
        $this->assertArrayHasKey('id', $websiteDetail);
    }

    public function testMerchantWebsiteSectionActionSectionStatus3Publish()
    {
        Mail::fake();

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

        $input = [
            "section_name"     => "contact_us",
            "action"           => "publish",
            "merchant_consent" => true
        ];

        $websiteDetail = (new Merchant\Website\Service)->postWebsiteSectionAction($input);

        $this->assertArraySubset(["shipping_period"          => "3-5 days",
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
                                          "published_url"  => env(WConstant::MERCHANT_POLICIES_SUBDOMAIN) . '/policy/'.$merchantWebsite->getId().'/contact_us'
                                      ]
                                  ]], $websiteDetail);

        $merchantConsent = $this->getDbLastEntity('merchant_consents');

        $this->assertEquals($merchant->getId(), $merchantConsent->getMerchantId());
        $this->assertEquals("website_contact_us", $merchantConsent->getConsentFor());

        $merchantConsentDetails = $this->getDbLastEntity('merchant_consent_details');

        $this->assertNull($merchantConsentDetails);

        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.website_section_published', $mail->getTemplate());

            return true;
        });
    }

    public function testGetMerchantDocumentId()
    {

        $merchantWebsite = Mockery::mock('\RZP\Models\Merchant\Website\Entity')
                                  ->makePartial();

        $merchantWebsite->shouldReceive('getAttribute')->andReturn([
                                                                       'section' => [
                                                                           'playstore_url' => [
                                                                               'www.playstoreurl.com'  => [
                                                                                   'document_id' => 'id',
                                                                               ],
                                                                               'www.playstoreurl1.com'  => [
                                                                                   'document_id' => 'id1',
                                                                               ],
                                                                               'www.playstoreurl2.com'  => [
                                                                                   'document_id'
                                                                               ],
                                                                               'www.playstoreurl3.com'
                                                                           ],
                                                                       ],
                                                                   ]);

        $result = $merchantWebsite->getMerchantDocumentId('section', 'playstore_url', 'www.playstoreurl.com');

        $this->assertEquals('id', $result);

        $result1 = $merchantWebsite->getMerchantDocumentId('section', 'playstore_url', 'www.playstoreurl1.com');

        $this->assertEquals('id1', $result1);

        $result2 = $merchantWebsite->getMerchantDocumentId('section', 'playstore_url', 'www.playstoreurl2.com');

        $this->assertEquals(null, $result2);

        $result3 = $merchantWebsite->getMerchantDocumentId('section', 'playstore_url', 'www.playstoreurl3.com');

        $this->assertEquals(null, $result3);
    }

    public function testMerchantWebsiteSectionActionUploadAndDelete()
    {
        // Uploading the merchant screenshot

        $merchant = $this->createMerchant(['business_website' => 'https://hello.com'], false);

        $this->mockAllSplitzTreatment();

        $this->createWebsiteDetails(['merchant_id'              => $merchant->getId(),
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

        $file = UploadedFile::fake()->create('test.pdf', 100);

        $input = [
            'merchant_id'  => '10000000000000',
            'section_name' => 'contact_us',
            'url_type'     => 'playstore_url',
            'action'       => 'upload',
            'file'         => $file
        ];

        $websiteDetail = (new Merchant\Website\Service())->postWebsiteSectionAction($input);

        $this->assertArrayHasKey('document_id', $websiteDetail['merchant_website_details']['contact_us']
                                                ['playstore_url']['https://play.google.com/store/apps/details?id=com.whatsapp']);
        $this->assertNotNull($websiteDetail['merchant_website_details']['contact_us']['playstore_url']
                             ['https://play.google.com/store/apps/details?id=com.whatsapp']['document_id']);

        $this->assertArrayHasKey('signed_url', $websiteDetail['merchant_website_details']['contact_us']
                                               ['playstore_url']['https://play.google.com/store/apps/details?id=com.whatsapp']);
        $this->assertNotNull($websiteDetail['merchant_website_details']['contact_us']
                             ['playstore_url']['https://play.google.com/store/apps/details?id=com.whatsapp']['signed_url']);

        // deleting the merchant screenshot

        $input = [
            'merchant_id'  => '10000000000000',
            'section_name' => 'contact_us',
            'url_type'     => 'playstore_url',
            'action'       => 'delete',
        ];

        $websiteDetail = (new Merchant\Website\Service())->postWebsiteSectionAction($input);

        $this->assertArrayHasKey('document_id', $websiteDetail['merchant_website_details']['contact_us']
                                                ['playstore_url']['https://play.google.com/store/apps/details?id=com.whatsapp']);
        $this->assertNull($websiteDetail['merchant_website_details']['contact_us']['playstore_url']
                          ['https://play.google.com/store/apps/details?id=com.whatsapp']['document_id']);

    }

    //it should be functional test case
    /*public function testMerchantWebsiteSectionActionSectionStatus2Download()
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

        $this->ba = new Authorization($this);
        $this->ba->proxyAuth();

        $input = [
            "section_name"     => "contact_us",
            "action"           => "download",
            "merchant_consent" => true
        ];

        $websiteDetail = (new Merchant\Website\Service)->postWebsiteSectionAction($input);

        $this->assertArraySubset(["shipping_period"          => "3-5 days",
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
        $this->assertEquals("website_contact_us", $merchantConsent->getConsentFor());

        $merchantConsentDetails = $this->getDbLastEntity('merchant_consent_details');

        $this->assertNull($merchantConsentDetails);
    }
    //it should be functional test case
    public function testMerchantWebsiteSectionActionSectionDownload()
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

        $this->ba->proxyAuth();

        $input = [
            "section_name"     => "contact_us",
            "action"           => "download"
        ];

        $websiteDetail = (new Merchant\Website\Service)->postWebsiteSectionAction($input);

    }
    */

    //admin dashboard
    public function testAdminCreateWebsiteSectionDetails()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com'],false);

        $input = [
            "section_name" => "refund",
            "url_type"     => "website",
            "url"          => "https://hello.com",
            "section_url"  => "http://hello.com/refund"

        ];

        $websiteDetail = (new Merchant\Website\Service)->saveAdminWebsiteSection($merchant->getId(), $input);

        $this->assertArraySubset([
                                     "isWebsiteSectionsApplicable" => true,
                                     "isGracePeriodApplicable"     => false,
                                     "admin_website_details" => [
                                         "website"                     => [
                                             'https://hello.com' => [
                                                 "refund" => [
                                                     "url" => "http://hello.com/refund"
                                                 ]
                                             ]
                                         ]
                                     ]
                                 ], $websiteDetail);

        $this->assertArrayHasKey('admin_website_details', $websiteDetail);
        $this->assertArrayHasKey('merchant_website_details', $websiteDetail);
    }

    public function testAdminCreateWebsiteSectionDetailsInvalidData()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com'],false);

        //1. invalid website
        $input = [
            "section_name" => "refund",
            "url_type"     => "website",
            "url"          => "http://hello.com",
            "section_url"  => "http://hello.com/refund"
        ];

        try
        {
            $websiteDetail = (new Merchant\Website\Service)->saveAdminWebsiteSection($merchant->getId(), $input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);

        }

        //2. invalid url type
        $input = [
            "section_name" => "refund",
            "url_type"     => "websites",
            "url"          => "https://hello.com",
            "section_url"  => "http://hello.com/refund"
        ];

        try
        {
            $websiteDetail = (new Merchant\Website\Service)->saveAdminWebsiteSection($merchant->getId(), $input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);

        }

        //3. invalid section url
        $input = [
            "section_name" => "refund",
            "url_type"     => "website",
            "url"          => "https://hello.com",
            "section_url"  => "refund"
        ];

        try
        {
            $websiteDetail = (new Merchant\Website\Service)->saveAdminWebsiteSection($merchant->getId(), $input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);

        }

        //4. invalid section name
        $input = [
            "section_name" => "refund1",
            "url_type"     => "website",
            "url"          => "https://hello.com",
            "section_url"  => "http://hello.com/refund"
        ];

        try
        {
            $websiteDetail = (new Merchant\Website\Service)->saveAdminWebsiteSection($merchant->getId(), $input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);

        }

        //4. required field comments is missing
        $input = [
            "section_name" => "comments",
            "url_type"     => "website",
            "url"          => "https://hello.com"
        ];

        try
        {
            $websiteDetail = (new Merchant\Website\Service)->saveAdminWebsiteSection($merchant->getId(), $input);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);

        }
    }

    public function testSaveAdminCreateWebsiteSection()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com'],false);

        //section section information
        $input = [
            "section_name" => "refund",
            "url_type"     => "website",
            "url"          => "https://hello.com",
            "section_url"  => "https://hello.com/refund"
        ];

        $websiteDetail = (new Merchant\Website\Service)->saveAdminWebsiteSection($merchant->getId(), $input);

        $this->assertArraySubset([
                                     "admin_website_details" => [
                                         "website" => [
                                             "https://hello.com" => [
                                                 "refund" => [
                                                     "url" => "https://hello.com/refund"
                                                 ]
                                             ]
                                         ]
                                     ]
                                 ], $websiteDetail);
        // save comments
        $input = [
            "section_name" => "comments",
            "url_type"     => "website",
            "url"          => "https://hello.com",
            "comments"     => "hello world"
        ];

        $websiteDetail = (new Merchant\Website\Service)->saveAdminWebsiteSection($merchant->getId(), $input);

        $this->assertArraySubset([
                                     "admin_website_details" => [
                                         "website" => [
                                             "https://hello.com" => [
                                                 "comments" => "hello world"
                                             ]
                                         ]
                                     ]
                                 ], $websiteDetail);
    }

    public function testvalidateMerchantActivation()
    {
        $merchant = $this->fixtures->create('merchant', ['has_key_access' => false]);

        $merchantId = $merchant['id'];

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'merchant_id'               => $merchantId,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'activation_status'         => 'activated',
        ]);

        $websiteDetails = $this->createWebsiteDetails(['merchant_id'              =>$merchantId,
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
                                                                "website"        => [
                                                                    "https://hello.com" => [
                                                                        "url" => "https://hello.co.in/contact_us"
                                                                    ]
                                                                ]
                                                            ]
                                                        ]]);


        $result = (new Merchant\Website\Service())->validateMerchantActivation($merchantDetails, $websiteDetails);

        $this->assertFalse($result);
    }

    //Test case to update website policy response when bmc response is updated
    public function testUpdateWebsitePolicyResponseWhenBMCResponseUpdated()
    {
        $merchant = $this->createMerchant(['business_website' => 'https://hello.com'], false);

        $this->createWebsiteDetails([
                                        'merchant_id'              => $merchant->getId(),
                                        "merchant_website_details" => [
                                            "terms" => [
                                                "section_status" => 1,
                                                "website"        => [
                                                    "https://hello.com" => [
                                                        "url" => "https://hello.co.in/terms"
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]);

        $payload = [
            "data" => [
                [
                    "question_id" => "question_2",
                    "answer"      => [
                        "option_2_1"
                    ]
                ]
            ]
        ];

        (new Merchant\Website\Service())->updateCommonWebsiteQuestions($payload, true);

        $merchantWebsiteDetail = $this->getDbLastEntity('merchant_website');

        $this->assertEquals($merchant->getId(), $merchantWebsiteDetail->getMerchantId());

        $this->assertEquals("0-2 days", $merchantWebsiteDetail['shipping_period']);
    }

    //Test case to update bmc response when website policy response is updated
    public function testUpdateBMCResponseWhenWebsitePolicyResponseUpdated()
    {
        Config::set('pgos.proxy.request.mock', true);

        $this->createMerchant(['business_website' => 'https://hello.com']);

        $input = [
            "shipping_period"             => "0-2 days"
        ];

        (new Merchant\Website\Service)->saveMerchantWebsiteSection($input);

        $merchantWebsiteDetail = $this->getDbLastEntity('merchant_website');

        $this->assertEquals("0-2 days", $merchantWebsiteDetail['shipping_period']);
    }
}
