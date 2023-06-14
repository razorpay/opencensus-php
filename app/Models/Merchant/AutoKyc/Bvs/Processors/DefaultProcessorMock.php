<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Processors;

use Rzp\Bvs\Validation\V1\Error;
use RZP\Exception\LogicException;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\AutoKyc\Response;
use Rzp\Bvs\Validation\V2\ValidationResponse as ValidationResponseV2;
use Rzp\Bvs\Validation\V1\ValidationResponse as ValidationResponseV1;
use RZP\Models\Merchant\BvsValidation\Entity as BvsValidationEntity;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\ValidationBaseResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\ValidationBaseResponseV2;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\ValidationDetailsResponse;

class DefaultProcessorMock extends DefaultProcessor
{
    private $mockStatus;

    private $mockValidationDetail;

    const UNITTEST_VALIDATION_ARRAY_CACHE_KEY = 'unittest_bvs_validation_array';

    const UNITTEST_VALIDATION_ARRAY_CACHE_TTL = 15 * 60; // 15 minutes

    /**
     * @param array  $input
     * @param        $configName
     *
     * @param        $merchant
     *
     * @throws LogicException
     */
    public function __construct(array $input, $configName, $merchant)
    {
        parent::__construct($input, $configName, $merchant);

        //
        // This config is not defined in application config , this is used in test case only
        //
        $this->setMockStatus($this->app['config']['services.bvs.response'] ?? Constant::SUCCESS);

        $this->setMockValidationDetail($this->app['config']['services.bvs.validationDetail'] ?? []);
    }

    public function Process($sendEnrichmentDetails = false, $skipAsyncFlow = false ): Response
    {
        $app = \App::getFacadeRoot();

        if ($app->runningUnitTests() === true)
        {
            // setting it to redis here so that we can assert in tests that the correct values were sent to BvsService
            $app['cache']->put(self::UNITTEST_VALIDATION_ARRAY_CACHE_KEY,
                               $this->getCreateValidationArray(),
                               self::UNITTEST_VALIDATION_ARRAY_CACHE_TTL);
        }

        switch ($this->mockStatus)
        {
            case Constant::SUCCESS:

                $bvsValidation = new BvsValidationEntity();

                $bvsValidation->generateId();

                if ($this->requestMode() == Constant::SYNC)
                {
                    $validationResponse = new ValidationResponseV2();

                    $validationResponse->setValidationId($bvsValidation->getId());

                    $validationResponse->setStatus('success');

                    $validationResponse->setRuleExecutionList($this->getRuleExecutionList('success'));

                    if ($sendEnrichmentDetails === true)
                    {
                        $validationResponse->setEnrichmentDetails(
                            get_Protobuf_Struct([
                                                  'online_provider' => [
                                                      'details' => [
                                                          'registration_date'   => [
                                                              [
                                                                  'value' => "16/02/2020",
                                                              ]
                                                          ],
                                                          'aggregate_turnover'  => "Slab: Rs. 5 Cr. to 25 Cr."
                                                      ]
                                                  ]
                                              ])
                        );
                    }

                    return new ValidationBaseResponseV2($validationResponse);
                }
                else
                {
                    $validationResponse = new ValidationResponseV1();

                    $validationResponse->setValidationId($bvsValidation->getId());

                    $validationResponse->setStatus('captured');

                    return new ValidationBaseResponse($validationResponse);
                }
                break;

            case Constant::FAILURE:

                $error = new Error();

                $bvsValidation = new BvsValidationEntity();

                $bvsValidation->generateId();

                if ($this->requestMode() == Constant::SYNC)
                {
                    $validationResponse = new ValidationResponseV2();

                    $validationResponse->setValidationId($bvsValidation->getId());

                    $validationResponse->setStatus('failed');

                    $validationResponse->setRuleExecutionList($this->getRuleExecutionList('failure'));

                    if($this->app['config']['services.bvs.input.error'] == true)
                    {
                        $validationResponse->setErrorCode("INPUT_DATA_ISSUE");

                        $validationResponse->setErrorDescription("KC07: Account Closed");
                    }
                    else
                    {
                        $validationResponse->setErrorCode("BAD_REQUEST_VALIDATION_ERROR");

                        $validationResponse->setErrorDescription("merchant type is not supported");
                    }
                    return new ValidationBaseResponseV2($validationResponse);
                }
                else
                {
                    $validationResponse = new ValidationResponseV1();

                    $validationResponse->setStatus('failed');

                    $validationResponse->setErrorCode("BAD_REQUEST_VALIDATION_ERROR");

                    $validationResponse->setErrorDescription("merchant type is not supported");

                    return new ValidationBaseResponse($validationResponse);
                }
                break;

            default:

                throw new IntegrationException("integration error: failed to make request");
        }

    }

