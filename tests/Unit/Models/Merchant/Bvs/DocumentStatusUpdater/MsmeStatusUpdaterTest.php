<?php


namespace Unit\Models\Merchant\Bvs\DocumentStatusUpdater;

use Mockery;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;
use RZP\Services\KafkaMessageProcessor;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class MsmeStatusUpdaterTest extends TestCase
{
    use DbEntityFetchTrait;

    private function createFixtures()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', ['business_type' => '1',]);

        $mid = $merchantDetail->getId();

        $verificationDetail = $this->fixtures->create('merchant_verification_detail', [
            'merchant_id'         => $mid,
            'artefact_type'       => Constant::MSME,
            'artefact_identifier' => 'doc',
        ]);

        $this->fixtures->connection('live')->create('merchant_verification_detail', $verificationDetail->toArray());

        return $mid;
    }

    private function processKafkaEvent($validationStatus, $expectedVerificationStatus, $ruleExecutionList, $variant, $errorCode = '')
    {
        $mid = $this->createFixtures();

        $this->mockSplitzTreatment($mid, $variant);

        $bvsValidation = $this->fixtures->create('bvs_validation',
                                                 [
                                                     'owner_id'      => $mid,
                                                     'artefact_type' => 'msme',
                                                     'validation_unit' => 'proof',
                                                 ]);

        $kafkaEventPayload = [
            'data' => [
                'validation_id'       => $bvsValidation->getValidationId(),
                'status'              => $validationStatus,
                'error_description'   => '',
                'error_code'          => $errorCode,
                'rule_execution_list' => $ruleExecutionList
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-validation-result-events', $kafkaEventPayload, 'live');

        $bvsValidation  = $this->getDbEntityById('bvs_validation', $bvsValidation->getValidationId());

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertEquals($validationStatus, $bvsValidation->getValidationStatus());

        $this->assertEquals($expectedVerificationStatus, $merchantDetail->getMsmeDocVerificationStatus());
    }

    public function testMsmeDocVerificationStatusForSuccessEvent()
    {
        $this->processKafkaEvent('success', 'verified', [], '');
    }

    public function testMsmeDocVerificationStatusForFailureEvent()
    {
        $this->processKafkaEvent('failed', 'failed', [], '');
    }

    public function testMsmeDocVerificationSignatorySuccessAndExperimentOn()
    {
        $kafkaPayload = [
                    "0" => [
                        "rule"                  => [
                            "rule_type" => "string_comparison_rule",
                            "rule_def"  => [
                                "if" => [
                                    [
                                        "===" => [
                                            [
                                                "var" => "enrichments.ocr.details.1.issuer.value"
                                            ],
                                            "Udyog Aadhaar Memorandum"
                                        ]
                                    ],
                                    [
                                        "fuzzy_suzzy" => [
                                            [
                                                "var" => "artefact.details.signatory_name.value"
                                            ],
                                            [
                                                "var" => "enrichments.ocr.details.1.signatory_name.value"
                                            ],
                                            81
                                        ]
                                    ],
                                    true
                                ]
                            ]
                        ],
                        "rule_execution_result" => [
                            "result"   => true,
                            "operator" => "",
                            "operands" => null,
                            "remarks"  => ""
                        ],
                        "error"                 => ""
                    ],
                    "1" => [
                        "rule"                  => [
                            "rule_type" => "string_comparison_rule",
                            "rule_def"  => [
                                "fuzzy_suzzy" => [
                                    [
                                        "var" => "artefact.details.trade_name.value"
                                    ],
                                    [
                                        "var" => "enrichments.ocr.details.1.trade_name.value"
                                    ],
                                    81
                                ]
                            ]
                        ],
                        "rule_execution_result" => [
                            "result"   => true,
                            "operator" => "fuzzy_suzzy",
                            "operands" => [
                                "operand_1" => "TANIDRAPES",
                                "operand_2" => "TANIDRAPES",
                                "operand_3" => 81
                            ],
                            "remarks"  => [
                                "algorithm_type"      => "fuzzy_suzzy_lev_token_set_algorithm",
                                "match_percentage"    => 100,
                                "required_percentage" => 81
                            ]
                        ],
                        "error"                 => ""
                    ]
        ];

        $this->processKafkaEvent('success', 'verified', $kafkaPayload, 'true');

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail', 'live');

        $this->assertEquals('verified', $verificationDetail->getStatus());
    }

    public function testMsmeDocVerificationSignatoryFailureAndExperimentOn()
    {
        $kafkaPayload = [
            "0" => [
                "rule"                  => [
                    "rule_type" => "string_comparison_rule",
                    "rule_def"  => [
                        "if" => [
                            [
                                "===" => [
                                    [
                                        "var" => "enrichments.ocr.details.1.issuer.value"
                                    ],
                                    "Udyog Aadhaar Memorandum"
                                ]
                            ],
                            [
                                "fuzzy_suzzy" => [
                                    [
                                        "var" => "artefact.details.signatory_name.value"
                                    ],
                                    [
                                        "var" => "enrichments.ocr.details.1.signatory_name.value"
                                    ],
                                    81
                                ]
                            ],
                            true
                        ]
                    ]
                ],
                "rule_execution_result" => [
                    "result"   => false,
                    "operator" => "",
                    "operands" => null,
                    "remarks"  => ""
                ],
                "error"                 => ""
            ],
            "1" => [
                "rule"                  => [
                    "rule_type" => "string_comparison_rule",
                    "rule_def"  => [
                        "fuzzy_suzzy" => [
                            [
                                "var" => "artefact.details.trade_name.value"
                            ],
                            [
                                "var" => "enrichments.ocr.details.1.trade_name.value"
                            ],
                            81
                        ]
                    ]
                ],
                "rule_execution_result" => [
                    "result"   => true,
                    "operator" => "fuzzy_suzzy",
                    "operands" => [
                        "operand_1" => "TANIDRAPES",
                        "operand_2" => "TANIDRAPES",
                        "operand_3" => 81
                    ],
                    "remarks"  => [
                        "algorithm_type"      => "fuzzy_suzzy_lev_token_set_algorithm",
                        "match_percentage"    => 100,
                        "required_percentage" => 81
                    ]
                ],
                "error"                 => ""
            ]
        ];

        $this->processKafkaEvent('failed', 'verified', $kafkaPayload, 'true', 'RULE_EXECUTION_FAILED');

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail', 'live');

        $this->assertEquals('not_matched', $verificationDetail->getStatus());
    }

    public function testMsmeDocVerificationTradeNameAndSignatoryFailureAndExperimentOn()
    {
        $kafkaPayload = [
            "0" => [
                "rule"                  => [
                    "rule_type" => "string_comparison_rule",
                    "rule_def"  => [
                        "if" => [
                            [
                                "===" => [
                                    [
                                        "var" => "enrichments.ocr.details.1.issuer.value"
                                    ],
                                    "Udyog Aadhaar Memorandum"
                                ]
                            ],
                            [
                                "fuzzy_suzzy" => [
                                    [
                                        "var" => "artefact.details.signatory_name.value"
                                    ],
                                    [
                                        "var" => "enrichments.ocr.details.1.signatory_name.value"
                                    ],
                                    81
                                ]
                            ],
                            false
                        ]
                    ]
                ],
                "rule_execution_result" => [
                    "result"   => false,
                    "operator" => "",
                    "operands" => null,
                    "remarks"  => ""
                ],
                "error"                 => ""
            ],
            "1" => [
                "rule"                  => [
                    "rule_type" => "string_comparison_rule",
                    "rule_def"  => [
                        "fuzzy_suzzy" => [
                            [
                                "var" => "artefact.details.trade_name.value"
                            ],
                            [
                                "var" => "enrichments.ocr.details.1.trade_name.value"
                            ],
                            81
                        ]
                    ]
                ],
                "rule_execution_result" => [
                    "result"   => false,
                    "operator" => "fuzzy_suzzy",
                    "operands" => [
                        "operand_1" => "TANIDRAPES",
                        "operand_2" => "TANIDRAPES",
                        "operand_3" => 81
                    ],
                    "remarks"  => [
                        "algorithm_type"      => "fuzzy_suzzy_lev_token_set_algorithm",
                        "match_percentage"    => 100,
                        "required_percentage" => 81
                    ]
                ],
                "error"                 => ""
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
