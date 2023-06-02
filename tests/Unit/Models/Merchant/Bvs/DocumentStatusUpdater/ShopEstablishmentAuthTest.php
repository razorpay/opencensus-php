<?php


namespace Unit\Models\Merchant\Bvs\DocumentStatusUpdater;

use Mockery;
use RZP\Services\SplitzService;
use RZP\Services\KafkaMessageProcessor;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
class ShopEstablishmentAuthTest extends TestCase
{
    use DbEntityFetchTrait;

    private function createFixtures()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', ['business_type' => '1',]);

        $mid = $merchantDetail->getId();

        return $mid;
    }

    private function processKafkaEvent($validationStatus, $signatoryVerifationStatus, $ruleExecutionList, $variant, $errorCode = '')
    {
        $mid = $this->createFixtures();

        $this->mockSplitzTreatment($mid, $variant);

        $bvsValidation = $this->fixtures->create('bvs_validation',
            [
                'owner_id'          => $mid,
                'artefact_type'     => Constant::SHOP_ESTABLISHMENT,
                'validation_unit'   => 'identifier',
            ]);

        $kafkaEventPayload = [
            'data' => [
                'validation_id'         => $bvsValidation->getValidationId(),
                'status'                => $validationStatus,
                'error_description'     => '',
                'error_code'            => $errorCode,
                'rule_execution_list'   => $ruleExecutionList
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-validation-result-events', $kafkaEventPayload, 'live');

        $bvsValidation = $this->getDbEntityById('bvs_validation', $bvsValidation->getValidationId());

        $this->assertEquals($validationStatus, $bvsValidation->getValidationStatus());
    }

    // Test scenario bvs validation is success with rule_execution_list absent in BVS response
    public function testShopEstablishmentAuthVerificationStatusForSuccessEvent()
    {
        $this->processKafkaEvent('success', 'verified', [], '');
    }

    // Test scenario bvs validation is failed with rule_execution_list absent in BVS response
    public function testShopEstablishmentAuthVerificationStatusForFailureEvent()
    {
        $this->processKafkaEvent('failed', 'failed', [], '');
    }


    // Test scenario where Signatory and Business name is verified with rule_execution_list present in BVS response
    public function testShopEstablishmentAuthVerificationSignatorySuccessAndExperimentOn()
    {
        $kafkaPayload = [

            '0' => [
                "rule" => [
                    "rule_def" => [
                        "fuzzy_suzzy" => [
                            [
                                "var" => "artefact.details.owner_name.value"
                            ],
                            [
                                "var" => "enrichments.online_provider.details.owner_name.value"
                            ],
                            81
                        ]
                    ],
                    "rule_type" => "string_comparison_rule"
                ],
                "error" => "",
                "rule_execution_result" => [
                    "result" => true,
                    "remarks" => [
                        "algorithm_type" => "fuzzy_suzzy_lev_token_set_algorithm",
                        "match_percentage" => 96,
                        "required_percentage" => 81
                    ],
                    "operands" => [
                        "operand_1" => "suresh sahani",
                        "operand_2" => "Suresh Sahni",
                        "operand_3" => 81
                    ],
                    "operator" => "fuzzy_suzzy"
                ]
            ],
            '1' => [
                "rule" => [
                    "rule_def" => [
                        "fuzzy_wuzzy" => [
                            [
                                "var" => "artefact.details.entity_name.value"
                            ],
                            [
                                "var" => "enrichments.online_provider.details.entity_name.value"
                            ],
                            81,
                            [
                                "private limited",
                                "limited liability partnership",
                                "pvt",
                                "ltd",
                                "."
                            ]
                        ]
                    ],
                    "rule_type" => "string_comparison_rule"
                ],
                "error" => "",
                "rule_execution_result" => [
                    "result" => true,
                    "remarks" => [
                        "algorithm_type" => "fuzzy_wuzzy_default_algorithm",
                        "match_percentage" => 74,
                        "required_percentage" => 81
                    ],
                    "operands" => [
                        "operand_1" => "suresh fast food and tiffin serices",
                        "operand_2" => "Suresh Tiffin Services",
                        "operand_3" => 81,
                        "operand_4" => [
                            "private limited",
                            "limited liability partnership",
                            "pvt",
                            "ltd",
                            "."
                        ]
                    ],
                    "operator" => "fuzzy_wuzzy"
                ]
            ]
        ];

        $this->processKafkaEvent('success', 'verified', $kafkaPayload, 'true');

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail', 'live');

        $this->assertEquals('verified', $verificationDetail->getStatus());

        $bvsValidation = $this->getDbEntity('bvs_validation', ['artefact_type' => 'shop_establishment']);

        $verificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'shop_establishment']);

        $this->assertEquals(["signatory_validation_status" => "verified", 'bvs_validation_id' => $bvsValidation['validation_id']], $verificationDetail['metadata']);

    }

    // Test scenario where Business name is verified but signatory match is failed with rule_execution_list present in BVS response
    public function testShopEstablishmentAuthVerificationSignatoryFailureAndExperimentOn()
    {
        $kafkaPayload = [

            '0' => [
                "rule" => [
                    "rule_def" => [
                        "fuzzy_suzzy" => [
                            [
                                "var" => "artefact.details.owner_name.value"
                            ],
                            [
                                "var" => "enrichments.online_provider.details.owner_name.value"
                            ],
                            81
                        ]
                    ],
                    "rule_type" => "string_comparison_rule"
                ],
                "error" => "",
                "rule_execution_result" => [
                    "result" => false,
                    "remarks" => [
                        "algorithm_type" => "fuzzy_suzzy_lev_token_set_algorithm",
                        "match_percentage" => 96,
                        "required_percentage" => 81
                    ],
                    "operands" => [
                        "operand_1" => "suresh sahani",
                        "operand_2" => "Suresh Sahni",
                        "operand_3" => 81
                    ],
                    "operator" => "fuzzy_suzzy"
                ]
            ],
            '1' => [
                "rule" => [
                    "rule_def" => [
                        "fuzzy_wuzzy" => [
                            [
                                "var" => "artefact.details.entity_name.value"
                            ],
                            [
                                "var" => "enrichments.online_provider.details.entity_name.value"
                            ],
                            81,
                            [
                                "private limited",
                                "limited liability partnership",
                                "pvt",
                                "ltd",
                                "."
                            ]
                        ]
                    ],
                    "rule_type" => "string_comparison_rule"
                ],
                "error" => "",
                "rule_execution_result" => [
                    "result" => true,
                    "remarks" => [
                        "algorithm_type" => "fuzzy_wuzzy_default_algorithm",
                        "match_percentage" => 74,
                        "required_percentage" => 81
                    ],
                    "operands" => [
                        "operand_1" => "suresh fast food and tiffin serices",
                        "operand_2" => "Suresh Tiffin Services",
                        "operand_3" => 81,
                        "operand_4" => [
                            "private limited",
                            "limited liability partnership",
                            "pvt",
                            "ltd",
                            "."
                        ]
                    ],
                    "operator" => "fuzzy_wuzzy"
                ]
            ]
        ];

        $this->processKafkaEvent('failed', 'verified', $kafkaPayload, 'true', 'RULE_EXECUTION_FAILED');

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail', 'live');

        $this->assertEquals('not_matched', $verificationDetail->getStatus());

        $bvsValidation = $this->getDbEntity('bvs_validation', ['artefact_type' => 'shop_establishment']);

        $verificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'shop_establishment']);

        $this->assertEquals(["signatory_validation_status" => "not_matched", 'bvs_validation_id' => $bvsValidation['validation_id']], $verificationDetail['metadata']);

    }

    // Test scenario where Business name verification and signatory match is failed with rule_execution_list present in BVS response
    public function testShopEstablishmentAuthVerificationBusinessNameMatchFailureAndExperimentOn()
    {
        $kafkaPayload = [

            '0' => [
                "rule" => [
                    "rule_def" => [
                        "fuzzy_suzzy" => [
                            [
                                "var" => "artefact.details.owner_name.value"
                            ],
                            [
                                "var" => "enrichments.online_provider.details.owner_name.value"
                            ],
                            81
                        ]
                    ],
                    "rule_type" => "string_comparison_rule"
                ],
                "error" => "",
                "rule_execution_result" => [
                    "result" => true,
                    "remarks" => [
                        "algorithm_type" => "fuzzy_suzzy_lev_token_set_algorithm",
                        "match_percentage" => 96,
                        "required_percentage" => 81
                    ],
                    "operands" => [
                        "operand_1" => "suresh sahani",
                        "operand_2" => "Suresh Sahni",
                        "operand_3" => 81
                    ],
                    "operator" => "fuzzy_suzzy"
                ]
            ],
            '1' => [
                "rule" => [
                    "rule_def" => [
                        "fuzzy_wuzzy" => [
                            [
                                "var" => "artefact.details.entity_name.value"
                            ],
                            [
                                "var" => "enrichments.online_provider.details.entity_name.value"
                            ],
                            81,
                            [
                                "private limited",
                                "limited liability partnership",
                                "pvt",
                                "ltd",
                                "."
                            ]
                        ]
                    ],
                    "rule_type" => "string_comparison_rule"
                ],
                "error" => "",
                "rule_execution_result" => [
                    "result" => false,
                    "remarks" => [
                        "algorithm_type" => "fuzzy_wuzzy_default_algorithm",
                        "match_percentage" => 74,
                        "required_percentage" => 81
                    ],
                    "operands" => [
                        "operand_1" => "suresh fast food and tiffin serices",
                        "operand_2" => "Suresh Tiffin Services",
                        "operand_3" => 81,
                        "operand_4" => [
                            "private limited",
                            "limited liability partnership",
                            "pvt",
                            "ltd",
                            "."
                        ]
                    ],
                    "operator" => "fuzzy_wuzzy"
                ]
            ]
        ];
        $this->processKafkaEvent('failed', 'not_matched', $kafkaPayload, 'true', 'RULE_EXECUTION_FAILED');

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail', 'live');

        $this->assertEquals('not_initiated', $verificationDetail->getStatus());

        $bvsValidation = $this->getDbEntity('bvs_validation', ['artefact_type' => 'shop_establishment']);

        $verificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'shop_establishment']);

        $this->assertEquals(["signatory_validation_status" => "not_initiated", 'bvs_validation_id' => $bvsValidation['validation_id']], $verificationDetail['metadata']);

    }

    protected function mockSplitzTreatment($mid, $variantName)
    {
        $input = [
            "experiment_id" => "LhL34xFB6fki66",
            "id"            => $mid,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => $variantName,
                ]
            ]
        ];

        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->atLeast()
            ->once()
            ->with($input)
            ->andReturn($output);
    }

}
