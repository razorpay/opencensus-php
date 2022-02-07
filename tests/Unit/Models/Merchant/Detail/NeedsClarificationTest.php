<?php

namespace RZP\Tests\Unit\Models\Merchant\Detail;

use RZP\Constants\Mode;
use RZP\Models\Feature\Entity;
use RZP\Models\Merchant\Store;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\UpdateMerchantContext;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Detail\NeedsClarification;
use RZP\Models\Merchant\Detail\NeedsClarification\Core;
use RZP\Models\Merchant\Detail\NeedsClarificationMetaData;
use RZP\Models\Merchant\Detail\NeedsClarification\Constants;

class NeedsClarificationTest extends TestCase
{

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NeedsClarificationTestData.php';

        parent::setUp();
    }

    protected function mockRazorxTreatment(string $returnValue = 'On')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn($returnValue);
    }

    public function testClarificationReasonTransformation()
    {
        $needClarification = New NeedsClarification\Core();

        $kycClarificationReason = $this->testData['testClarificationReasonTransformationInput'];

        $requirements = $needClarification->getFormattedKycClarificationReasons($kycClarificationReason);

        $expectedOutput = $this->testData['testClarificationReasonTransformationOutput'];

        $this->assertEquals($requirements, $expectedOutput);
    }

    public function testClarificationReasonMerge()
    {
        $needClarification = New NeedsClarification\Core();

        $kycClarificationReason = $this->testData['testClarificationReasonTransformationInput'];

        $kycClarificationSecondaryArray = $this->testData['testClarificationReasonMergeInput'];

        $requirements = $needClarification->mergeKycClarificationReasons(
            $kycClarificationReason,
            $kycClarificationSecondaryArray['clarification_reasons'],
            $kycClarificationSecondaryArray['additional_details']);

        $expectedOutput = $this->testData['testClarificationReasonMergeOutput'];

        $this->assertEquals($requirements, $expectedOutput);
    }

    public function testReasonComposerForGstinNotMatched()
    {
        $input          = [
            'poi_verification_status'                => 'verified',
            'poa_verification_status'                => 'verified',
            'bank_details_verification_status'       => 'verified',
            'gstin_verification_status'              => 'not_matched',
            'shop_establishment_verification_status' => 'incorrect_details',
        ];
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $mid = $merchantDetail->getId();

        $this->mockRazorxTreatment('on');

        $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'gstin',
            'error_code'    => 'RULE_EXECUTION_FAILED',
            'rule_execution_list' => [
                0 => [
                    'rule' => [
                        'rule_type' => 'string_comparison_rule',
                        'rule_def' => [
                            'or' => [
                                0 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'artefact.details.legal_name.value',
                                        ],
                                        1 => [
                                            'var' => 'enrichments.online_provider.details.legal_name.value',
                                        ],
                                        2 => 70,
                                    ],
                                ],
                                1 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'artefact.details.trade_name.value',
                                        ],
                                        1 => [
                                            'var' => 'enrichments.online_provider.details.trade_name.value',
                                        ],
                                        2 => 70,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'or',
                        'operands' => [
                            'operand_1' => [
                                'result' => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'Rzp Test QA Merchant',
                                    'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                                    'operand_3' => 70,
                                ],
                                'remarks' => [
                                    'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                    'match_percentage'      => 45,
                                    'required_percentage'   => 70,
                                ],
                            ],
                            'operand_2' => [
                                'result' => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'CHIZRINZ INFOWAY PRIVATE LIMITED',
                                    'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                                    'operand_3' => 70,
                                ],
                                'remarks' => [
                                    'algorithm_type'       => 'fuzzy_wuzzy_default_algorithm',
                                    'match_percentage'     => 68,
                                    'required_percentage'  => 70,
                                ],
                            ],
                        ],
                        'remarks' => [
                            'algorithm_type'      => 'fuzzy_wuzzy_default_algorithm',
                            'match_percentage'    => 68,
                            'required_percentage' => 70,
                        ],
                    ],
                    'error' => '',
                ],
                1 => [
                    'rule' => [
                        'rule_type' => 'array_comparison_rule',
                        'rule_def' => [
                            'some' => [
                                0 => [
                                    'var' => 'enrichments.online_provider.details.signatory_names',
                                ],
                                1 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'each_array_element',
                                        ],
                                        1 => [
                                            'var' => 'artefact.details.legal_name.value',
                                        ],
                                        2 => 70,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'some',
                        'operands' => [
                            'operand_1' => [
                                'result' => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'HARSHILMATHUR ',
                                    'operand_2' => 'Rzp Test QA Merchant',
                                    'operand_3' => 70,
                                ],
                                'remarks' => [
                                    'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                    'match_percentage'      => 30,
                                    'required_percentage'   => 70,
                                ],
                            ],
                            'operand_2' => [
                                'result' => false,
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
                        'remarks' => null,
                    ]
                ]
            ],
            'validation_status' => 'failed'
        ]);

        $factory = new NeedsClarification\ReasonComposer\Factory( $merchantDetail);

        $metadata = NeedsClarificationMetaData::SYSTEM_BASED_NEEDS_CLARIFICATION_METADATA[Constants::GSTIN_IDENTIFER];

        $reason = $factory->getClarificationReasonComposer($metadata)->getClarificationReason();

        $expectedReasons = [
            'clarification_reasons' => [
                'promoter_pan_name' => [
                    [
                        'reason_type' => 'predefined',
                        'field_type' =>'text',
                        'reason_code' => 'signatory_name_not_matched'
                    ]
                ]
            ]];

        $this->assertEquals($expectedReasons, $reason);
    }

    public function testReasonComposerForCinSignatoryNotMatched()
    {
        $input          = [
            'poi_verification_status'                => 'verified',
            'poa_verification_status'                => 'verified',
            'bank_details_verification_status'       => 'verified',
            'gstin_verification_status'              => 'verified',
            'cin_verification_status'                => 'not_matched',
            'shop_establishment_verification_status' => 'incorrect_details',
        ];
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $mid = $merchantDetail->getId();

        $this->mockRazorxTreatment('on');

        $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'cin',
            'error_code'    => 'RULE_EXECUTION_FAILED',
            'rule_execution_list' => [
                0 => [
                    'rule' => [
                        'rule_type' => 'string_comparison_rule',
                        'rule_def' => [
                            'or' => [
                                0 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'artefact.details.legal_name.value',
                                        ],
                                        1 => [
                                            'var' => 'enrichments.online_provider.details.legal_name.value',
                                        ],
                                        2 => 70,
                                    ],
                                ],
                                1 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'artefact.details.trade_name.value',
                                        ],
                                        1 => [
                                            'var' => 'enrichments.online_provider.details.trade_name.value',
                                        ],
                                        2 => 70,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'or',
                        'operands' => [
                            'operand_1' => [
                                'result'   => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'Rzp Test QA Merchant',
                                    'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                                    'operand_3' => 70,
                                ],
                                'remarks' => [
                                    'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                    'match_percentage'      => 45,
                                    'required_percentage'   => 70,
                                ],
                            ],
                            'operand_2' => [
                                'result'   => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'CHIZRINZ INFOWAY PRIVATE LIMITED',
                                    'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                                    'operand_3' => 70,
                                ],
                                'remarks' => [
                                    'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                    'match_percentage'      => 68,
                                    'required_percentage'   => 70,
                                ],
                            ],
                        ],
                        'remarks' => [
                            'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                            'match_percentage'      => 68,
                            'required_percentage'   => 70,
                        ],
                    ],
                    'error' => '',
                ],
                1 => [
                    'rule' => [
                        'rule_type' => 'array_comparison_rule',
                        'rule_def' => [
                            'some' => [
                                0 => [
                                    'var' => 'enrichments.online_provider.details.signatory_names',
                                ],
                                1 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'each_array_element',
                                        ],
                                        1 => [
                                            'var' => 'artefact.details.legal_name.value',
                                        ],
                                        2 => 70,
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
                                'result'   => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'HARSHILMATHUR ',
                                    'operand_2' => 'Rzp Test QA Merchant',
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
            ],
            'validation_status' => 'failed'
        ]);

        $factory = new NeedsClarification\ReasonComposer\Factory( $merchantDetail);

        $metadata = NeedsClarificationMetaData::SYSTEM_BASED_NEEDS_CLARIFICATION_METADATA[Constants::CIN_IDENTIFER];

        $reason = $factory->getClarificationReasonComposer($metadata)->getClarificationReason();

        $expectedReasons = [
            'clarification_reasons' => [
                'promoter_pan_name' => [
                    [
                        'reason_type' => 'predefined',
                        'field_type'  =>'text',
                        'reason_code' => 'signatory_name_not_matched'
                    ]
                ],
            ]];
        $this->assertEquals($expectedReasons, $reason);
    }

    public function testReasonComposerForCinCompanyNameNotMatched()
    {
        $input          = [
            'poi_verification_status'                => 'verified',
            'poa_verification_status'                => 'verified',
            'bank_details_verification_status'       => 'verified',
            'gstin_verification_status'              => 'verified',
            'cin_verification_status'                => 'not_matched',
            'shop_establishment_verification_status' => 'incorrect_details',
        ];
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $mid = $merchantDetail->getId();

        $this->mockRazorxTreatment('on');

        $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'cin',
            'error_code'    => 'RULE_EXECUTION_FAILED',
            'rule_execution_list' => [
                0 => [
                    'rule' => [
                        'rule_type' => 'string_comparison_rule',
                        'rule_def'  => [
                            'or' => [
                                0 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'artefact.details.legal_name.value',
                                        ],
                                        1 => [
                                            'var' => 'enrichments.online_provider.details.legal_name.value',
                                        ],
                                        2 => 70,
                                    ],
                                ],
                                1 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'artefact.details.trade_name.value',
                                        ],
                                        1 => [
                                            'var' => 'enrichments.online_provider.details.trade_name.value',
                                        ],
                                        2 => 70,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'or',
                        'operands' => [
                            'operand_1' => [
                                'result' => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'Rzp Test QA Merchant',
                                    'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                                    'operand_3' => 70,
                                ],
                                'remarks' => [
                                    'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                    'match_percentage'      => 45,
                                    'required_percentage'   => 70,
                                ],
                            ],
                            'operand_2' => [
                                'result' => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'CHIZRINZ INFOWAY PRIVATE LIMITED',
                                    'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                                    'operand_3' => 70,
                                ],
                                'remarks' => [
                                    'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                    'match_percentage'      => 68,
                                    'required_percentage'   => 70,
                                ],
                            ],
                        ],
                        'remarks' => [
                            'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                            'match_percentage'      => 50,
                            'required_percentage'   => 70,
                        ],
                    ],
                    'error' => '',
                ],
                1 => [
                    'rule' => [
                        'rule_type' => 'array_comparison_rule',
                        'rule_def' => [
                            'some' => [
                                0 => [
                                    'var' => 'enrichments.online_provider.details.signatory_names',
                                ],
                                1 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'each_array_element',
                                        ],
                                        1 => [
                                            'var' => 'artefact.details.legal_name.value',
                                        ],
                                        2 => 70,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'some',
                        'operands' => [
                            'operand_1' => [
                                'result' => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'HARSHILMATHUR ',
                                    'operand_2' => 'Rzp Test QA Merchant',
                                    'operand_3' => 70,
                                ],
                                'remarks' => [
                                    'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                    'match_percentage'      => 80,
                                    'required_percentage'   => 70,
                                ],
                            ],
                            'operand_2' => [
                                'result' => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'Shashank kumar ',
                                    'operand_2' => 'Rzp Test QA Merchant',
                                    'operand_3' => 70,
                                ],
                                'remarks' => [
                                    'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                    'match_percentage'      => 81,
                                    'required_percentage'   => 70,
                                ],
                            ],
                        ],
                        'remarks' => [
                            'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                            'match_percentage'      => 81,
                            'required_percentage'   => 70,
                        ],
                    ]
                ]
            ],
            'validation_status' => 'failed'
        ]);

        $factory = new NeedsClarification\ReasonComposer\Factory( $merchantDetail);

        $metadata = NeedsClarificationMetaData::SYSTEM_BASED_NEEDS_CLARIFICATION_METADATA[Constants::CIN_IDENTIFER];

        $reason = $factory->getClarificationReasonComposer($metadata)->getClarificationReason();

        $expectedReasons = [
            'clarification_reasons' => [
                'business_name' => [
                    [
                        'reason_type' => 'predefined',
                        'field_type'  => 'text',
                        'reason_code' => 'company_name_not_matched'
                    ]
                ]]];

        $this->assertEquals($expectedReasons, $reason);
    }

    public function testReasonComposerForLLPNotMatched()
    {
        $input          = [
            'poi_verification_status'                => 'verified',
            'poa_verification_status'                => 'verified',
            'bank_details_verification_status'       => 'verified',
            'gstin_verification_status'              => 'verified',
            'cin_verification_status'                => 'not_matched',
            'shop_establishment_verification_status' => 'incorrect_details',
        ];
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $mid = $merchantDetail->getId();

        $this->mockRazorxTreatment('on');

        $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'llp_deed',
            'error_code'    => 'RULE_EXECUTION_FAILED',
            'rule_execution_list' => [
                0 => [
                    'rule' => [
                        'rule_type' => 'string_comparison_rule',
                        'rule_def' => [
                            'or' => [
                                0 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'artefact.details.legal_name.value',
                                        ],
                                        1 => [
                                            'var' => 'enrichments.online_provider.details.legal_name.value',
                                        ],
                                        2 => 70,
                                    ],
                                ],
                                1 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'artefact.details.trade_name.value',
                                        ],
                                        1 => [
                                            'var' => 'enrichments.online_provider.details.trade_name.value',
                                        ],
                                        2 => 70,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'or',
                        'operands' => [
                            'operand_1' => [
                                'result'   => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'Rzp Test QA Merchant',
                                    'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                                    'operand_3' => 70,
                                ],
                                'remarks' => [
                                    'algorithm_type' => 'fuzzy_wuzzy_default_algorithm',
                                    'match_percentage' => 45,
                                    'required_percentage' => 70,
                                ],
                            ],
                            'operand_2' => [
                                'result'   => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'CHIZRINZ INFOWAY PRIVATE LIMITED',
                                    'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                                    'operand_3' => 70,
                                ],
                                'remarks' => [
                                    'algorithm_type' => 'fuzzy_wuzzy_default_algorithm',
                                    'match_percentage' => 42,
                                    'required_percentage' => 70,
                                ],
                            ],
                        ],
                        'remarks' => [
                            'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                            'match_percentage'      => 45,
                            'required_percentage'   => 70,
                        ],
                    ],
                    'error' => '',
                ],
                1 => [
                    'rule' => [
                        'rule_type' => 'array_comparison_rule',
                        'rule_def'  => [
                            'some' => [
                                0 => [
                                    'var' => 'enrichments.online_provider.details.signatory_names',
                                ],
                                1 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'each_array_element',
                                        ],
                                        1 => [
                                            'var' => 'artefact.details.legal_name.value',
                                        ],
                                        2 => 70,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'some',
                        'operands' => [
                            'operand_1' => [
                                'result'   => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'HARSHILMATHUR ',
                                    'operand_2' => 'Rzp Test QA Merchant',
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
            ],
            'validation_status' => 'failed'
        ]);

        $factory = new NeedsClarification\ReasonComposer\Factory( $merchantDetail);

        $metadata = NeedsClarificationMetaData::SYSTEM_BASED_NEEDS_CLARIFICATION_METADATA[Constants::LLPIN_IDENTIFIER];

        $reason = $factory->getClarificationReasonComposer($metadata)->getClarificationReason();

        $expectedReasons = [
            'clarification_reasons' => [
                    'promoter_pan_name' => [
                     [
                        'reason_type' => 'predefined',
                        'field_type'  =>'text',
                        'reason_code' => 'signatory_name_not_matched'
                     ]
                     ],
                     'business_name' => [
                     [
                        'reason_type'  => 'predefined',
                         'field_type'  => 'text',
                         'reason_code' =>'company_name_not_matched'
                     ]]
        ]];
        $this->assertEquals($expectedReasons, $reason);
    }

    public function testReasonComposerForShopEstablishmentNotMatched()
    {
        $input          = [
            'poi_verification_status'                => 'verified',
            'poa_verification_status'                => 'verified',
            'bank_details_verification_status'       => 'verified',
            'gstin_verification_status'              => 'verified',
            'cin_verification_status'                => 'verified',
            'shop_establishment_verification_status' => 'not_matched',
        ];
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $mid = $merchantDetail->getId();

        $this->mockRazorxTreatment('on');

        $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'shop_establishment',
            'error_code'    => 'RULE_EXECUTION_FAILED',
            'rule_execution_list' => [
                0 => [
                    'rule' => [
                        'rule_type' => 'string_comparison_rule',
                        'rule_def' => [
                            'fuzzy_suzzy' => [
                                0 => [
                                    'var' => 'artefact.details.owner_name.value',
                                ],
                                1 => [
                                    'var' => 'enrichments.online_provider.details.owner_name.value',
                                ],
                                2 => 70,
                            ],
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'fuzzy_suzzy',
                        'operands' => [
                            'operand_1' => 'CHIZRINZ INFOWAY PRIVATE LIMITED',
                            'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                            'operand_3' => 70,
                        ],
                        'remarks' => [
                            'algorithm_type' => 'fuzzy_suzzy_default_algorithm',
                            'match_percentage' => 49,
                            'required_percentage' => 70,
                        ],
                    ],
                    'error' => '',
                ],
                1 => [
                    'rule' => [
                        'rule_type' => 'array_comparison_rule',
                        'rule_def' => [
                            'fuzzy_wuzzy' => [
                                [
                                    'var' => 'artefact.details.entity_name.value'
                                ],
                                [
                                    'var' => 'enrichments.online_provider.details.entity_name.value'
                                ],
                                81,
                                [
                                    "private limited",
                                    "limited liability partnership",
                                    "pvt",
                                    "ltd",
                                    "."
                                ],
                            ]
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'fuzzy_wuzzy',
                        'operands' => [
                            'operand_1' => 'HARSHILMATHUR ',
                            'operand_2' => 'Rzp Test QA Merchant',
                            'operand_3' => 70,
                        ],
                        'remarks' => [
                            'algorithm_type' => 'fuzzy_wuzzy_default_algorithm',
                            'match_percentage' => 30,
                            'required_percentage' => 70,
                        ],
                    ],
                    'error' => '',
                ]
            ],
            'validation_status' => 'failed'
        ]);

        $factory = new NeedsClarification\ReasonComposer\Factory( $merchantDetail);

        $metadata = NeedsClarificationMetaData::SYSTEM_BASED_NEEDS_CLARIFICATION_METADATA[Constants::SHOP_ESTABLISHMENT_IDENTIFIER];

        $reason = $factory->getClarificationReasonComposer($metadata)->getClarificationReason();

        $expectedReasons = [
            'clarification_reasons' => [
                'promoter_pan_name' => [
                    [
                        'reason_type' => 'predefined',
                        'field_type'  => 'text',
                        'reason_code' => 'signatory_name_not_matched'
                    ]
                ],
                'business_name' => [
                    [
                        'reason_type' => 'predefined',
                        'field_type'  => 'text',
                        'reason_code' => 'company_name_not_matched'
                    ]]
            ]];

        $this->assertEquals($expectedReasons, $reason);

        // Test KYC clarification reasons for Partner activation
        $partnerActivationInput = [
            'merchant_id'       => $mid,
            'activation_status' => 'under_review',
            'submitted'         => true
        ];

        $partnerActivation = $this->fixtures->create('partner_activation', $partnerActivationInput);

        $partnerKycClarificationReasons =  (new Core())->composeNeedsClarificationReason($partnerActivation);

        $this->assertEmpty($partnerKycClarificationReasons);
    }

    public function testReasonComposerForIncorrectPersonalPan() {
        $input          = [
            'poi_verification_status'               => 'incorrect_details',
            'poa_verification_status'               => 'verified',
            'bank_details_verification_status'      => 'verified',
            'business_type'                         => 11
        ];
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $mid = $merchantDetail->getId();

        $this->mockRazorxTreatment('on');

        $this->fixtures->create('bvs_validation', [
            'owner_type'        => 'merchant',
            'owner_id'          => $mid,
            'artefact_type'     => 'personal_pan',
            'validation_unit'   => 'identifier',
            'error_code'        => 'INPUT_DATA_ISSUE',
            'validation_status' => 'failed'
        ]);

        $kycClarificationReasons =  (new Core())->composeNeedsClarificationReason($merchantDetail);

        $expectedKycClarificationReasons = [
            'clarification_reasons' =>  [
                'promoter_pan' => [
                    [
                        'reason_type'   => 'predefined',
                        'field_type'    => 'text',
                        'reason_code'   => 'invalid_personal_pan_number'
                    ]
                ],
            ]
        ];

        $this->assertEquals($expectedKycClarificationReasons, $kycClarificationReasons);
    }

    public function testReasonComposerForIncorrectBusinessPanAndBankWithNoDocFeature()
    {
        $input          = [
            'company_pan_verification_status'       => 'incorrect_details',
            'bank_details_verification_status'      => 'incorrect_details',
            'business_type'                         => 4
        ];
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $mid = $merchantDetail->getId();

        $featureParams = [
            Entity::ENTITY_ID   => $mid,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'no_doc_onboarding',
        ];

        (new \RZP\Models\Feature\Core())->create($featureParams,true);

        $this->mockRazorxTreatment('on');

        $this->fixtures->create('bvs_validation', [
            'owner_type'        => 'merchant',
            'owner_id'          => $mid,
            'artefact_type'     => 'business_pan',
            'validation_unit'   => 'identifier',
            'error_code'        => 'INPUT_DATA_ISSUE',
            'validation_status' => 'failed'
        ]);

        $this->fixtures->create('bvs_validation', [
            'owner_type'        => 'merchant',
            'owner_id'          => $mid,
            'artefact_type'     => 'bank_account',
            'validation_unit'   => 'identifier',
            'error_code'        => 'INPUT_DATA_ISSUE',
            'validation_status' => 'failed'
        ]);

        $kycClarificationReasons =  (new Core())->composeNeedsClarificationReason($merchantDetail);

        $expectedKycClarificationReasons = [
            'additional_details' =>  [
                'cancelled_cheque' => [
                    [
                        'reason_type' => "predefined",
                        'field_type' => "document",
                        'field_value' => null,
                        'reason_code' => "bank_account_change_request_for_pvt_public_llp"
                    ]
                ],
                'bank_account_name' => [
                    [
                        'reason_type' => "predefined",
                        'field_type' => "text",
                        'field_value' => "test",
                        'reason_code' => "bank_account_change_request_for_pvt_public_llp"
                    ]
                ],
                'bank_account_number' => [
                    [
                        'reason_type' => "predefined",
                        'field_type' => "text",
                        'field_value' => "123456789012345",
                        'reason_code' => "bank_account_change_request_for_pvt_public_llp"
                    ]
                ],
                'bank_branch_ifsc' => [
                    [
                        'reason_type' => "predefined",
                        'field_type' => "text",
                        'field_value' => "ICIC0000001",
                        'reason_code' => "bank_account_change_request_for_pvt_public_llp"
                    ]
                ]
            ],
            'clarification_reasons' =>  [
                'company_pan' => [
                    [
                        'reason_type'   => 'predefined',
                        'field_type'    => 'text',
                        'reason_code'   => 'invalid_company_pan_number'
                    ]
                ],
            ]
        ];

        $this->assertEquals($expectedKycClarificationReasons, $kycClarificationReasons);
    }

    public function testReasonComposerForIncorrectGstWithNoDocFeature()
    {
        $input          = [
            'poi_verification_status'               => 'verified',
            'bank_details_verification_status'      => 'verified',
            'gstin_verification_status'             => 'incorrect_details',
            'business_type'                         => 11
        ];
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $mid = $merchantDetail->getId();

        $featureParams = [
            Entity::ENTITY_ID   => $mid,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'no_doc_onboarding',
        ];

        (new \RZP\Models\Feature\Core())->create($featureParams,true);

        $value = [
            'gst' => ['09AAACR5055K1Z5'],
            'current_index' =>0,
        ];

        $data = [
            Store\Constants::NAMESPACE  => ConfigKey::ONBOARDING_NAMESPACE,
            ConfigKey::NO_DOC_ONBOARDING_INFO => $value
        ];

        $data = (new Store\Core())->updateMerchantStore($mid, $data, Store\Constants::INTERNAL);

        $this->mockRazorxTreatment('on');

        $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'gstin',
            'error_code'    => 'INPUT_DATA_ISSUE',
            'validation_status' => 'failed'
        ]);

        $kycClarificationReasons =  (new Core())->composeNeedsClarificationReason($merchantDetail);

        $expectedKycClarificationReasons = [
            'clarification_reasons' =>  [
                'gstin' => [
                    [
                        'reason_type' =>  'predefined',
                        'field_type' => 'text',
                        'reason_code' => 'invalid_gstin_number'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedKycClarificationReasons, $kycClarificationReasons);
    }

    public function testReasonComposerForIncorrectAndNotMatched() {
        $input          = [
            'poi_verification_status'                => 'verified',
            'poa_verification_status'                => 'verified',
            'bank_details_verification_status'       => 'verified',
            'gstin_verification_status'              => 'incorrect_details',
            'cin_verification_status'                => 'verified',
            'shop_establishment_verification_status' => 'not_matched',
        ];
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $mid = $merchantDetail->getId();

        $this->mockRazorxTreatment('on');

        $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'shop_establishment',
            'error_code'    => 'RULE_EXECUTION_FAILED',
            'rule_execution_list' => [
                0 => [
                    'rule' => [
                        'rule_type' => 'string_comparison_rule',
                        'rule_def' => [
                            'fuzzy_suzzy' => [
                                0 => [
                                    'var' => 'artefact.details.owner_name.value',
                                ],
                                1 => [
                                    'var' => 'enrichments.online_provider.details.owner_name.value',
                                ],
                                2 => 70,
                            ],
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'fuzzy_suzzy',
                        'operands' => [
                            'operand_1' => 'CHIZRINZ INFOWAY PRIVATE LIMITED',
                            'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                            'operand_3' => 70,
                        ],
                        'remarks' => [
                            'algorithm_type' => 'fuzzy_suzzy_default_algorithm',
                            'match_percentage' => 49,
                            'required_percentage' => 70,
                        ],
                    ],
                    'error' => '',
                ],
                1 => [
                    'rule' => [
                        'rule_type' => 'array_comparison_rule',
                        'rule_def' => [
                            'fuzzy_wuzzy' => [
                                [
                                    'var' => 'artefact.details.entity_name.value'
                                ],
                                [
                                    'var' => 'enrichments.online_provider.details.entity_name.value'
                                ],
                                81,
                                [
                                    "private limited",
                                    "limited liability partnership",
                                    "pvt",
                                    "ltd",
                                    "."
                                ],
                            ]
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'fuzzy_wuzzy',
                        'operands' => [
                            'operand_1' => 'HARSHILMATHUR ',
                            'operand_2' => 'Rzp Test QA Merchant',
                            'operand_3' => 70,
                        ],
                        'remarks' => [
                            'algorithm_type' => 'fuzzy_wuzzy_default_algorithm',
                            'match_percentage' => 30,
                            'required_percentage' => 70,
                        ],
                    ],
                    'error' => '',
                ]
            ],
            'validation_status' => 'failed'
        ]);

        $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'gstin',
            'error_code'    => 'INPUT_DATA_ISSUE',
            'validation_status' => 'failed'
        ]);

       $kycClarificationReasons =  (new Core())->composeNeedsClarificationReason($merchantDetail);

        $expectedKycClarificationReasons = [
            'clarification_reasons' =>  [
                'gstin' => [
                    [
                        'reason_type' =>  'predefined',
                        'field_type' => 'text',
                        'reason_code' => 'invalid_gstin_number'
                    ]
                ],
                'promoter_pan_name' => [
                    [
                        'reason_type' => 'predefined',
                        'field_type'  =>'text',
                        'reason_code' => 'signatory_name_not_matched'
                    ]
                ],
                'business_name' => [
                    [
                        'reason_type' => 'predefined',
                        'field_type'  => 'text',
                        'reason_code' =>'company_name_not_matched'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedKycClarificationReasons, $kycClarificationReasons);

        // Test KYC clarification reasons for Partner activation
        $partnerActivationInput = [
            'merchant_id'       => $mid,
            'activation_status' => 'under_review',
            'submitted'         => true
        ];

        $partnerActivation = $this->fixtures->create('partner_activation', $partnerActivationInput);

        $partnerKycClarificationReasons =  (new Core())->composeNeedsClarificationReason($partnerActivation);

        $expectedPartnerKycClarificationReasons = [
            'clarification_reasons' =>  [
                'gstin' => [
                    [
                        'reason_type' =>  'predefined',
                        'field_type' => 'text',
                        'reason_code' => 'invalid_gstin_number'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedPartnerKycClarificationReasons, $partnerKycClarificationReasons);
    }

    public function testReasonComposerForGstAndBankIncorrectForPartner()
    {
        $input = [
            'poi_verification_status'                => 'verified',
            'bank_details_verification_status'       => 'incorrect_details',
            'gstin_verification_status'              => 'incorrect_details',
            'shop_establishment_verification_status' => 'not_matched',
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $mid = $merchantDetail->getId();

        $this->mockRazorxTreatment('on');

        $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'gstin',
            'error_code'    => 'INPUT_DATA_ISSUE',
            'validation_status' => 'failed'
        ]);

        $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'bank_account',
            'error_code'    => 'INPUT_DATA_ISSUE',
            'validation_status' => 'failed'
        ]);

        $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'shop_establishment',
            'error_code'    => 'RULE_EXECUTION_FAILED',
            'validation_status' => 'failed'
        ]);

        // Test KYC clarification reasons for Partner activation
        $partnerActivationInput = [
            'merchant_id'       => $mid,
            'activation_status' => 'under_review',
            'submitted'         => true
        ];

        $partnerActivation = $this->fixtures->create('partner_activation', $partnerActivationInput);

        $partnerKycClarificationReasons =  (new Core())->composeNeedsClarificationReason($partnerActivation);

        $expectedPartnerKycClarificationReasons = [
            'additional_details' =>  [
                'cancelled_cheque' => [
                    [
                        'reason_type' => "predefined",
                        'field_type' => "document",
                        'field_value' => null,
                        'reason_code' => "bank_account_change_request_for_prop_ngo_trust"
                    ]
                ],
                'bank_account_name' => [
                    [
                        'reason_type' => "predefined",
                        'field_type' => "text",
                        'field_value' => "test",
                        'reason_code' => "bank_account_change_request_for_prop_ngo_trust"
                    ]
                ],
                'bank_account_number' => [
                    [
                        'reason_type' => "predefined",
                        'field_type' => "text",
                        'field_value' => "123456789012345",
                        'reason_code' => "bank_account_change_request_for_prop_ngo_trust"
                    ]
                ],
                'bank_branch_ifsc' => [
                    [
                        'reason_type' => "predefined",
                        'field_type' => "text",
                        'field_value' => "ICIC0000001",
                        'reason_code' => "bank_account_change_request_for_prop_ngo_trust"
                    ]
                ]
           ],
            'clarification_reasons' => [
                'gstin' =>  [
                    [
                        'reason_type' => "predefined",
                        'field_type' => "text",
                        'reason_code' => "invalid_gstin_number"
                    ]
                ]
            ]
        ];

        $this->assertEquals($partnerKycClarificationReasons, $expectedPartnerKycClarificationReasons);
    }

    public function testReasonComposerForShopEstablishmentAndCinNotMatched() {
        $input          = [
            'poi_verification_status'                => 'verified',
            'poa_verification_status'                => 'verified',
            'bank_details_verification_status'       => 'verified',
            'gstin_verification_status'              => 'not_matched',
            'shop_establishment_verification_status' => 'not_matched',
        ];
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $mid = $merchantDetail->getId();

        $this->mockRazorxTreatment('on');

        $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'shop_establishment',
            'error_code'    => 'RULE_EXECUTION_FAILED',
            'rule_execution_list' => [
                0 => [
                    'rule' => [
                        'rule_type' => 'string_comparison_rule',
                        'rule_def' => [
                            'fuzzy_suzzy' => [
                                0 => [
                                    'var' => 'artefact.details.owner_name.value',
                                ],
                                1 => [
                                    'var' => 'enrichments.online_provider.details.owner_name.value',
                                ],
                                2 => 70,
                            ],
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'fuzzy_suzzy',
                        'operands' => [
                            'operand_1' => 'CHIZRINZ INFOWAY PRIVATE LIMITED',
                            'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                            'operand_3' => 70,
                        ],
                        'remarks' => [
                            'algorithm_type' => 'fuzzy_suzzy_default_algorithm',
                            'match_percentage' => 49,
                            'required_percentage' => 70,
                        ],
                    ],
                    'error' => '',
                ],
                1 => [
                    'rule' => [
                        'rule_type' => 'array_comparison_rule',
                        'rule_def' => [
                            'fuzzy_wuzzy' => [
                                [
                                    'var' => 'artefact.details.entity_name.value'
                                ],
                                [
                                    'var' => 'enrichments.online_provider.details.entity_name.value'
                                ],
                                81,
                                [
                                    "private limited",
                                    "limited liability partnership",
                                    "pvt",
                                    "ltd",
                                    "."
                                ],
                            ]
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'fuzzy_wuzzy',
                        'operands' => [
                            'operand_1' => 'HARSHILMATHUR ',
                            'operand_2' => 'Rzp Test QA Merchant',
                            'operand_3' => 70,
                        ],
                        'remarks' => [
                            'algorithm_type' => 'fuzzy_wuzzy_default_algorithm',
                            'match_percentage' => 30,
                            'required_percentage' => 70,
                        ],
                    ],
                    'error' => '',
                ]
            ],
            'validation_status' => 'failed'
        ]);

        $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'gstin',
            'error_code'    => 'RULE_EXECUTION_FAILED',
            'rule_execution_list' => [
                0 => [
                    'rule' => [
                        'rule_type' => 'string_comparison_rule',
                        'rule_def' => [
                            'or' => [
                                0 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'artefact.details.legal_name.value',
                                        ],
                                        1 => [
                                            'var' => 'enrichments.online_provider.details.legal_name.value',
                                        ],
                                        2 => 70,
                                    ],
                                ],
                                1 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'artefact.details.trade_name.value',
                                        ],
                                        1 => [
                                            'var' => 'enrichments.online_provider.details.trade_name.value',
                                        ],
                                        2 => 70,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'or',
                        'operands' => [
                            'operand_1' => [
                                'result' => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'Rzp Test QA Merchant',
                                    'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                                    'operand_3' => 70,
                                ],
                                'remarks' => [
                                    'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                    'match_percentage'      => 45,
                                    'required_percentage'   => 70,
                                ],
                            ],
                            'operand_2' => [
                                'result' => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'CHIZRINZ INFOWAY PRIVATE LIMITED',
                                    'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                                    'operand_3' => 70,
                                ],
                                'remarks' => [
                                    'algorithm_type'       => 'fuzzy_wuzzy_default_algorithm',
                                    'match_percentage'     => 68,
                                    'required_percentage'  => 70,
                                ],
                            ],
                        ],
                        'remarks' => [
                            'algorithm_type'      => 'fuzzy_wuzzy_default_algorithm',
                            'match_percentage'    => 68,
                            'required_percentage' => 70,
                        ],
                    ],
                    'error' => '',
                ],
                1 => [
                    'rule' => [
                        'rule_type' => 'array_comparison_rule',
                        'rule_def' => [
                            'some' => [
                                0 => [
                                    'var' => 'enrichments.online_provider.details.signatory_names',
                                ],
                                1 => [
                                    'fuzzy_wuzzy' => [
                                        0 => [
                                            'var' => 'each_array_element',
                                        ],
                                        1 => [
                                            'var' => 'artefact.details.legal_name.value',
                                        ],
                                        2 => 70,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'rule_execution_result' => [
                        'result' => false,
                        'operator' => 'some',
                        'operands' => [
                            'operand_1' => [
                                'result' => false,
                                'operator' => 'fuzzy_wuzzy',
                                'operands' => [
                                    'operand_1' => 'HARSHILMATHUR ',
                                    'operand_2' => 'Rzp Test QA Merchant',
                                    'operand_3' => 70,
                                ],
                                'remarks' => [
                                    'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                    'match_percentage'      => 30,
                                    'required_percentage'   => 70,
                                ],
                            ],
                            'operand_2' => [
                                'result' => false,
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
                        'remarks' => null,
                    ]
                ]
            ],
            'validation_status' => 'failed'
        ]);

        $kycClarificationReasons =  (new Core())->composeNeedsClarificationReason($merchantDetail);

        $expectedKycClarificationReasons = [
            'clarification_reasons' =>  [
                'promoter_pan_name' => [
                    [
                        'reason_type' => 'predefined',
                        'field_type'  =>'text',
                        'reason_code' => 'signatory_name_not_matched'
                    ]
                ],
                'business_name' => [
                    [
                        'reason_type' => 'predefined',
                        'field_type'  => 'text',
                        'reason_code' =>'company_name_not_matched'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedKycClarificationReasons, $kycClarificationReasons);

    }
}
