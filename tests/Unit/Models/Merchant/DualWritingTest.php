<?php


namespace Unit\Models\Merchant;

use DB;
use Mockery;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Services\SplitzService;
use RZP\Models\Merchant\Service;
use RZP\Services\Mock\HarvesterClient;
use RZP\Services\RazorXClient;
use RZP\Models\Admin\Permission;
use RZP\Models\Feature\Constants;
use Illuminate\Support\Facades\Mail;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Services\Mock\DruidService as MockDruidService;
use RZP\Models\Merchant\AutoKyc\Escalations\Core as EscalationCore;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants as EscalationConstant;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Cron\Core as CronJobHandler;
use RZP\Models\Merchant\Cron\Constants as CronConstants;
use function PHPUnit\Framework\assertNotNull;

class DualWritingTest extends TestCase
{
    use DbEntityFetchTrait;

    protected function mockSplitzTreatment($input, $output)
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);

        $this->app->instance('splitzService', $this->splitzMock);
    }

    protected function createAndFetchMocks($mid,$experimentEnabled=true,$service='pgos')
    {
        Mail::fake();

        $merchant = $this->fixtures->create('merchant', [
            'id'            => $mid,
            'website'       => null,
            'name'          => null,
            'email'         => null,
            'billing_label' => null,
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id'      => $mid,
            'contact_email'    => null,
            'business_website' => null]);

        $this->fixtures->create('stakeholder', ['name' => 'stakeholder name', 'percentage_ownership' => 90, 'merchant_id' => $mid]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($mid);

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $mid,
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding',
            'metadata'        => [
                'service' => $service
            ]
        ]);

        if ($experimentEnabled === true)
        {
            $splitzMockInput = [
                'id'            => $mid,
                'experiment_id' => \Config::get('app.pgos_migration_dual_writing_exp_id')
            ];

            $splitzMockOutput = [
                'response' => [
                    'variant' => [
                        'name' => 'variables'
                    ]
                ]
            ];

            $this->mockSplitzTreatment($splitzMockInput, $splitzMockOutput);
        }
    }

    public function testSaveMerchantsPGOSDataToAPI()
    {
        $this->createAndFetchMocks('KqsQEszAud2PqZ');

        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "merchants",
            "type"                => "update",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                 => "KqsQEszAud2PqZ",
                "stakeholder"        => [
                    "stakeholder_name"  => "Shwetabh Shekhar",
                    "stakeholder_email" => ""
                ],
                "country_code"       => null,
                "business_details"   => [
                    "billing_label"               => "CHIZRINZ INFOWAY PRIVATE",
                    "business_type"               => "llp",
                    "business_operation_address"  => null,
                    "business_registered_address" => null,
                ],
                "business_identity"  => [
                    "company_pan"      => "ABCCD1235B",
                    "promoter_pan"     => "ABCPD1234A",
                    "company_pan_name" => "CHIZRINZ INFOWAY PRIVATE",
                ],
                "payment_preference" => [
                    "others"              => "Others",
                    "social_media"        => false,
                    "others_present"      => true,
                    "physical_store"      => false,
                    "ios_app_present"     => false,
                    "website_present"     => false,
                    "android_app_present" => false
                ]
            ],
            "old"                 => [
                "stakeholder" => [
                    "stakeholder_name"  => "Shwetabh Shekhar old",
                    "stakeholder_email" => ""
                ]
            ]
        ];

        (new Service)->savePGOSDataToAPI($data);

        $merchant1 = (new \RZP\Models\Merchant\Repository)->find('KqsQEszAud2PqZ');

        $this->assertArraySubset(["id"            => "KqsQEszAud2PqZ",
                                  "name"          => "Shwetabh Shekhar",
                                  "email"         => "",
                                  "billing_label" => "CHIZRINZ INFOWAY PRIVATE"],
                                 $merchant1->toArray());

        $merchantDetail1 = (new \RZP\Models\Merchant\Detail\Repository)->find('KqsQEszAud2PqZ');

        $this->assertArraySubset(["merchant_id"   => "KqsQEszAud2PqZ",
                                  "contact_name"  => "Shwetabh Shekhar",
                                  "contact_email" => "",
                                  "business_dba"  => "CHIZRINZ INFOWAY PRIVATE",
                                  "company_pan"   => "ABCCD1235B",
                                  "promoter_pan"  => "ABCPD1234A",
                                  "business_type" => 6,
                                 ],
                                 $merchantDetail1->toArray());

        $stakeholder1 = $this->getDbLastEntity('stakeholder', 'live');

        $this->assertArraySubset(["merchant_id"               => "KqsQEszAud2PqZ",
                                  "email"                     => "",
                                  "name"                      => "Shwetabh Shekhar",
                                  "poi_identification_number" => "ABCPD1234A",
                                 ],
                                 $stakeholder1->toArray());

        $merchantBusinessDetail1 = $this->getDbLastEntity('merchant_business_detail', 'live');

        $this->assertArraySubset(["merchant_id"     => "KqsQEszAud2PqZ",
                                  "website_details" => [
                                      "others"              => "Others",
                                      "social_media"        => false,
                                      "others_present"      => true,
                                      "physical_store"      => false,
                                      "ios_app_present"     => false,
                                      "website_present"     => false,
                                      "android_app_present" => false
                                  ]],
                                 $merchantBusinessDetail1->toArray());

        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "merchants",
            "type"                => "update",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                 => 'KqsQEszAud2PqZ',
                "stakeholder"        => [
                    "stakeholder_name"  => "Vasanthi",
                    "stakeholder_email" => "vasanthik22@gmail.com"
                ],
                "country_code"       => null,
                "business_details"   => [
                    "billing_label"               => "CHIZRINZ INFOWAY PRIVATE",
                    "business_type"               => "llp",
                    "yearly_transaction_volume"   => 0,
                    "business_operation_address"  => null,
                    "business_registered_address" => null,
                    "business_parent_category"    => "healthcare"
                ],
                "business_identity"  => [
                    "company_pan"      => "ABCCD1235B",
                    "promoter_pan"     => "ABCPD1234A",
                    "company_pan_name" => "CHIZRINZ INFOWAY PRIVATE",
                ],
                "payment_preference" => [
                    "others"              => "Others",
                    "social_media"        => false,
                    "others_present"      => true,
                    "physical_store"      => false,
                    "ios_app_present"     => false,
                    "website_present"     => false,
                    "android_app_present" => false
                ]
            ],
            "old"                 => [
                "stakeholder" => [
                    "stakeholder_name"  => "Shwetabh Shekhar old",
                    "stakeholder_email" => ""
                ]
            ]
        ];

        (new Service)->savePGOSDataToAPI($data);

        $stakeholder2 = $this->getDbLastEntity('stakeholder', 'live');

        $this->assertArraySubset(["id"                        => $stakeholder1->getId(),
                                  "merchant_id"               => "KqsQEszAud2PqZ",
                                  "email"                     => "vasanthik22@gmail.com",
                                  "name"                      => "Vasanthi",
                                  "poi_identification_number" => "ABCPD1234A",
                                 ],
                                 $stakeholder2->toArray());

        $merchantBusinessDetail2 = $this->getDbLastEntity('merchant_business_detail', 'live');

        $this->assertArraySubset(["merchant_id"              => "KqsQEszAud2PqZ",
                                  "website_details"          => [
                                      "others"              => "Others",
                                      "social_media"        => false,
                                      "others_present"      => true,
                                      "physical_store"      => false,
                                      "ios_app_present"     => false,
                                      "website_present"     => false,
                                      "android_app_present" => false
                                  ],
                                  "business_parent_category" => "healthcare"],
                                 $merchantBusinessDetail2->toArray());


    }

    public function testSaveVerificationsPGOSDataToAPI()
    {

        $this->createAndFetchMocks('LDWO4rOnPQTjan');

        //insert success verification
        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "verification_details",
            "type"                => "insert",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                  => "LDXmdN7bVNs9Qo",
                "metadata"            => [
                    "platform"       => "bvs",
                    "bvs_probe_id"   => "LDWTUJHJBsTMXS",
                    "aadhaar_linked" => "1"
                ],
                "created_at"          => 1675770614,
                "updated_at"          => 1675770614,
                "merchant_id"         => "LDWO4rOnPQTjan",
                "artefact_type"       => "aadhaar_esign",
                "verification_id"     => "LDWTUJHJBsTMXS",
                "verification_unit"   => "auth",
                "verification_status" => "verified"
            ],
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $stakeholder1 = $this->getDbLastEntity('stakeholder', 'live');

        $this->assertArraySubset(["merchant_id"          => "LDWO4rOnPQTjan",
                                  "aadhaar_esign_status" => "verified",
                                  "bvs_probe_id"         => "LDWTUJHJBsTMXS",
                                  "aadhaar_linked"       => "1"],
                                 $stakeholder1->toArray());

        $verificationDetail1 = $this->getDbLastEntity('merchant_verification_detail', 'live');

        $this->assertArraySubset(["merchant_id"         => "LDWO4rOnPQTjan",
                                  "artefact_type"       => "aadhaar_esign",
                                  "status"              => "verified",
                                  "artefact_identifier" => "number"],
                                 $verificationDetail1->toArray());

        $bvsValidation1 = $this->getDbLastEntity('bvs_validation', 'live');

        $this->assertArraySubset([
                                     "validation_id"       => "LDWTUJHJBsTMXS",
                                     "artefact_type"       => "aadhaar_esign",
                                     "platform"            => "pg",
                                     "validation_status"   => "success",
                                     "validation_unit"     => "identifier",
                                     "owner_id"            => "LDWO4rOnPQTjan",
                                     "owner_type"          => "merchant",
                                     "error_code"          => null,
                                     "error_description"   => null,
                                     "rule_execution_list" => null
                                 ],
                                 $bvsValidation1->toArray());

        //insert pending verification
        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "verification_details",
            "type"                => "insert",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                  => "LDXmdN7bVNs9Qo",
                "metadata"            => [
                    "platform" => "bvs",
                ],
                "created_at"          => 1675770614,
                "updated_at"          => 1675770614,
                "merchant_id"         => "LDWO4rOnPQTjan",
                "artefact_type"       => "aadhaar_esign",
                "verification_id"     => "LFs3cCyQKvYTLQ",
                "verification_unit"   => "ocr",
                "verification_status" => "captured"
            ],
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        //verification status update test case in merchant details
        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "verification_details",
            "type"                => "insert",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                                                             => "LDXmdN7bVNs9Qo",
                "metadata"                                                       => [
                    "platform" => "bvs",
                ],
                "created_at"                                                     => 1675770614,
                "updated_getApplicableActivationStatusForUnregisteredMerchantat" => 1675770614,
                "merchant_id"                                                    => "LDWO4rOnPQTjan",
                "artefact_type"                                                  => "personal_pan",
                "verification_id"                                                => "LDWTUJHJBsTMXS",
                "verification_unit"                                              => "auth",
                "verification_status"                                            => "verified"
            ],
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $merchantDetail1 = (new \RZP\Models\Merchant\Detail\Repository)->getByMerchantId('LDWO4rOnPQTjan');

        $this->assertArraySubset(["merchant_id"             => "LDWO4rOnPQTjan",
                                  "poi_verification_status" => "verified"],
                                 $merchantDetail1->toArray());

        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "verification_details",
            "type"                => "insert",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                  => "LDXmdN7bVNs9Qo",
                "metadata"            => [
                    "platform"          => "bvs",
                    "error_code"        => "NO_PROVIDER_ERROR",
                    "fields_verified"   => null,
                    "error_description" => ""
                ],
                "created_at"          => 1675770614,
                "updated_at"          => 1675770614,
                "merchant_id"         => "LDWO4rOnPQTjan",
                "artefact_type"       => "personal_pan",
                "verification_id"     => "LDWTUJHJBsTMXS",
                "verification_unit"   => "auth",
                "verification_status" => "failed"
            ],
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $merchantDetail1 = (new \RZP\Models\Merchant\Detail\Repository)->getByMerchantId('LDWO4rOnPQTjan');

        $this->assertArraySubset(["merchant_id"             => "LDWO4rOnPQTjan",
                                  "poi_verification_status" => "failed"],
                                 $merchantDetail1->toArray());
    }

    public function testSaveDocumentsPGOSDataToAPI()
    {
        $this->createAndFetchMocks('OPMRlog41YXkli');

        //insert
        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "documents",
            "type"                => "insert",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                 => "L0QSK3lNI9nl37",
                "deleted_at"         => null,
                "merchant_id"        => "OPMRlog41YXkli",
                "document_type"      => "sebi_registration_certificate",
                "file_store_id"      => "L0QSJzjt0UMwSG",
                "upload_by_admin_id" => null
            ],
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $doc1 = $this->getDbLastEntity('merchant_document', 'live');

        $this->assertArraySubset(["document_type" => "sebi_registration_certificate",
                                  "file_store_id" => "L0QSJzjt0UMwSG",
                                  "deleted_at"    => null,
                                  "merchant_id"   => "OPMRlog41YXkli"],
                                 $doc1->toArray());

        //update
        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "documents",
            "type"                => "insert",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                 => "L0QSK3lNI9nl37",
                "deleted_at"         => 1673248693,
                "merchant_id"        => "OPMRlog41YXkli",
                "document_type"      => "sebi_registration_certificate",
                "file_store_id"      => "L0QSJzjt0UMwSG",
                "upload_by_admin_id" => null
            ],
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $doc2 = (new \RZP\Models\Merchant\Document\Repository)->findDocumentByFileStoreId('L0QSJzjt0UMwSG');

        $this->assertNull($doc2);

    }

    public function testSaveOnboardingDataToAPI()
    {
        $mid='KqsQEszAud2PqZ';
        $this->createAndFetchMocks($mid);

        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "onboarding_details",
            "type"                => "update",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "locked"                        => 0,
                "activated"                     => 1,
                "submitted"                     => 1,
                "merchant_id"                   => $mid,
                "activated_at"                  => 1673248693,
                "submitted_at"                  => 1673248693,
                "signup_source"                 => null,
                "activation_flow"               => "whitelist",
                "signup_campaign"               => null,
                "activation_status"             => null,
                "activation_progress"           => 60,
                "activation_form_milestone"     => "L1",
                "international_activation_flow" => null
            ]
            ,
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $merchant1 = (new \RZP\Models\Merchant\Repository)->find($mid);

        $this->assertArraySubset(["id"           => $mid,
                                  "activated"    => true,
                                  "activated_at" => 1673248693
                                 ],
                                 $merchant1->toArray());

        $merchant1 = (new \RZP\Models\Merchant\Detail\Repository)->find($mid);

        $this->assertArraySubset(["merchant_id"               => $mid,
                                  "submitted"                 => true,
                                  "submitted_at"              => 1673248693,
                                  "activation_flow"           => "whitelist",
                                  "activation_progress"       => 60,
                                  "activation_form_milestone" => "L1",
                                 ],
                                 $merchant1->toArray());
    }

    public function testSaveWebsiteDataToAPI()
    {
        $mid='KqsQEszAud2PqZ';

        $this->createAndFetchMocks($mid);

        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "website_details",
            "type"                => "update",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                 => "LGaOT076nCxB6a",
                "status"             => null,
                "metadata"           => [
                    "additional_websites" => [
                        "https://www.facebook.com/",
                        "https://hello.com"
                    ],
                    "whitelisted_domains" => [
                        "facebook.com",
                        "microsoft.com",
                        "hello.com"
                    ]
                ],
                "merchant_id"        => $mid,
                "appstore_url"       => "https://apps.apple.com/in/app/apple-store/id375380948",
                "grace_period"       => null,
                "playstore_url"      => "https://play.google.com/store/apps/details?id=com.xd.fpos.ad",
                "business_website"   => "https://myapplications.microsoft.com/",
                "send_communication" => null
            ],
            "old"                 => []
        ];
        (new Service)->savePGOSDataToAPI($data);

        $merchant1 = (new \RZP\Models\Merchant\Repository)->find($mid);

        $this->assertArraySubset(["id"                  => "KqsQEszAud2PqZ",
                                  "website"             => "https://myapplications.microsoft.com/",
                                  "whitelisted_domains" => [
                                      "facebook.com",
                                      "microsoft.com",
                                      "hello.com"
                                  ]],
                                 $merchant1->toArray());

        $merchantDetail1 = (new \RZP\Models\Merchant\Detail\Repository)->find($mid);

        $this->assertArraySubset(["merchant_id"         => "KqsQEszAud2PqZ",
                                  "business_website"    => "https://myapplications.microsoft.com/",
                                  "additional_websites" => [
                                      "https://www.facebook.com/",
                                      "https://hello.com"
                                  ],
                                 ],
                                 $merchantDetail1->toArray());

        $merchantBusinessDetail1 = $this->getDbLastEntity('merchant_business_detail', 'live');

        $this->assertArraySubset(["merchant_id" => $mid,
                                  "app_urls"    => [
                                      "appstore_url"  => "https://apps.apple.com/in/app/apple-store/id375380948",
                                      "playstore_url" => "https://play.google.com/store/apps/details?id=com.xd.fpos.ad",
                                  ]],
                                 $merchantBusinessDetail1->toArray());
    }

    public function testSaveClarificationDetailsToAPI()
    {
        $mid='LKDtR1ECoLNx5g';

        $this->createAndFetchMocks($mid);

        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "clarification_details",
            "type"                => "update",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"            => "LOe7ZFgd9eOawB",
                "status"        => "submitted",
                "metadata"      =>
                    [
                        "nc_count"    => 1,
                        "admin_email" => "abhishek.m@razorpay.com"
                    ],
                "group_name"    => "contact_name",
                "merchant_id"   => $mid,
                "comment_data"  => [
                    "text" => "provide_poc",
                    "type" => "predefined"
                ],
                "message_from"  => "admin",
                "field_details" => [
                    "contact_name" => "spicy chaii"
                ]
            ],
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $clarificationDetail = (new \RZP\Models\ClarificationDetail\Repository)->find('LOe7ZFgd9eOawB')->ToArray();

        $this->assertArraySubset([
                                     "id"            => "LOe7ZFgd9eOawB",
                                     "status"        => "submitted",
                                     "metadata"      =>
                                         [
                                             "nc_count"    => 1,
                                             "admin_email" => "abhishek.m@razorpay.com"
                                         ],
                                     "group_name"    => "contact_name",
                                     "merchant_id"   => $mid,
                                     "comment_data"  => [
                                         "text" => "provide_poc",
                                         "type" => "predefined"
                                     ],
                                     "message_from"  => "admin",
                                     "field_details" => [
                                         "contact_name" => "spicy chaii"
                                     ]
                                 ], $clarificationDetail);

        $merchantDetail1 = (new \RZP\Models\Merchant\Detail\Repository)->find($mid);

        $this->assertArraySubset([
                                     "clarification_reasons"    =>
                                         [
                                             "contact_name" =>
                                                 [
                                                     [
                                                         "from"        => "admin",
                                                         "nc_count"    => 1,
                                                         "is_current"  => true,
                                                         "reason_code" => "provide_poc",
                                                         "reason_type" => "predefined"
                                                     ]
                                                 ]
                                         ],
                                     "clarification_reasons_v2" => [
                                         "contact_name" => [
                                             [
                                                 "from"        => "admin",
                                                 "nc_count"    => 1,
                                                 "is_current"  => true,
                                                 "reason_code" => "provide_poc",
                                                 "reason_type" => "predefined"
                                             ]
                                         ]
                                     ],
                                     "nc_count"                 => 1
                                 ], $merchantDetail1->getKycClarificationReasons());
    }

    public function testSaveMerchantsPGOSDataToAPIFor99PercentMerchants()
    {
        $mid = 'KqsQEszAud2PqZ';

        $this->createAndFetchMocks($mid, true, 'api');

        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "merchants",
            "type"                => "update",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                 => $mid,
                "stakeholder"        => [
                    "stakeholder_name"  => "Shwetabh Shekhar",
                    "stakeholder_email" => ""
                ],
                "country_code"       => null,
                "business_details"   => [
                    "billing_label"               => "CHIZRINZ INFOWAY PRIVATE",
                    "business_type"               => "llp",
                    "business_operation_address"  => null,
                    "business_registered_address" => null,
                ],
                "business_identity"  => [
                    "company_pan"      => "ABCCD1235B",
                    "promoter_pan"     => "ABCPD1234A",
                    "company_pan_name" => "CHIZRINZ INFOWAY PRIVATE",
                ],
                "payment_preference" => [
                    "others"              => "Others",
                    "social_media"        => false,
                    "others_present"      => true,
                    "physical_store"      => false,
                    "ios_app_present"     => false,
                    "website_present"     => false,
                    "android_app_present" => false
                ]
            ],
            "old"                 => [
                "stakeholder" => [
                    "stakeholder_name"  => "Shwetabh Shekhar old",
                    "stakeholder_email" => ""
                ]
            ]
        ];

        (new Service)->savePGOSDataToAPI($data);

        $merchant1 = (new \RZP\Models\Merchant\Repository)->find($mid);

        $this->assertArraySubset(["id"            => "KqsQEszAud2PqZ",
                                  "name"          => null,
                                  "email"         => null,
                                  "billing_label" => null],
                                 $merchant1->toArray());

        $merchantDetail1 = (new \RZP\Models\Merchant\Detail\Repository)->find($mid);

        $this->assertArraySubset(["merchant_id"   => $mid,
                                  "contact_name"  => null,
                                  "contact_email" => null,
                                  "business_dba"  => null,
                                  "company_pan"   => null,
                                  "promoter_pan"  => null,
                                 ],
                                 $merchantDetail1->toArray());

        $stakeholders = (new \RZP\Models\Merchant\Stakeholder\Repository)->fetchStakeholders($mid);

        $this->assertArraySubset(['name'                => "stakeholder name",
                            'email'                     => NULL,
                            'poi_identification_number' => NULL
                           ], $stakeholders[0]->toArray());

        $merchantBusinessDetail1 = (new \RZP\Models\Merchant\BusinessDetail\Repository)->getBusinessDetailsForMerchantId($mid);

        $this->assertEmpty($merchantBusinessDetail1);

    }

    public function testSaveMerchantsPGOSDataToAPIExperimentDisabled()
    {
        $this->markTestSkipped('owners will fix this test');

        $mid = UniqueIdEntity::generateUniqueId();

        $this->createAndFetchMocks($mid,false,'pgos');

        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "merchants",
            "type"                => "update",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                 => $mid,
                "stakeholder"        => [
                    "stakeholder_name"  => "Shwetabh Shekhar",
                    "stakeholder_email" => ""
                ],
                "country_code"       => null,
                "business_details"   => [
                    "billing_label"               => "CHIZRINZ INFOWAY PRIVATE",
                    "business_type"               => "llp",
                    "business_operation_address"  => null,
                    "business_registered_address" => null,
                ],
                "business_identity"  => [
                    "company_pan"      => "ABCCD1235B",
                    "promoter_pan"     => "ABCPD1234A",
                    "company_pan_name" => "CHIZRINZ INFOWAY PRIVATE",
                ],
                "payment_preference" => [
                    "others"              => "Others",
                    "social_media"        => false,
                    "others_present"      => true,
                    "physical_store"      => false,
                    "ios_app_present"     => false,
                    "website_present"     => false,
                    "android_app_present" => false
                ]
            ],
            "old"                 => [
                "stakeholder" => [
                    "stakeholder_name"  => "Shwetabh Shekhar old",
                    "stakeholder_email" => ""
                ]
            ]
        ];

        (new Service)->savePGOSDataToAPI($data);

        $merchant1 = (new \RZP\Models\Merchant\Repository)->find($mid);

        $this->assertArraySubset(["id"            => $mid,
                                  "name"          => "Shwetabh Shekhar",
                                  "email"         => "",
                                  "billing_label" => "CHIZRINZ INFOWAY PRIVATE"],
                                 $merchant1->toArray());

        $merchantDetail1 = (new \RZP\Models\Merchant\Detail\Repository)->find($mid);

        $this->assertArraySubset(["merchant_id"   => $mid,
                                  "contact_name"  => "Shwetabh Shekhar",
                                  "contact_email" => "",
                                  "business_dba"  => "CHIZRINZ INFOWAY PRIVATE",
                                  "company_pan"   => "ABCCD1235B",
                                  "promoter_pan"  => "ABCPD1234A",
                                 ],
                                 $merchantDetail1->toArray());

        $stakeholders = (new \RZP\Models\Merchant\Stakeholder\Repository)->fetchStakeholders($mid);

        $this->assertArraySubset(['name'                      => "Shwetabh Shekhar",
                                  'email'                     => "",
                                  'poi_identification_number' => "ABCPD1234A"
                                 ], $stakeholders[0]->toArray());

        $merchantBusinessDetail1 = (new \RZP\Models\Merchant\BusinessDetail\Repository)->getBusinessDetailsForMerchantId($mid);

        $this->assertNotEmpty($merchantBusinessDetail1);

    }

    public function testSaveVerificationsPGOSDataToAPIFor99PercentMerchants()
    {

        $mid='LDWO4rOnPQTjan';

        $this->createAndFetchMocks($mid,true,'api');

        //insert success verification
        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "verification_details",
            "type"                => "insert",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                  => "LDXmdN7bVNs9Qo",
                "metadata"            => [
                    "platform"       => "bvs",
                    "bvs_probe_id"   => "LDWTUJHJBsTMXS",
                    "aadhaar_linked" => "1"
                ],
                "created_at"          => 1675770614,
                "updated_at"          => 1675770614,
                "merchant_id"         => "LDWO4rOnPQTjan",
                "artefact_type"       => "aadhaar_esign",
                "verification_id"     => "LDWTUJHJBsTMXS",
                "verification_unit"   => "auth",
                "verification_status" => "verified"
            ],
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $stakeholder1 = $this->getDbLastEntity('stakeholder', 'live');

        $this->assertArraySubset(["merchant_id"          => $mid,
                                  "aadhaar_esign_status" => null,
                                  "bvs_probe_id"         => null,
                                  "aadhaar_linked"       => "1"],
                                 $stakeholder1->toArray());


        $verificationDetail = (new \RZP\Models\Merchant\VerificationDetail\Repository())->getDetailsForTypeAndIdentifier(
            $mid,
            'aadhaar_esign',
            'number'
        );

        $this->assertEmpty($verificationDetail);

        $bvsDetail = (new \RZP\Models\Merchant\BvsValidation\Repository())->getLatestArtefactValidationForOwnerId(
            $mid,
            'aadhaar_esign',
            'identifier',
            'merchant'
        );

        $this->assertEmpty($bvsDetail);

    }

    public function testSaveVerificationsPGOSDataToAPIExperimentDisabled()
    {
        $this->markTestSkipped('owners will fix this test');

        $mid = UniqueIdEntity::generateUniqueId();

        $this->createAndFetchMocks($mid,false,'pgos');

        //insert success verification
        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "verification_details",
            "type"                => "insert",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                  => "LDXmdN7bVNs9Qo",
                "metadata"            => [
                    "platform"       => "bvs",
                    "bvs_probe_id"   => "LDWTUJHJBsTMXS",
                    "aadhaar_linked" => "1"
                ],
                "created_at"          => 1675770614,
                "updated_at"          => 1675770614,
                "merchant_id"         => $mid,
                "artefact_type"       => "aadhaar_esign",
                "verification_id"     => "LDWTUJHJBsTMXS",
                "verification_unit"   => "auth",
                "verification_status" => "verified"
            ],
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $stakeholder1 = $this->getDbLastEntity('stakeholder', 'live');

        $this->assertArraySubset(["merchant_id"          => $mid,
                                  "aadhaar_esign_status" => "verified",
                                  "bvs_probe_id"         => "LDWTUJHJBsTMXS",
                                  "aadhaar_linked"       => 1],
                                 $stakeholder1->toArray());


        $verificationDetail = (new \RZP\Models\Merchant\VerificationDetail\Repository())->getDetailsForTypeAndIdentifier(
            $mid,
            'aadhaar_esign',
            'number'
        );

        $this->assertNotEmpty($verificationDetail);

        $bvsDetail = (new \RZP\Models\Merchant\BvsValidation\Repository())->getLatestArtefactValidationForOwnerId(
            $mid,
            'aadhaar_esign',
            'identifier',
            'merchant'
        );

        $this->assertNotEmpty($bvsDetail);

    }

    public function testSaveDocumentsPGOSDataToAPIExperimentDisabled()
    {
        $this->markTestSkipped('owners will fix this test');

        $mid = UniqueIdEntity::generateUniqueId();

        $this->createAndFetchMocks($mid,false,'pgos');

        //insert
        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "documents",
            "type"                => "insert",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                 => "L0QSK3lNI9nl37",
                "deleted_at"         => null,
                "merchant_id"        => $mid,
                "document_type"      => "sebi_registration_certificate",
                "file_store_id"      => "L0QSJzjt0UMwSG",
                "upload_by_admin_id" => null
            ],
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $document = (new \RZP\Models\Merchant\Document\Repository())->findDocumentByFileStoreId('L0QSJzjt0UMwSG');

        $this->assertNotEmpty($document);

    }

    public function testSaveDocumentsPGOSDataToAPIFor99PercentMerchants()
    {
        $this->createAndFetchMocks('OPMRlog41YXkli',true,'api');

        //insert
        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "documents",
            "type"                => "insert",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                 => "L0QSK3lNI9nl37",
                "deleted_at"         => null,
                "merchant_id"        => "OPMRlog41YXkli",
                "document_type"      => "sebi_registration_certificate",
                "file_store_id"      => "L0QSJzjt0UMwSG",
                "upload_by_admin_id" => null
            ],
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $document = (new \RZP\Models\Merchant\Document\Repository())->findDocumentByFileStoreId('L0QSJzjt0UMwSG');

        $this->assertEmpty($document);
    }

    public function testSaveOnboardingDataToAPIExperimentDisabled()
    {
        $this->markTestSkipped('owners will fix this test');

        $mid='KqsQEszAud2PqZ';

        $this->createAndFetchMocks($mid,false,'pgos');

        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "onboarding_details",
            "type"                => "update",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "locked"                        => 0,
                "activated"                     => 1,
                "submitted"                     => 1,
                "merchant_id"                   => $mid,
                "activated_at"                  => 1673248693,
                "submitted_at"                  => 1673248693,
                "signup_source"                 => null,
                "activation_flow"               => "whitelist",
                "signup_campaign"               => null,
                "activation_status"             => null,
                "activation_progress"           => 60,
                "activation_form_milestone"     => "L1",
                "international_activation_flow" => null
            ]
            ,
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $merchant1 = (new \RZP\Models\Merchant\Repository)->find($mid);

        $this->assertArraySubset(["id"           => $mid,
                                  "activated"    => 1
                                 ],
                                 $merchant1->toArray());

        $merchantDetail1 = (new \RZP\Models\Merchant\Detail\Repository)->find($mid);

        $this->assertArraySubset(["merchant_id"               => $mid,
                                  "submitted"                 => 1,
                                  "activation_flow"           => "whitelist",
                                  "activation_form_milestone" => "L1",
                                 ],
                                 $merchantDetail1->toArray());
    }

    public function testSaveOnboardingDataToAPIFor99PercentMerchants()
    {
        $mid = 'KqsQEszAud2PqZ';

        $this->createAndFetchMocks($mid, true, 'api');

        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "onboarding_details",
            "type"                => "update",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "locked"                        => 0,
                "activated"                     => 1,
                "submitted"                     => 1,
                "merchant_id"                   => $mid,
                "activated_at"                  => 1673248693,
                "submitted_at"                  => 1673248693,
                "signup_source"                 => null,
                "activation_flow"               => "whitelist",
                "signup_campaign"               => null,
                "activation_status"             => null,
                "activation_progress"           => 60,
                "activation_form_milestone"     => "L1",
                "international_activation_flow" => null
            ]
            ,
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $merchant1 = (new \RZP\Models\Merchant\Repository)->find($mid);

        $this->assertArraySubset(["id"        => $mid,
                                  "activated" => false
                                 ],
                                 $merchant1->toArray());

        $merchantDetail1 = (new \RZP\Models\Merchant\Detail\Repository)->find($mid);

        $this->assertArraySubset(["merchant_id"               => $mid,
                                  "submitted"                 => false,
                                  "activation_flow"           => null,
                                  "activation_form_milestone" => null,
                                 ],
                                 $merchantDetail1->toArray());

    }

    public function testSaveWebsiteDataToAPIFor99PercentMerchants()
    {
        $mid='KqsQEszAud2PqZ';

        $this->createAndFetchMocks($mid,true,'api');

        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "website_details",
            "type"                => "update",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                 => "LGaOT076nCxB6a",
                "status"             => null,
                "metadata"           => [
                    "additional_websites" => [
                        "https://www.facebook.com/",
                        "https://hello.com"
                    ],
                    "whitelisted_domains" => [
                        "facebook.com",
                        "microsoft.com",
                        "hello.com"
                    ]
                ],
                "merchant_id"        => $mid,
                "appstore_url"       => "https://apps.apple.com/in/app/apple-store/id375380948",
                "grace_period"       => null,
                "playstore_url"      => "https://play.google.com/store/apps/details?id=com.xd.fpos.ad",
                "business_website"   => "https://myapplications.microsoft.com/",
                "send_communication" => null
            ],
            "old"                 => []
        ];
        (new Service)->savePGOSDataToAPI($data);

        $merchant1 = (new \RZP\Models\Merchant\Repository)->find($mid);

        $this->assertArraySubset(["id"                  => $mid,
                                  "website"             => null,
                                  "whitelisted_domains" => null],
                                 $merchant1->toArray());

        $merchantDetail1 = (new \RZP\Models\Merchant\Detail\Repository)->find($mid);

        $this->assertArraySubset(["merchant_id"         => $mid,
                                  "business_website"    => null,
                                  "additional_websites" => null,
                                 ],
                                 $merchantDetail1->toArray());

        $merchantBusinessDetail1 = (new \RZP\Models\Merchant\BusinessDetail\Repository)->getBusinessDetailsForMerchantId($mid);

        $this->assertEmpty($merchantBusinessDetail1);
    }

    public function testSaveWebsiteDataToAPIExperimentDisabled()
    {
        $this->markTestSkipped('owners will fix this test');

        $mid = UniqueIdEntity::generateUniqueId();

        $this->createAndFetchMocks($mid,false,'pgos');

        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "website_details",
            "type"                => "update",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"                 => "LGaOT076nCxB6a",
                "status"             => null,
                "metadata"           => [
                    "additional_websites" => [
                        "https://www.facebook.com/",
                        "https://hello.com"
                    ],
                    "whitelisted_domains" => [
                        "facebook.com",
                        "microsoft.com",
                        "hello.com"
                    ]
                ],
                "merchant_id"        => $mid,
                "appstore_url"       => "https://apps.apple.com/in/app/apple-store/id375380948",
                "grace_period"       => null,
                "playstore_url"      => "https://play.google.com/store/apps/details?id=com.xd.fpos.ad",
                "business_website"   => "https://myapplications.microsoft.com/",
                "send_communication" => null
            ],
            "old"                 => []
        ];
        (new Service)->savePGOSDataToAPI($data);

        $merchant1 = (new \RZP\Models\Merchant\Repository)->find($mid);

        $this->assertArraySubset(["id"                  => $mid,
                                  "website"             => "https://myapplications.microsoft.com/",
                                  "whitelisted_domains" => [
                                      "facebook.com",
                                      "microsoft.com",
                                      "hello.com"
                                  ]],
                                 $merchant1->toArray());

        $merchantDetail1 = (new \RZP\Models\Merchant\Detail\Repository)->find($mid);

        $this->assertArraySubset(["merchant_id"         => $mid,
                                  "business_website"    => "https://myapplications.microsoft.com/",
                                  "additional_websites" => [
                                      "https://www.facebook.com/",
                                      "https://hello.com"
                                  ],
                                 ],
                                 $merchantDetail1->toArray());

        $merchantBusinessDetail1 = (new \RZP\Models\Merchant\BusinessDetail\Repository)->getBusinessDetailsForMerchantId($mid);

        $this->assertNotEmpty($merchantBusinessDetail1);
    }

    public function testSaveClarificationDetailsToAPIFor99PercentMerchants()
    {
        $mid='LKDtR1ECoLNx5g';

        $this->createAndFetchMocks($mid,true,'api');

        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "clarification_details",
            "type"                => "update",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"            => "LOe7ZFgd9eOawB",
                "status"        => "submitted",
                "metadata"      =>
                    [
                        "nc_count"    => 1,
                        "admin_email" => "abhishek.m@razorpay.com"
                    ],
                "group_name"    => "contact_name",
                "merchant_id"   => $mid,
                "comment_data"  => [
                    "text" => "provide_poc",
                    "type" => "predefined"
                ],
                "message_from"  => "admin",
                "field_details" => [
                    "contact_name" => "spicy chaii"
                ]
            ],
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $clarificationDetail = (new \RZP\Models\ClarificationDetail\Repository)->find('LOe7ZFgd9eOawB');

        $this->assertEmpty($clarificationDetail);

        $merchantDetail1 = (new \RZP\Models\Merchant\Detail\Repository)->find($mid);

        $this->assertNull($merchantDetail1->getKycClarificationReasons());
    }

    public function testSaveClarificationDetailsToAPIExperimentDisabled()
    {
        $this->markTestSkipped('owners will fix this test');

        $mid = UniqueIdEntity::generateUniqueId();

        $this->createAndFetchMocks($mid,false,'pgos');

        $data = [
            "database"            => "stage-pg_onboarding_service",
            "table"               => "clarification_details",
            "type"                => "update",
            "ts"                  => 1673248693,
            "xid"                 => 1389385481,
            "commit"              => true,
            "position"            => "mysql-bin-changelog.008996=>7826736",
            "primary_key_columns" => [
                "id"
            ],
            "data"                => [
                "id"            => "LOe7ZFgd9eOawB",
                "status"        => "submitted",
                "metadata"      =>
                    [
                        "nc_count"    => 1,
                        "admin_email" => "abhishek.m@razorpay.com"
                    ],
                "group_name"    => "contact_name",
                "merchant_id"   => $mid,
                "comment_data"  => [
                    "text" => "provide_poc",
                    "type" => "predefined"
                ],
                "message_from"  => "admin",
                "field_details" => [
                    "contact_name" => "spicy chaii"
                ]
            ],
            "old"                 => []
        ];

        (new Service)->savePGOSDataToAPI($data);

        $clarificationDetail = (new \RZP\Models\ClarificationDetail\Repository)->find('LOe7ZFgd9eOawB');

        $this->assertNotEmpty($clarificationDetail);

        $merchantDetail1 = (new \RZP\Models\Merchant\Detail\Repository)->find($mid);

        $this->assertNotNull($merchantDetail1->getKycClarificationReasons());
    }
}

