<?php


namespace Unit\Models\Merchant\Bvs\DocumentStatusUpdater;

use Mockery;
use RZP\Services\SplitzService;
use RZP\Services\KafkaMessageProcessor;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class PartnershipDeedStatusUpdaterTest extends TestCase
{
    use DbEntityFetchTrait;

    private function createFixtures()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [ 'business_type' => '3',]);

        $mid = $merchantDetail->getId();

        $verificationDetail = $this->fixtures->create('merchant_verification_detail', [
            'merchant_id'          => $mid,
            'artefact_type'        => Constant::PARTNERSHIP_DEED,
            'artefact_identifier'  => 'doc',
        ]);

        $this->fixtures->connection('live')->create('merchant_verification_detail', $verificationDetail->toArray());

        return $mid;
    }

    private function processKafkaEvent($validationStatus, $expectedVerificationStatus, $ruleExecutionList, $variant, $errorCode = '')
    {
        $mid=$this->createFixtures();

        $this->mockSplitzTreatment($mid, $variant);

        $bvsValidation = $this->fixtures->create('bvs_validation',
                                                 [
                                                     'owner_id'        => $mid,
                                                     'artefact_type'   => Constant::PARTNERSHIP_DEED,
                                                     'validation_unit' => 'proof',
                                                 ]);
        $kafkaEventPayload = [
            'data'  => [
                'validation_id'         => $bvsValidation->getValidationId(),
                'status'                => $validationStatus,
                'error_description'     => '',
                'error_code'            => $errorCode,
                'rule_execution_list'   => $ruleExecutionList
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-validation-result-events', $kafkaEventPayload, 'live');

        $bvsValidation = $this->getDbEntityById('bvs_validation', $bvsValidation->getValidationId());

        $verificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'partnership_deed', 'merchant_id' => $mid]);

        $this->assertEquals($validationStatus, $bvsValidation->getValidationStatus());

        $this->assertEquals($expectedVerificationStatus, $verificationDetail->getStatus());
    }

    // Test scenario bvs validation is success with rule_execution_list absent in BVS response
    public function testPartnershipDeedVerificationStatusForSuccessEvent()
    {
        $this->processKafkaEvent('success', 'verified', [], '');
    }

    // Test scenario bvs validation is failed with rule_execution_list absent in BVS response
    public function testPartnershipDeedVerificationStatusForFailureEvent()
    {
        $this->processKafkaEvent('failed', 'failed', [], '');
    }

    // Test scenario where Signatory and Business name is verified with rule_execution_list present in BVS response
    public function testPartnershipDeedVerificationSignatorySuccessAndExperimentOn()
    {
       $kafkaPayload  = [
            0 => [
                'rule' => [
                    'rule_type' => 'string_comparison_rule',
                    'rule_def' => [
                        0 => [
                            'fuzzy_wuzzy' => [
                                0 => [
                                    'var' => 'artefact.details.business_name.value',
                                ],
                                1 => [
                                    'var' => 'enrichments.ocr.details.1.business_name.value',
                                ],
                                2 => 60,
                            ],
                        ],
                    ],
                ],
                'rule_execution_result' => [
                    'result' => true,
                    'operator' => 'fuzzy_wuzzy',
                    'operands' => [
                        'operand_1' => 'ORANGEE CLOTHINGLINE',
                        'operand_2' => 'ORANGEE CLOTHINGLINE',
                        'operand_3' => 60,
                        'operand_4' => [
                            "private limited",
                            "limited liability partnership",
                            "pvt",
                            "ltd",
                            "."
                        ]
                    ],
                    'remarks' => [
                        'algorithm_type'        => 'fuzzy_wuzzy_custom_algorithm_4',
                        'match_percentage'      => 100,
                        'required_percentage'   => 60,
                    ],
                ],
                'error' => '',
            ],
            1 => [
                'rule' => [
                    'rule_type' => 'array_comparison_rule',
                    'rule_def' => [
                        'any' => [
                            0 => [
                                'var' => 'enrichments.ocr.details.1.name_of_partners',
                            ],
                            1 => [
                                'fuzzy_wuzzy' => [
                                    0 => [
                                        'var' => 'each_array_element',
                                    ],
                                    1 => [
                                        'var' => 'artefact.details.name_of_partners',
                                    ],
                                    2 => 60,
                                ],
                            ],
                        ],
                    ],
                ],
                'rule_execution_result' => [
                    'result'   => true,
                    'operator' => 'some',
                    'operands' => [
                        'operand_1' => [
                            'result'   => true,
                            'operator' => 'fuzzy_wuzzy',
                            'operands' => [
                                'operand_1' => 'HARSHILMATHUR ',
                                'operand_2' => 'HARSHILMATHUR',
                                'operand_3' => 70,
                            ],
                            'remarks' => [
                                'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                'match_percentage'      => 100,
                                'required_percentage'   => 70,
                            ],
                        ],
                        'operand_2' => [
                            'result'   => false,
                            'operator' => 'fuzzy_wuzzy',
                            'operands' => [
                                'operand_1' => 'Shashank kumar ',
                                'operand_2' => 'Rzp Test QA Merchant',
                                'operand_3' => 70,
                            ],
                            'remarks' => [
                                'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                'match_percentage'      => 29,
                                'required_percentage'   => 70,
                            ],
                        ],
                    ],
                    'remarks' => [
                        'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                        'match_percentage'      => 29,
                        'required_percentage'   => 70,
                    ],
                ]
            ]
        ];

        $this->processKafkaEvent('success', 'verified', $kafkaPayload, 'true');

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail', 'live');

        $this->assertEquals('verified', $verificationDetail->getStatus());
    }

    // Test scenario where Business name is verified but signatory match is failed with rule_execution_list present in BVS response
    public function testPartnershipDeedVerificationSignatoryFailureAndExperimentOn()
    {
       $kafkaPayload  = [
            0 => [
                'rule' => [
                    'rule_type' => 'string_comparison_rule',
                    'rule_def' => [
                        0 => [
                            'fuzzy_wuzzy' => [
                                0 => [
                                    'var' => 'artefact.details.business_name.value',
                                ],
                                1 => [
                                    'var' => 'enrichments.ocr.details.1.business_name.value',
                                ],
                                2 => 60,
                            ],
                        ],
                    ],
                ],
                'rule_execution_result' => [
                    'result' => true,
                    'operator' => 'fuzzy_wuzzy',
                    'operands' => [
                        'operand_1' => 'ORANGEE CLOTHINGLINE',
                        'operand_2' => 'ORANGEE CLOTHINGLINE',
                        'operand_3' => 60,
                        'operand_4' => [
                            "private limited",
                            "limited liability partnership",
                            "pvt",
                            "ltd",
                            "."
                        ]
                    ],
                    'remarks' => [
                        'algorithm_type'        => 'fuzzy_wuzzy_custom_algorithm_4',
                        'match_percentage'      => 100,
                        'required_percentage'   => 60,
                    ],
                ],
                'error' => '',
            ],
            1 => [
                'rule' => [
                    'rule_type' => 'array_comparison_rule',
                    'rule_def' => [
                        'any' => [
                            0 => [
                                'var' => 'enrichments.ocr.details.1.name_of_partners',
                            ],
                            1 => [
                                'fuzzy_wuzzy' => [
                                    0 => [
                                        'var' => 'each_array_element',
                                    ],
                                    1 => [
                                        'var' => 'artefact.details.name_of_partners',
                                    ],
                                    2 => 60,
                                ],
                            ],
                        ],
                    ],
                ],
                'rule_execution_result' => [
                    'result'   => false,
                    'operator' => 'some',
                    'operands' => [
                        'operand_1' => [
                            'result'   => true,
                            'operator' => 'fuzzy_wuzzy',
                            'operands' => [
                                'operand_1' => 'HARSHILMATHUR ',
                                'operand_2' => 'RZP TEST',
                                'operand_3' => 70,
                            ],
                            'remarks' => [
                                'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                'match_percentage'      => 30,
                                'required_percentage'   => 70,
                            ],
                        ],
                        'operand_2' => [
                            'result'   => false,
                            'operator' => 'fuzzy_wuzzy',
                            'operands' => [
                                'operand_1' => 'Shashank kumar ',
                                'operand_2' => 'Rzp Test QA Merchant',
                                'operand_3' => 70,
                            ],
                            'remarks' => [
                                'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                'match_percentage'      => 29,
                                'required_percentage'   => 70,
                            ],
                        ],
                    ],
                    'remarks' => [
                        'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                        'match_percentage'      => 29,
                        'required_percentage'   => 70,
                    ],
                ]
            ]
        ];

        $this->processKafkaEvent('failed', 'verified', $kafkaPayload, 'true', 'RULE_EXECUTION_FAILED');

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail', 'live');

        $this->assertEquals('not_matched', $verificationDetail->getStatus());
    }

    // Test scenario where Business name verification and signatory match is failed with rule_execution_list present in BVS response
    public function testPartnershipDeedVerificationBusinessNameMatchFailureAndExperimentOn()
    {
       $kafkaPayload  = [
            0 => [
                'rule' => [
                    'rule_type' => 'string_comparison_rule',
                    'rule_def' => [
                        0 => [
                            'fuzzy_wuzzy' => [
                                0 => [
                                    'var' => 'artefact.details.business_name.value',
                                ],
                                1 => [
                                    'var' => 'enrichments.ocr.details.1.business_name.value',
                                ],
                                2 => 60,
                            ],
                        ],
                    ],
                ],
                'rule_execution_result' => [
                    'result' => false,
                    'operator' => 'fuzzy_wuzzy',
                    'operands' => [
                        'operand_1' => 'ORANGEE CLOTHINGLINE',
                        'operand_2' => 'RZP TEST',
                        'operand_3' => 60,
                        'operand_4' => [
                            "private limited",
                            "limited liability partnership",
                            "pvt",
                            "ltd",
                            "."
                        ]
                    ],
                    'remarks' => [
                        'algorithm_type'        => 'fuzzy_wuzzy_custom_algorithm_4',
                        'match_percentage'      => 30,
                        'required_percentage'   => 60,
                    ],
                ],
                'error' => '',
            ],
            1 => [
                'rule' => [
                    'rule_type' => 'array_comparison_rule',
                    'rule_def' => [
                        'any' => [
                            0 => [
                                'var' => 'enrichments.ocr.details.1.name_of_partners',
                            ],
                            1 => [
                                'fuzzy_wuzzy' => [
                                    0 => [
                                        'var' => 'each_array_element',
                                    ],
                                    1 => [
                                        'var' => 'artefact.details.name_of_partners',
                                    ],
                                    2 => 60,
                                ],
                            ],
                        ],
                    ],
                ],
                'rule_execution_result' => [
                    'result'   => false,
                    'operator' => 'some',
                    'operands' => [
                        'operand_1' => [
                            'result'   => true,
                            'operator' => 'fuzzy_wuzzy',
                            'operands' => [
                                'operand_1' => 'HARSHILMATHUR ',
                                'operand_2' => 'RZP TEST',
                                'operand_3' => 70,
                            ],
                            'remarks' => [
                                'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                'match_percentage'      => 30,
                                'required_percentage'   => 70,
                            ],
                        ],
                        'operand_2' => [
                            'result'   => false,
                            'operator' => 'fuzzy_wuzzy',
                            'operands' => [
                                'operand_1' => 'Shashank kumar ',
                                'operand_2' => 'Rzp Test QA Merchant',
                                'operand_3' => 70,
                            ],
                            'remarks' => [
                                'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                'match_percentage'      => 29,
                                'required_percentage'   => 70,
                            ],
                        ],
                    ],
                    'remarks' => [
                        'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                        'match_percentage'      => 29,
                        'required_percentage'   => 70,
                    ],
                ]
            ]
        ];

        $this->processKafkaEvent('failed', 'not_matched', $kafkaPayload, 'true', 'RULE_EXECUTION_FAILED');

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail', 'live');

        $this->assertEquals('not_initiated', $verificationDetail->getStatus());
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
