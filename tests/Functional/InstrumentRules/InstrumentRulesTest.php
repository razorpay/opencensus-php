<?php

namespace Functional\InstrumentRules;

use Event;
use RZP\Models\Merchant;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class InstrumentRulesTest extends TestCase
{
    use TerminalTrait;
    use RequestResponseFlowTrait;

    protected $terminalsServiceMock;

    const EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      = 'expected_path';
    const EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    = 'expected_method';
    const EXPECTED_REQUEST_CONTENT_TERMINALS_SERVICE   = 'expected_request_to_terminals_service';
    const EXPECTED_REQUEST_HEADERS_TERMINALS_SERVICE   = 'expected_request_headers_terminals_service';
    const REQUEST                                      = 'request';

    const EVENT_KEYS = [
        Merchant\Detail\Entity::MERCHANT_ID,
        Merchant\Entity::ORG_ID,
        Merchant\Entity::CATEGORY,
        Merchant\Entity::CATEGORY2,
        Merchant\Entity::WEBSITE,
        Merchant\Detail\Entity::BUSINESS_TYPE,
        Merchant\Detail\Entity::ACTIVATION_STATUS,
    ];

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/InstrumentRulesTestData.php';

        parent::setUp();

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();
    }

    public function testMerchantOnSavedEvent()
    {
        $this->markTestSkipped();
        $this->ba->adminAuth();

        $testCase = [
            self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/instrument_rules/event',
            self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
        ];

        $this->mockTerminalsServiceHandleRequestAndResponse(function ($path) use ($testCase) {

            return ($path ===  $testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE]);

        }, function ($path, $content, $method) use ($testCase) {

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

            $this->assertArrayKeysExist(json_decode($content, true), self::EVENT_KEYS);

            $response = new \WpOrg\Requests\Response;

            $response->body = '
                       {
                        "data": {
                           "testKey": "testValue"
                        }
                    }';

            return $response;
        }, 4);

        $this->startTest();
    }

    public function testMerchantDetailOnSavedEvent()
    {
        $this->markTestSkipped();
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant       = $merchantDetail->merchant;

        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();

        $testCase = [
            self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/instrument_rules/event',
            self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
        ];

        $this->mockTerminalsServiceHandleRequestAndResponse(function ($path) use ($testCase) {

            return ($path ===  $testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE]);

        }, function ($path, $content, $method) use ($testCase) {

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

            $this->assertArrayKeysExist(json_decode($content, true), self::EVENT_KEYS);

            $response = new \WpOrg\Requests\Response;

            $response->body = '
                       {
                        "data": {
                           "testKey": "testValue"
                        }
                    }';

            return $response;
        }, 4);

        $this->startTest();
    }

    public function testMerchantDetailOnSavedEventWithruleBasedFeatureFlag()
    {
        $this->markTestSkipped();
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant       = $merchantDetail->merchant;

        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->fixtures->feature->create(
            [
                'entity_type' => 'merchant',
                'entity_id'   => $merchant['id'],
                'name'        => 'rule_based_enablement'
            ]
        );

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();
    }

    public function testMerchantManualTriggerEventThrowsException()
    {
        $this->markTestSkipped();
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant       = $merchantDetail->merchant;

        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();

        $testCase = [
            self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE      => 'v2/instrument_rules/event',
            self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE    => \Requests::POST,
        ];

        $this->mockTerminalsServiceHandleRequestAndResponse(function ($path) use ($testCase) {

            return ($path ===  $testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE]);

        }, function ($path, $content, $method) use ($testCase) {

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_PATH_TERMINALS_SERVICE], $path);

            $this->assertEquals($testCase[self::EXPECTED_REQUEST_METHOD_TERMINALS_SERVICE], $method);

            $this->assertArrayKeysExist(json_decode($content, true), self::EVENT_KEYS);

            $response = new \WpOrg\Requests\Response;

            $response->body = '
                       {
                        "data": {
                           "testKey": "testValue"
                        }
                    }';

            return $response;
        }, 1);

        $this->startTest();
    }

    public function testMerchantManualTriggerEventThrowsExceptionWithRuleBasedFeatureFlag()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant       = $merchantDetail->merchant;

        $this->fixtures->feature->create(
            [
                'entity_type' => 'merchant',
                'entity_id'   => $merchant['id'],
                'name'        => 'rule_based_enablement'
            ]
        );

        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();
    }

}
