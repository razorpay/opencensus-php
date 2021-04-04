<?php

namespace RZP\Functional\Payment\GatewayRule;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Tests\Functional\RequestResponseFlowTrait;

/**
 * Tests CRUD operations on gateway_rule entity
 */
class GatewayRuleTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/GatewayRuleTestData.php';

        parent::setUp();
    }

    public function testCreateGatewayRule()
    {
        $this->ba->adminAuth();

        $testCases = $this->testData[__FUNCTION__];

        //
        // All test cases have the below format
        // [
        //      'fixtures' => <any rules that neds to be created via fixtures
        //      'request' => 'create request to be made'
        //      'response' => expected response
        // ]
        //
        foreach ($testCases as $test)
        {
            $this->runTestCase($test);
        }
    }

    public function testCreateAuthenticationGatewayRule()
    {
        $this->ba->adminAuth();

        $testCases = $this->testData[__FUNCTION__];

        //
        // All test cases have the below format
        // [
        //      'fixtures' => <any rules that neds to be created via fixtures
        //      'request' => 'create request to be made'
        //      'response' => expected response
        // ]
        //
        foreach ($testCases as $test)
        {
            $this->runTestCase($test);
        }
    }

    public function testUpdateGatewayRule()
    {
        $this->ba->adminAuth();

        $testCases = $this->testData[__FUNCTION__];

        //
        // All test cases have the below format
        // [
        //      'to_update' => Existing rule which needs to be updated
        //      'fixtures' => <any rules that neds to be created via fixtures
        //      'request' => 'create request to be made'
        //      'response' => expected response
        // ]
        //
        foreach ($testCases as $test)
        {
            $test['step'] = 'authorization';

            $this->runTestCase($test);
        }
    }

    public function testDeleteGatewayRule()
    {
        $rule = $this->fixtures->gateway_rule->create(
            [
                'method'      => 'card',
                'type'        => 'sorter',
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'min_amount'  => 0,
                'load'        => 50,
                'step'        => 'authorization',
            ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/rules/' . $rule->getId();

        $this->ba->adminAuth();

        $this->startTest();
    }

    protected function runTestCase(array $testData)
    {
        $rules = [];

        if (empty($testData['fixtures']) === false)
        {
            $rules = $this->createRules($testData['fixtures']);
        }

        $data = [
            'request' => $testData['request'],
            'response' => $testData['response'],
            'exception' => $testData['exception'] ?? null
        ];

        if (empty($testData['to_update']) === false)
        {
            $testData['to_update']['step'] = 'authorization';

            $ruleToUpdate = $this->fixtures->create('gateway_rule', $testData['to_update']);

            $rules[] = $ruleToUpdate->getId();

            $data['request']['url'] = '/gateway/rules/' . $ruleToUpdate->getId();
        }

        $content = $this->runRequestResponseFlow($data);

        if ((empty($content['id']) === false) and
            (in_array($content['id'], $rules, true)) === false)
        {
            $rules[] = $content['id'];
        }

        $this->fixtures->gateway_rule->delete($rules);
    }

    protected function createRules(array $ruleParams, $step = 'authorization'): array
    {
        $ruleIds = [];

        foreach ($ruleParams as $params)
        {
            $params['step'] = $step;

            $rule = $this->fixtures->create('gateway_rule', $params);

            $ruleIds[] = $rule->getId();
        }

        return $ruleIds;
    }

    public function testGatewayRuleAuthSorterLoadTest()
    {
        $this->ba->adminAuth();

        $testDataRule1 = [
                'request' => [
                    'content' => [
                        'method'        => 'card',
                        'merchant_id'   => '10000000000000',
                        'gateway'       => 'hdfc',
                        'type'          => 'sorter',
                        'load'          => 50,
                        'group'         => 'authentication',
                        'auth_type'     => '3ds',
                        'step'          => 'authentication',
                    ],
                    'url' => '/gateway/rules',
                    'method' => 'POST',
                ],
                'response' => [
                    'content' => []
                ],
        ];

        $this->runRequestResponseFlow($testDataRule1);

        $testDataRule2 = [
                'request' => [
                    'content' => [
                        'method'        => 'card',
                        'merchant_id'   => '10000000000000',
                        'gateway'       => 'first_data',
                        'type'          => 'sorter',
                        'load'          => 100,
                        'group'         => 'authentication',
                        'auth_type'     => '3ds',
                        'step'          => 'authentication',
                    ],
                    'url' => '/gateway/rules',
                    'method' => 'POST',
                ],
                'response' => [
                    'content' => []
                ],
        ];

        $this->runRequestResponseFlow($testDataRule2);

        $testDataRule3 = [
                'request' => [
                    'content' => [
                        'method'        => 'card',
                        'merchant_id'   => '10000000000000',
                        'gateway'       => 'first_data',
                        'type'          => 'sorter',
                        'load'          => 100,
                        'group'         => 'authentication',
                        'auth_type'     => 'headless_otp',
                        'step'          => 'authentication',
                    ],
                    'url' => '/gateway/rules',
                    'method' => 'POST',
                ],
                'response' => [
                    'content' => [
                        'error' => [
                            'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                            'description' => 'Load across all gateway rules must be less than 100 percent',
                        ]
                    ],
                    'status_code' => 400
                    ],
                'exception' => [
                    'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                    'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
                ],
        ];

        $this->runRequestResponseFlow($testDataRule3);
    }

    public function testGatewayRuleAuthFilterTest()
    {
        $this->ba->adminAuth();

        $testDataRule1 = [
                'request' => [
                    'content' => [
                        'method'        => 'card',
                        'merchant_id'   => '10000000000000',
                        'gateway'       => 'hdfc',
                        'type'          => 'filter',
                        'filter_type'   => 'select',
                        'group'         => 'authentication',
                        'auth_type'     => '3ds',
                        'step'          => 'authentication',
                        'authentication_gateway' => 'mpi_blade'
                    ],
                    'url' => '/gateway/rules',
                    'method' => 'POST',
                ],
                'response' => [
                    'content' => []
                ],
        ];

        $this->runRequestResponseFlow($testDataRule1);

        $testDataRule2 = [
                'request' => [
                    'content' => [
                        'method'        => 'card',
                        'merchant_id'   => '10000000000000',
                        'gateway'       => 'cybersource',
                        'type'          => 'filter',
                        'filter_type'   => 'select',
                        'group'         => 'authentication',
                        'auth_type'     => '3ds',
                        'step'          => 'authentication',
                    ],
                    'url' => '/gateway/rules',
                    'method' => 'POST',
                ],
                'response' => [
                    'content' => []
                ],
        ];

        $this->runRequestResponseFlow($testDataRule2);

        $testDataRule3 = [
                'request' => [
                    'content' => [
                        'method'        => 'card',
                        'merchant_id'   => '10000000000000',
                        'gateway'       => 'hdfc',
                        'type'          => 'filter',
                        'filter_type'   => 'select',
                        'group'         => 'authentication',
                        'auth_type'     => 'headless_otp',
                        'step'          => 'authentication',
                        'authentication_gateway' => 'mpi_blade'
                    ],
                    'url' => '/gateway/rules',
                    'method' => 'POST',
                ],
                 'response' => [
                    'content' => []
                ],
        ];

        $this->runRequestResponseFlow($testDataRule3);
    }

    public function testAddGatewayRulesWithOrgId()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprAdminToken', 'org_'.Org::RAZORPAY_ORG_ID, null,
            $org->getPublicId());

        $request = [
            'request' => [
                'content' => [
                    'method'          => 'card',
                    'gateway'         => 'hdfc',
                    'type'            => 'filter',
                    'filter_type'     => 'select',
                    'group'           => 'direct_filter',
                    'step'            => 'authorization',
                    'shared_terminal' => 0,
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' =>[
                    'org_id'          => $org->getId(),
                    'method'          => 'card',
                    'gateway'         => 'hdfc',
                    'type'            => 'filter',
                    'filter_type'     => 'select',
                    'group'           => 'direct_filter',
                    'step'            => 'authorization',
                    'shared_terminal' => false,
                ]
            ],
        ];

        $this->runRequestResponseFlow($request);
    }

    public function testAddGatewayRulesWithMerchantId()
    {
        $this->ba->adminAuth('test');

        $request = [
            'request' => [
                'content' => [
                    'merchant_id'     => '10000000000000',
                    'method'          => 'card',
                    'gateway'         => 'hdfc',
                    'type'            => 'filter',
                    'filter_type'     => 'select',
                    'group'           => 'direct_filter',
                    'step'            => 'authorization',
                    'shared_terminal' => 0,
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' =>[
                    'merchant_id'     => '10000000000000',
                    'method'          => 'card',
                    'gateway'         => 'hdfc',
                    'type'            => 'filter',
                    'filter_type'     => 'select',
                    'group'           => 'direct_filter',
                    'step'            => 'authorization',
                    'shared_terminal' => false,
                ]
            ],
        ];

        $this->runRequestResponseFlow($request);
    }
}
