<?php

namespace RZP\Functional\Payment\GatewayRule;

use Carbon\Carbon;
use RZP\Models\Merchant;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal\Options;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

/**
 * Tests CRUD operations on gateway_rule entity
 */
class GatewayRuleTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/GatewayRuleTestData.php';

        parent::setUp();
    }

    public function testCreateGatewayRule()
    {
        $this->ba->appAuth();

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
        $this->ba->appAuth();

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
                'load'        => 50
            ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/rules/' . $rule->getId();

        $this->ba->appAuth();

        $content = $this->startTest();
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

    protected function createRules(array $ruleParams): array
    {
        foreach ($ruleParams as $params)
        {
            $rule = $this->fixtures->create('gateway_rule', $params);

            $ruleIds[] = $rule->getId();
        }

        return $ruleIds;
    }
}