    public function FetchDetails(string $validationId): Response
    {
        $status = $this->mockStatus ?? 'success';

        $data = [
            'validation_id'      => $validationId,
            'status'             => $status,
            'enrichment_details' => get_Protobuf_Struct([
                                                            'online_provider' => [
                                                                'details' => [
                                                                    'account_holder_names' => [
                                                                        [
                                                                            'score' => 0,
                                                                            'value' => 'name 1'
                                                                        ],
                                                                        [
                                                                            'score' => 0,
                                                                            'value' => 'name 2'
                                                                        ]
                                                                    ],
                                                                    'account_status'       => [
                                                                        'value' => 'active',
                                                                    ]
                                                                ]
                                                            ]
                                                        ])
        ];

        $data = array_merge($data, $this->mockValidationDetail);

        $validationResponse = new ValidationResponseV1($data);

        return new ValidationDetailsResponse($validationResponse);
    }

    public function setMockStatus(string $mockStatus)
    {
        $this->mockStatus = $mockStatus;
    }

    public function setMockValidationDetail(array $validationDetail)
    {
        $this->mockValidationDetail = $validationDetail;
    }

    private function getRuleExecutionList($status)
    {
        switch ($status)
        {
            case 'success':
                $body = '{
        "details": {
            "0": {
                "error": "",
                "rule": {
                    "rule_def": {
                        "or": [
                            {
                                "fuzzy_suzzy": [
                                    {
                                        "var": "artefact.details.legal_name.value"
                                    },
                                    {
                                        "var": "enrichments.online_provider.details.legal_name.value"
                                    },
                                    81
                                ]
                            },
                            {
                                "fuzzy_wuzzy": [
                                    {
                                        "var": "artefact.details.trade_name.value"
                                    },
                                    {
                                        "var": "enrichments.online_provider.details.trade_name.value"
                                    },
                                    81,
                                    [
                                        "private limited",
                                        "limited liability partnership",
                                        "pvt",
                                        "ltd",
                                        "."
                                    ]
                                ]
                            }
                        ]
                    },"rule_type": "string_comparison_rule"
                },
                "rule_execution_result": {
                    "operands": {
                        "operand_1": {
                            "operands": {
                                "operand_1": "Rzp Test QA Merchant",
                                "operand_2": "RELIANCE INDUSTRIES LIMITED",
                                "operand_3": 81
                            },
                            "operator": "fuzzy_suzzy",
                            "remarks": {
                                "algorithm_type": "fuzzy_suzzy_lev_token_set_algorithm",
                                "match_percentage": 34,
                                "required_percentage": 81
                            },
                            "result": false
                        },
                        "operand_2": {
                            "operands": {
                                "operand_1": "CHIZRINZ INFOWAY PRIVATE LIMITED","operand_2": "CHIZRINZ INFOWAY PRIVATE LIMITED",
                                "operand_3": 81,
                                "operand_4": [
                                    "private limited",
                                    "limited liability partnership",
                                    "pvt",
                                    "ltd",
                                    "."
                                ]
                            },
                            "operator": "fuzzy_wuzzy",
                            "remarks": {
                                "algorithm_type": "fuzzy_wuzzy_custom_algorithm_4",
                                "match_percentage": 100,
                                "required_percentage": 81
                            },
                            "result": true
                        }
                    },
                    "operator": "or",
                    "remarks": {
                        "algorithm_type": "fuzzy_wuzzy_custom_algorithm_4",
                        "match_percentage": 100,
                        "required_percentage": 81
                    },
                    "result": true
                }
            },
            "1": {
                "error": "",
                "rule": {
                    "rule_def": {
                        "some": [
                            {
                                "var": "enrichments.online_provider.details.signatory_names"
                            },
                            {
                                "fuzzy_suzzy": [{
                                        "var": "artefact.details.legal_name.value"
                                    },
                                    {
                                        "var": "each_array_element"
                                    },
                                    81
                                ]
                            }
                        ]
                    },
                    "rule_type": "array_comparison_rule"
                },
                "rule_execution_result": {
                    "operands": {
                        "operand_1": {
                            "operands": {
                                "operand_1": "Rzp Test QA Merchant",
                                "operand_2": "Rzp Test QA Merchant",
                                "operand_3": 81
                            },
                            "operator": "fuzzy_suzzy",
                            "remarks": {
                                "algorithm_type": "fuzzy_suzzy_lev_token_set_algorithm",
                                "match_percentage": 100,
                                "required_percentage": 81
                            },
                            "result": true
                        }
                    },
                    "operator": "some",
                    "remarks": {
                        "algorithm_type": "fuzzy_suzzy_lev_token_set_algorithm",
                        "match_percentage": 100,
                        "required_percentage": 81},
                    "result": true
                }
            }
        }
    }';

                $arr = json_decode($body, true);
                return get_Protobuf_Struct($arr);

            case 'failure':
                $body = '{
        "details": {
            "0": {
                "error": "",
                "rule": {
                    "rule_def": {
                        "or": [
                            {
                                "fuzzy_suzzy": [
                                    {
                                        "var": "artefact.details.legal_name.value"
                                    },
                                    {
                                        "var": "enrichments.online_provider.details.legal_name.value"
                                    },
                                    81
                                ]
                            },
                            {
                                "fuzzy_wuzzy": [
                                    {
                                        "var": "artefact.details.trade_name.value"
                                    },
                                    {
                                        "var": "enrichments.online_provider.details.trade_name.value"
                                    },
                                    81,
                                    [
                                        "private limited",
                                        "limited liability partnership",
                                        "pvt",
                                        "ltd",
                                        "."
                                    ]
                                ]
                            }
                        ]
                    },"rule_type": "string_comparison_rule"
                },
                "rule_execution_result": {
                    "operands": {
                        "operand_1": {
                            "operands": {
                                "operand_1": "Rzp Test QA Merchant",
                                "operand_2": "RELIANCE INDUSTRIES LIMITED",
                                "operand_3": 81
                            },
                            "operator": "fuzzy_suzzy",
                            "remarks": {
                                "algorithm_type": "fuzzy_suzzy_lev_token_set_algorithm",
                                "match_percentage": 34,
                                "required_percentage": 81
                            },
                            "result": false
                        },
                        "operand_2": {
                            "operands": {
                                "operand_1": "CHIZRINZ INFOWAY PRIVATE LIMITED","operand_2": "CHIZRINZ INFOWAY PRIVATE LIMITED",
                                "operand_3": 81,
                                "operand_4": [
                                    "private limited",
                                    "limited liability partnership",
                                    "pvt",
                                    "ltd",
                                    "."
                                ]
                            },
                            "operator": "fuzzy_wuzzy",
                            "remarks": {
                                "algorithm_type": "fuzzy_wuzzy_custom_algorithm_4",
                                "match_percentage": 100,
                                "required_percentage": 81
                            },
                            "result": true
                        }
                    },
                    "operator": "or",
                    "remarks": {
                        "algorithm_type": "fuzzy_wuzzy_custom_algorithm_4",
                        "match_percentage": 100,
                        "required_percentage": 81
                    },
                    "result": false
                }
            },
            "1": {
                "error": "",
                "rule": {
                    "rule_def": {
                        "some": [
                            {
                                "var": "enrichments.online_provider.details.signatory_names"
                            },
                            {
                                "fuzzy_suzzy": [{
                                        "var": "artefact.details.legal_name.value"
                                    },
                                    {
                                        "var": "each_array_element"
                                    },
                                    81
                                ]
                            }
                        ]
                    },
                    "rule_type": "array_comparison_rule"
                },
                "rule_execution_result": {
                    "operands": {
                        "operand_1": {
                            "operands": {
                                "operand_1": "Rzp Test QA Merchant",
                                "operand_2": "Rzp Test QA Merchant",
                                "operand_3": 81
                            },
                            "operator": "fuzzy_suzzy",
                            "remarks": {
                                "algorithm_type": "fuzzy_suzzy_lev_token_set_algorithm",
                                "match_percentage": 100,
                                "required_percentage": 81
                            },
                            "result": true
                        }
                    },
                    "operator": "some",
                    "remarks": {
                        "algorithm_type": "fuzzy_suzzy_lev_token_set_algorithm",
                        "match_percentage": 100,
                        "required_percentage": 81},
                    "result": true
                }
            }
        }
    }';

