<?php


namespace Unit\Models\Merchant\Bvs\DocumentStatusUpdater;

use Config;
use RZP\Models\Feature\Core;
use RZP\Models\Feature\Entity;
use RZP\Services\RazorXClient;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Services\KafkaMessageProcessor;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Store\Constants as StoreConstants;

class PersonalPanStatusUpdaterTest extends TestCase
{
    use DbEntityFetchTrait;

    private function processKafkaEvent($validationStatus, $expectedVerificationStatus)
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mid = $merchantDetail->getId();

        $bvsValidation = $this->fixtures->create('bvs_validation',
                                                 [
                                                     'owner_id'      => $mid,
                                                     'artefact_type' => Constant::PERSONAL_PAN,
                                                 ]);

        $kafkaEventPayload = [
            'data'  => [
                'validation_id'     => $bvsValidation->getValidationId(),
                'status'            => $validationStatus,
                'error_description' => '',
                'error_code'        => ''
            ]
        ];

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_BVS_EVENTS, $kafkaEventPayload, 'live');

        $bvsValidation = $this->getDbEntityById('bvs_validation', $bvsValidation->getValidationId());
        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertEquals($validationStatus, $bvsValidation->getValidationStatus());
        $this->assertEquals($expectedVerificationStatus, $merchantDetail->getPoiVerificationStatus());
    }

    public function testSuccessEvent()
    {
        $this->processKafkaEvent('success', 'verified');
    }

    public function testFailureEvent()
    {
        $this->processKafkaEvent('failed', 'failed');
    }

    public function testSuccessEventForNoDocFeature()
    {
        $validationStatus = 'success';
        $expectedVerificationStatus ='verified';
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mid = $merchantDetail->getId();

        $featureParams = [
            Entity::ENTITY_ID   => $mid,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => Constants::NO_DOC_ONBOARDING,
        ];

        (new Core())->create($featureParams,true);

        $bvsValidation = $this->fixtures->create('bvs_validation',
            [
                'owner_id'      => $mid,
                'artefact_type' => Constant::PERSONAL_PAN,
            ]);

        $namespace = ConfigKey::ONBOARDING_NAMESPACE;

        $value = [
            'value' => ['09AAACR5055K1Z5'],
            'current_index' =>0,
            'retryCount' => 0,
            'status' => 'passed',
        ];

        $noDocData = [
            'verification' => [
                'gstin' => $value,
                'contact_mobile' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ],
                'promoter_pan' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ],
                'company_pan' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ],
                'bank_account_number' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ]
            ],
            'dedupe'    => [
                'gstin' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ],
                'contact_mobile' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ],
                'promoter_pan' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ],
                'company_pan' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ],
                'bank_account_number' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ]
            ]
        ];

        $data = [
            StoreConstants::NAMESPACE  => $namespace,
            ConfigKey::NO_DOC_ONBOARDING_INFO => $noDocData
        ];

        $data = (new StoreCore())->updateMerchantStore($mid, $data, StoreConstants::INTERNAL);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $kafkaEventPayload = [
            'data'  => [
                'validation_id'     => $bvsValidation->getValidationId(),
                'status'            => $validationStatus,
                'error_description' => '',
                'error_code'        => ''
            ]
        ];

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_BVS_EVENTS, $kafkaEventPayload, 'live');

        $bvsValidation = $this->getDbEntityById('bvs_validation', $bvsValidation->getValidationId());
        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertEquals($validationStatus, $bvsValidation->getValidationStatus());
        $this->assertEquals($expectedVerificationStatus, $merchantDetail->getPoiVerificationStatus());
    }

    public function testFailureEventForNoDocFeature()
    {
        $validationStatus = 'failed';
        $expectedVerificationStatus ='failed';

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mid = $merchantDetail->getId();

        $featureParams = [
            Entity::ENTITY_ID   => $mid,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => Constants::NO_DOC_ONBOARDING,
        ];

        (new Core())->create($featureParams,true);

        $value = [
            'value' => ['09AAACR5055K1Z5'],
            'current_index' =>0,
            'retryCount' => 0,
            'status' => 'passed',
        ];

        $noDocData = [
            'verification' => [
                'gstin' => $value,
                'contact_mobile' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ],
                'promoter_pan' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ],
                'company_pan' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ],
                'bank_account_number' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ]
            ],
            'dedupe'    => [
                'gstin' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ],
                'contact_mobile' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ],
                'promoter_pan' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ],
                'company_pan' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ],
                'bank_account_number' => [
                    'retryCount' => 0,
                    'status' => 'passed',
                ]
            ]
        ];

        $data = [
            StoreConstants::NAMESPACE  => ConfigKey::ONBOARDING_NAMESPACE,
            ConfigKey::NO_DOC_ONBOARDING_INFO => $noDocData
        ];

        $data = (new StoreCore())->updateMerchantStore($mid, $data, StoreConstants::INTERNAL);

        $bvsValidation = $this->fixtures->create('bvs_validation',
            [
                'owner_id'      => $mid,
                'artefact_type' => Constant::PERSONAL_PAN,
            ]);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $kafkaEventPayload = [
            'data'  => [
                'validation_id'     => $bvsValidation->getValidationId(),
                'status'            => $validationStatus,
                'error_description' => '',
                'error_code'        => ''
            ]
        ];

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_BVS_EVENTS, $kafkaEventPayload, 'live');

        $bvsValidation = $this->getDbEntityById('bvs_validation', $bvsValidation->getValidationId());
        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertEquals($validationStatus, $bvsValidation->getValidationStatus());
        $this->assertEquals($expectedVerificationStatus, $merchantDetail->getPoiVerificationStatus());
    }

    public function testPoiVerifiedTriggersInstantlyActivate()
    {
        $this->mockRazorxTreatment();

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'activation_form_milestone' => 'L1',
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'fashion_and_lifestyle',
        ]);

        $mid = $merchantDetail->getId();

        $this->fixtures->merchant->edit($mid, [
            MerchantEntity::CATEGORY          => '5399',
            MerchantEntity::CATEGORY2         => 'ecommerce',
        ]);

        $bvsValidation = $this->fixtures->create('bvs_validation',
                                                 [
                                                     'owner_id'      => $mid,
                                                     'artefact_type' => Constant::PERSONAL_PAN,
                                                 ]);

        $kafkaEventPayload = [
            'data'  => [
                'validation_id'     => $bvsValidation->getValidationId(),
                'status'            => 'success',
                'error_description' => '',
                'error_code'        => ''
            ]
        ];

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_BVS_EVENTS, $kafkaEventPayload, 'live');

        $merchant = $this->getDbLastEntity('merchant');

        $this->assertEquals(true, $merchant->isActivated());
    }

    protected function mockRazorxTreatment(string $returnValue = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn($returnValue);
    }
}
