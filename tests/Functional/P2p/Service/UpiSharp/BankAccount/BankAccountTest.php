<?php

namespace RZP\Tests\P2p\Service\UpiSharp\BankAccount;

use RZP\Tests\P2p\Service\Base;
use RZP\Models\P2p\BankAccount\Entity;
use RZP\Http\Controllers\P2p\Requests;
use RZP\Tests\P2p\Service\Base\Scenario;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;
use RZP\Tests\P2p\Service\Base\Scenario as S;

class BankAccountTest extends TestCase
{
    use Base\Traits\NpciClTrait;

    public function testFetchBanks()
    {
        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $banks = $helper->fetchBanks();

        $this->assertCollection($banks, 3);
    }

    public function initiateRetrieve()
    {
        $cases = [];

        $message = 'successful';
        $cases[$message] = [[S::N0000]];

        $message = 'timedout';
        $cases[$message] = [[S::BA102]];

        return $cases;
    }

    /**
     * @dataProvider initiateRetrieve
     */
    public function testInitiateRetrieve($scenarios)
    {
        $id = 'bank_' . Base\Constants::ARZP;

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenarios[0]);

        $response = $helper->initiateRetrieve($id);

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $this->assertArraySubset([
            'version'   => 'v1',
            'type'      => 'redirect',
            'request'   => [
                'time'  => $this->fixtures->device->getCreatedAt(),
            ],
            'callback'  => route(Requests::P2P_CUSTOMER_BA_RETRIEVE, [$id]),
        ], $response);
    }

    public function retrieve()
    {
        // Scenarios, Sub Scenarios, Count based on scenarios, items base on scenarios
        $cases = [];

        $cases['scenario#BA201'] = [[S::BA201]];

        $cases['scenario#BA202'] = [[S::BA202]];

        $cases['scenario#BA203'] = [[S::BA203]];

        $items = [
            [
                $this->expectedBankAccount('xxxx141010', true, 4),
                $this->expectedBankAccount('xxxx141020', true, 4),
            ],
            [
                $this->expectedBankAccount('xxxx141010', false, 4),
            ]
        ];
        // First two bank accounts and then only one, testing that two are removed successfully
        $cases['scenario#BA204#000#001'] = [[S::BA204, S::BA204], ['000', '001'], [2, 1], json_encode($items)];

        $items = [
            [
                $this->expectedBankAccount('xxxx141010', true, 4),
            ],
            [
                $this->expectedBankAccount('xxxx141010', true, 4),
                $this->expectedBankAccount('xxxx141020', true, 4),
                $this->expectedBankAccount('xxxx141030', true, 4),
            ]
        ];
        // First one and then three, testing that only two are added
        $cases['scenario#BA204#101#103'] = [[S::BA204, S::BA204], ['101', '103'], [1, 3], json_encode($items)];

        $items = [
            [
                $this->expectedBankAccount('xxxx141010', true, 4),
                $this->expectedBankAccount('xxxx141020', true, 4),
            ],
            [
                $this->expectedBankAccount('xxxx141010', false, 6),
                $this->expectedBankAccount('xxxx141020', false, 6),
            ]
        ];
        // 2 banks, testing that the pin length and pin status is being updated
        $cases['scenario#BA204#000#402'] = [[S::BA204, S::BA204], ['000', '402'], [2, 2], json_encode($items)];

        $items = [
            [
                $this->expectedBankAccount('xxxxxxxxxxxx161010', true, 6),
            ],
            [
                $this->expectedBankAccount('xxxxxxxxxxxx161010', false, 6),
            ]
        ];
        // Long masked number and name
        $cases['scenario#BA204#700#400'] = [[S::BA204, S::BA204], ['700', '400'], [1, 1], json_encode($items)];

        return $cases;
    }

    /**
     * @dataProvider retrieve
     */
    public function testRetrieve($scenarios, $subs = ['000'], $counts = [], $json = '[]')
    {
        $id = 'bank_' . Base\Constants::ARZP;

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenarios[0], $subs[0]);

        $response = $helper->retrieve($this->expectedCallback(Requests::P2P_CUSTOMER_BA_RETRIEVE, [$id]));

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $items = json_decode($json, true);

        $this->assertCollection($response, $counts[0], $items[0]);

        $helper->setScenarioInContext($scenarios[1], $subs[1]);

        $response = $helper->retrieve($this->expectedCallback(Requests::P2P_CUSTOMER_BA_RETRIEVE, [$id]));

        $this->assertCollection($response, $counts[1], $items[1]);
    }

    public function testFetchAll()
    {
        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $bankAccounts = $helper->fetchAll();

        $this->assertCollection($bankAccounts, 1);
    }

    public function testFetch()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $bankAccount = $helper->fetch($bankAccountId);

        $this->assertSame($bankAccount[Entity::ID], $bankAccountId);
    }

    public function testInitiateSetUpiPin()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $response = $helper->initiateSetUpiPin($bankAccountId);

        $this->handleNpciClRequest(
            $response,
            'getCredential',
            $this->expectedCallback(Requests::P2P_CUSTOMER_BA_SET_UPI_PIN, [$bankAccountId]),
            [],
            function($vector)
            {
                $this->assertCount(8, $vector);
                $this->assertSame('NPCI', $vector[0]);
                $this->assertSame('<xml></xml>', $vector[1]);
                $this->assertArrayHasKey('CredAllowed', $vector[2]);
                $this->assertCount(2, $vector[3]);
                $this->assertSame(['txnId', 'deviceId', 'appId', 'mobileNumber'], array_keys($vector[4]));
                $this->assertTrue(is_string($vector[5]));
                $this->assertTrue(is_array($vector[6]) and empty($vector[6]));
                $this->assertSame('en_US', $vector[7]);
            });
    }

    public function setUpiPin()
    {
        $cases = [];

        $cases['Scenario#set#N0000'] = [Scenario::N0000, 'set'];

        $cases['Scenario#set#BA301'] = [Scenario::BA301, 'set', 'unknown'];

        $cases['Scenario#set#BA302'] = [Scenario::BA302, 'set', 'failed'];

        $cases['Scenario#set#BA303'] = [Scenario::BA303, 'set', 'failed'];

        $cases['Scenario#set#BA304'] = [Scenario::BA304, 'set', 'failed'];

        $cases['Scenario#set#BA305'] = [Scenario::BA305, 'set', 'failed'];

        $cases['Scenario#reset#N0000'] = [Scenario::N0000, 'reset'];

        $cases['Scenario#change#N0000'] = [Scenario::N0000, 'change'];

        return $cases;
    }

    /**
     * @dataProvider setUpiPin
     */
    public function testSetUpiPin($scenario, $action = 'set', $pinState = null)
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateSetUpiPin($bankAccountId, ['action' => $action]);

        $helper->setScenarioInContext($scenario);

        $function = $this->handleNpciClRequest($request, 'getCredential');

        $sdk = $function(['action' => $action]);

        $response = $helper->setUpiPin($request['callback'], ['sdk' => $sdk]);

        if ($helper->getScenarioInContext()->isSuccess() === true)
        {
            $this->assertArraySubset([
                'id'    => $bankAccountId,
                'creds' => [
                    'upipin'    => [
                        'set'   => 1,
                    ]
                ]
            ], $response);
        }

        if (empty($pinState) === false)
        {
            $bankAccount = $this->fixtures->bank_account->refresh();

            $gatewayData['upi_pin_state']   = $pinState;
            $gatewayData['id']              = $bankAccount->getId();
            $gatewayData['sharpId']         = $bankAccount->getId();

            $this->assertArraySubset($gatewayData, $bankAccount->getGatewayData());
        }
    }

    public function testClFailureForSetUpiPin()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $this->runClFailureFlow(
            function(): Base\BankAccountHelper
            {
                return $this->getBankAccountHelper();
            },
            function(Base\BankAccountHelper $helper) use ($bankAccountId)
            {
                return $helper->initiateSetUpiPin($bankAccountId);
            },
            function(Base\BankAccountHelper $helper, $request, $content)
            {
                return $helper->setUpiPin($request['callback'], $content);
            });
    }

    public function testInitiateFetchBalance()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $response = $helper->initiateFetchBalance($bankAccountId);

        $this->handleNpciClRequest(
            $response,
            'getCredential',
            $this->expectedCallback(Requests::P2P_CUSTOMER_BA_FETCH_BALANCE, [$bankAccountId]),
            [],
            function($vector)
            {
                $this->assertCount(8, $vector);
                $this->assertSame('NPCI', $vector[0]);
                $this->assertSame('<xml></xml>', $vector[1]);
                $this->assertArrayHasKey('CredAllowed', $vector[2]);
                $this->assertCount(2, $vector[3]);
                $this->assertSame(['txnId', 'deviceId', 'appId', 'mobileNumber'], array_keys($vector[4]));
                $this->assertTrue(is_string($vector[5]));
                $this->assertTrue(is_array($vector[6]) and empty($vector[6]));
                $this->assertSame('en_US', $vector[7]);
            });
    }

    public function fetchBalance()
    {
        $cases = [];

        $cases['Scenario#balance#N0000#000'] = [Scenario::N0000, '000', 102402];

        $cases['Scenario#balance#BA401#000'] = [Scenario::BA401, '000', null];

        $cases['Scenario#balance#BA402#000'] = [Scenario::BA402, '000', null];

        $cases['Scenario#balance#BA403#000'] = [Scenario::BA403, '000', null];

        $cases['Scenario#balance#BA404#000'] = [Scenario::BA404, '000', 102402];

        $cases['Scenario#balance#BA404#103'] = [Scenario::BA404, '103', 102403];

        $cases['Scenario#balance#BA404#203'] = [Scenario::BA404, '303', 107374182403];

        $cases['Scenario#balance#BA404#001'] = [Scenario::BA404, '001', 101];

        $cases['Scenario#balance#BA404#011'] = [Scenario::BA404, '011', 211];

        return $cases;
    }

    /**
     * @dataProvider fetchBalance
     */
    public function testFetchBalance($scenario, $sub, $balance)
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateFetchBalance($bankAccountId);

        $helper->setScenarioInContext($scenario, $sub);

        $function = $this->handleNpciClRequest($request, 'getCredential');

        $sdk = $function(['action' => 'balance']);

        $response = $helper->fetchBalance($request['callback'], ['sdk' => $sdk]);

        if ($helper->getScenarioInContext()->isSuccess() === true)
        {
            $this->assertSame([
                'success'   => true,
                'balance'   => $balance,
                'currency'  => 'INR',
                'id'        => $bankAccountId,
            ], $response);
        }
    }

    public function testClFailureForFetchBalance()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $this->runClFailureFlow(
            function(): Base\BankAccountHelper
            {
                return $this->getBankAccountHelper();
            },
            function(Base\BankAccountHelper $helper) use ($bankAccountId)
            {
                return $helper->initiateFetchBalance($bankAccountId);
            },
            function(Base\BankAccountHelper $helper, $request, $content)
            {
                return $helper->fetchBalance($request['callback'], $content);
            });
    }

    private function expectedBankAccount($masked = 'xxxx141010', $upi = 1, $length = 4)
    {
        return [
            'entity'                => 'bank_account',
            'masked_account_number' => $masked,
            'beneficiary_name'      => 'Sharp Customer' . (strlen($masked) === 10 ? '' : ' Longname'),
            'creds'                 => [
                'upipin'            => [
                    'set'           => boolval($upi),
                    'length'        => $length,
                ],
                'atmpin'            => [
                    'set'           => boolval($upi),
                    'length'        => $length,
                ],
            ]
        ];
    }
}