                $arr = json_decode($body, true);
                return get_Protobuf_Struct($arr);

            default:
                return get_Protobuf_Struct([]);

        }

    }

    /**
     * @param array $input
     *
     * @return array|string[]
     */
    public function getVerificationUrl(array $input)
    {
        if ($this->mockStatus === Constant::SUCCESS)
        {
            return [
                'verification_url' => 'https://api.digitallocker.gov.in/public'
            ];
        }

        return [
            'code'    => 'unavailable',
            'message' => 'hyperverge gateway request failed with http code - 500  internal code  - ER_SERVER, error - Something went wrong',
            'meta'    => [
                "internal_error_code" => "SERVER_ERROR",
                "public_error_code"   => "some_error_encountered"
            ]
        ];
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function fetchVerificationDetails(array $input)
    {
        if($this->mockStatus === Constant::SUCCESS)
        {
            return [
                'is_valid' => true,
                'probe_id' => 'HxOGLBW7n6AD2f',
                'file_url' => 'https://thinkbespoke.com.au/wp-content/uploads/2019/12/precondo-ca-QHDFm084RNk-unsplash.xml'
            ];
        }

        return [
            'code'    => 'unavailable',
            'message' => 'hyperverge gateway request failed with http code - 500  internal code  - ER_SERVER, error - Something went wrong',
            'meta'    => [
                "internal_error_code" => "SERVER_ERROR",
                "public_error_code"   => "some_error_encountered"
            ]
        ];
    }
}
