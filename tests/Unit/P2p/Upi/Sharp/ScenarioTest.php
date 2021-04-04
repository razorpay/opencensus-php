<?php

namespace RZP\Tests\Unit\P2p\Upi\Sharp;

use RZP\Constants\Mode;
use RZP\Gateway\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Gateway\P2p\Upi\Mock\Scenario;
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Tests\Functional\Partner\Commission\Action;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\Axis\Actions\DeviceAction;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class ScenarioTest extends TestCase
{
    /**
     * @var Context
     */
    protected $context;

    protected $action;

    protected $mode = Mode::TEST;

    protected $gateway = 'p2p_upi_sharp';

    protected $entity;

    /**
     * @var Scenario
     */
    protected $scenario;

    /**
     * @var ArrayBag
     */
    protected $gatewayInput;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setContext();

        $this->gatewayInput = new ArrayBag();
    }

    public function testClone()
    {
        $array = [
            'a' => 1,
            'b' => 2,
            'c' => [
                'd' => 4,
                'e' => 5,
            ]
        ];

        $cloned = $this->clone($array, function(& $array)
        {
            $array['c']['d'] = 40;
            unset($array['c']['e']);
        });

        // Array was not changed
        $this->assertSame([
            'a' => 1,
            'b' => 2,
            'c' => [
                'd' => 4,
                'e' => 5,
            ]
        ], $array);

        $this->assertSame([
            'a' => 1,
            'b' => 2,
            'c' => [
                'd' => 40,
            ]
        ], $cloned);
    }

    public function testScenarioConstants()
    {
        $constants = (new \ReflectionClass(Scenario::class))->getConstants();

        foreach ($constants as $constant => $value)
        {
            $this->assertSame($constant, $value);
        }
    }

    public function scenarioToArray()
    {
        $cases = [];

        $assert = [
            'id'    => Scenario::N0000,
            'sub'   => '000',
        ];
        $cases['allNull'] = [null, null, $assert, Scenario::N0000, '000'];

        $assert = [
            'id'    => Scenario::N0000,
            'sub'   => '000',
        ];
        $cases['allNullWithPreffered'] = [null, null, $assert, Scenario::BA204, '102'];

        $assert = [
            'id'    => Scenario::N0000,
            'sub'   => '001',
        ];
        $cases['differentScenarioWithPreffered'] = [Scenario::N0000, '001', $assert, Scenario::BA204, '102'];

        $assert = [
            'id'    => Scenario::N0000,
            'sub'   => '000',
        ];
        $cases['subNull'] = [Scenario::N0000, null, $assert, Scenario::N0000, '000',];

        $assert = [
            'id'    => Scenario::BA204,
            'sub'   => '000',
        ];
        $cases['defaultSubWithNull'] = [Scenario::BA204, null, $assert, Scenario::N0000, '000',];

        $assert = [
            'id'    => Scenario::BA204,
            'sub'   => '000',
        ];
        $cases['defaultSubWith000'] = [Scenario::BA204, '000', $assert, Scenario::BA204, '102',];

        $assert = [
            'id'    => Scenario::BA204,
            'sub'   => '001',
        ];
        $cases['forcedSubWith001'] = [Scenario::BA204, '001', $assert, Scenario::BA204, '001',];

        return $cases;
    }

    /**
     * @dataProvider scenarioToArray
     */
    public function testScenarioAndToArray($scenario, $sub, $assert, $prefferedId, $parsedSub)
    {
        $scenario = (new Scenario($scenario, $sub));

        $this->assertArraySubset($assert, $scenario->toArray(), true);

        $this->assertSame($parsedSub, $scenario->getParsedSub($prefferedId));
    }

    public function bankAccountRetrieve()
    {
        $bankAccount0 = [
            'ifsc'                      => 'SHRP0001010',
            'masked_account_number'     => 'xxxx141010',
            'gateway_data'              => [
                'id'                    => 'SRP1010',
            ],
            'creds'                     => [
                [
                    'type'      => 'pin',
                    'sub_type'  => 'upipin',
                    'set'       => true,
                    'length'    => 4,
                    'format'    => 'NUM',
                ],
                [
                    'type'      => 'pin',
                    'sub_type'  => 'atmpin',
                    'set'       => true,
                    'length'    => 4,
                    'format'    => 'ALPHANUM',
                ],
            ],
        ];

        $bankAccount1 = array_replace_recursive($bankAccount0, [
            'ifsc'                      => 'SHRP0001020',
            'masked_account_number'     => 'xxxx141020',
            'gateway_data'              => [
                'id'                    => 'SRP1020',
            ],
        ]);

        $bankAccount2 = array_replace_recursive($bankAccount0, [
            'ifsc'                      => 'SHRP0001030',
            'masked_account_number'     => 'xxxx141030',
            'gateway_data'              => [
                'id'                    => 'SRP1030',
            ],
        ]);

        $bankAccount20 = array_replace_recursive($bankAccount0, [
            'ifsc'                      => 'SHRP0001210',
            'masked_account_number'     => 'xxxx141210',
            'gateway_data'              => [
                'id'                    => 'SRP1210',
            ],
        ]);

        $cases = [];

        $bankAccounts = [
            0 => $bankAccount0,
            1 => $bankAccount1,
        ];
        $cases['scenario#N0000#default'] = [Scenario::N0000, '000', 2, json_encode($bankAccounts)];

        $bankAccounts = [
            0 => $bankAccount0,
            1 => $bankAccount1,
        ];
        $cases['scenario#N0000#default'] = [Scenario::N0000, '001', 2, json_encode($bankAccounts)];

        $bankAccounts = [
            0 => $bankAccount0,
        ];
        $cases['scenario#BA204#101'] = [Scenario::BA204, '101', 1, json_encode($bankAccounts)];

        $bankAccounts[1] = $bankAccount1;
        $bankAccounts[2] = $bankAccount2;
        $cases['scenario#BA204#003'] = [Scenario::BA204, '103', 3, json_encode($bankAccounts)];

        $bankAccounts[20] = $bankAccount20;
        $cases['scenario#BA204#021'] = [Scenario::BA204, '121', 21, json_encode($bankAccounts)];

        $bankAccounts = [
            0 => $this->clone($bankAccount0, function(& $i)
            {
                $i['masked_account_number'] = 'xxxx041010';
                $i['creds'][0]['set']       = false;
                $i['creds'][1]['set']       = false;
            }),
        ];
        $cases['scenario#BA204#001'] = [Scenario::BA204, '001', 1, json_encode($bankAccounts)];

        $bankAccounts = [
            0 => $this->clone($bankAccount0, function(& $i)
            {
                $i['masked_account_number'] = 'xxxxxxxxxxxx041010';
                $i['creds'][0]['set']       = false;
                $i['creds'][1]['set']       = false;
            }),
        ];
        $cases['scenario#BA204#201'] = [Scenario::BA204, '201', 1, json_encode($bankAccounts)];

        $bankAccounts = [
            0 => $this->clone($bankAccount0, function(& $i)
            {
                $i['masked_account_number'] = 'xxxxxxxxxxxx141010';
            }),
        ];
        $cases['scenario#BA204#301'] = [Scenario::BA204, '301', 1, json_encode($bankAccounts)];

        $bankAccounts = [
            0 => $this->clone($bankAccount0, function(& $i)
            {
                $i['masked_account_number'] = 'xxxx061010';
                $i['creds'][0]['set']       = false;
                $i['creds'][0]['length']    = 6;
                $i['creds'][1]['set']       = false;
                $i['creds'][1]['length']    = 6;
            }),
        ];
        $cases['scenario#BA204#401'] = [Scenario::BA204, '401', 1, json_encode($bankAccounts)];

        $bankAccounts = [
            0 => $this->clone($bankAccount0, function(& $i)
            {
                $i['masked_account_number'] = 'xxxx161010';
                $i['creds'][0]['length']    = 6;
                $i['creds'][1]['length']    = 6;
            }),
        ];
        $cases['scenario#BA204#501'] = [Scenario::BA204, '501', 1, json_encode($bankAccounts)];

        $bankAccounts = [
            0 => $this->clone($bankAccount0, function(& $i)
            {
                $i['masked_account_number'] = 'xxxxxxxxxxxx061010';
                $i['creds'][0]['set']       = false;
                $i['creds'][0]['length']    = 6;
                $i['creds'][1]['set']       = false;
                $i['creds'][1]['length']    = 6;
            }),
        ];
        $cases['scenario#BA204#601'] = [Scenario::BA204, '601', 1, json_encode($bankAccounts)];

        $bankAccounts = [
            0 => $this->clone($bankAccount0, function(& $i)
            {
                $i['masked_account_number'] = 'xxxxxxxxxxxx161010';
                $i['creds'][0]['length']    = 6;
                $i['creds'][1]['length']    = 6;
            }),
        ];
        $cases['scenario#BA204#701'] = [Scenario::BA204, '701', 1, json_encode($bankAccounts)];

        $bankAccounts = [
            0 => $this->clone($bankAccount0, function(& $i)
            {
                $i['account_number']        = '97531081010';
                $i['masked_account_number'] = 'xxxx041010';
                $i['creds'][0]['set']       = false;
                $i['creds'][1]['set']       = false;
            }),
        ];
        $cases['scenario#BA204#801'] = [Scenario::BA204, '801', 1, json_encode($bankAccounts)];

        $bankAccounts = [
            0 => $this->clone($bankAccount0, function(& $i)
            {
                $i['account_number']        = '97531081010';
                $i['masked_account_number'] = 'xxxx141010';
            }),
        ];
        $cases['scenario#BA204#901'] = [Scenario::BA204, '901', 1, json_encode($bankAccounts)];

        return $cases;
    }

    /**
     * @dataProvider bankAccountRetrieve
     */
    public function testScenarioBankAccountRetrieve($scenario, $sub, $count, $bankAccounts)
    {
        $this->entity = 'bank_account';

        $this->gatewayInput->putMany([
            'bank'  => new ArrayBag([
                'id'    => 'some_id',
                'ifsc'  => 'SHRP',
            ]),
        ]);

        $this->scenario = new Scenario($scenario, $sub);

        $response = $this->makeGatewayCall('retrieve');

        $this->assertCount($count, $response->data()['bank_accounts']);

        $bankAccounts = json_decode($bankAccounts, true);

        $this->assertArraySubset($bankAccounts, $response->data()['bank_accounts'], true);
    }

    protected function setContext()
    {
        $context = new Context();

        $context->setHandle($this->fixtures->handle(self::DEVICE_1));

        $context->setMerchant($this->fixtures->merchant(self::DEVICE_1));

        $context->setDevice($this->fixtures->device(self::DEVICE_1));

        $context->setDeviceToken($this->fixtures->deviceToken(self::DEVICE_1));

        $context->registerServices();

        $this->context = $context;
    }

    protected function makeGatewayCall($action): Base\Response
    {
        $this->context->setGatewayData($this->gateway, $action, $this->gatewayInput);

        $this->context->getOptions()->put('request_id', $this->scenario->toRequestId());

        return $this->app['gateway']->call($this->gateway, $this->entity, $this->context, $this->mode);
    }
}
