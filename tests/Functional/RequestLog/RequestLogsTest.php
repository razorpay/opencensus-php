<?php

namespace RZP\tests\Functional\RequestLog;

use DB;
use App;
use Mail;
use Queue;
use Redis;
use Mockery;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Http\Route;
use RZP\Models\Admin;
use RZP\Models\Merchant;
use RZP\Models\Pricing\Fee;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Pricing\Fee as FeeEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Constants\Table as TableConstants;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityFetchTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class RequestLogsTest extends TestCase
{
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected $merchantId;
    protected $balanceId;

    protected $app;

    protected function setStateViaRedisKeyForEnablingRequestLogging(string $state = 'on')
    {
        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::REQUEST_LOG_STATE => $state,
            ]);
    }

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/RequestLogsTestData.php';

        parent::setUp();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->setUpMerchantForBusinessBankingLive(false, 10000);
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $this->merchantId = '10000000000000';

        $this->fixtures->on('live')->create('balance',
        [
            'id' => 'testBalance000',
            'merchant_id' => '10000000000000',
            'type' => 'primary',
            'balance' => 100000,
            'account_number' => '2224440041626905',
            'currency' => 'INR',
        ]);
        $this->balanceId = 'testBalance000';
    }

    // DO NOT Modify any create* method, override $params instead.

    protected function createContactEntityArray($params = []) // DO NOT Modify
    {
        $contact = [
            'id' => 'cont1000000000',
            'name' => 'Test Testing',
            'email' => 'test@razorpay.com',
            'contact' => '987654321',
            'type' => 'self',
            'active' => 1,
        ];
        $contact = array_merge($contact, (array) $params);
        return $contact;
    }

    protected function createVpaEntityArray($params = []) // DO NOT Modify
    {
        $vpa = [
            'id' => 'vpa10000000000',
            'entity_id' => 'cont1000000000',
            'entity_type' => 'contact',
            'username' => 'test',
            'handle' => 'upi',
            'merchant_id' => $this->merchantId,
        ];
        $vpa = array_merge($vpa, (array) $params);
        return $vpa;
    }

    protected function createBankAccountEntityArray($params = []) // DO NOT Modify
    {
        $bankAccount = [
            'id' => 'bnk10000000000',
            'beneficiary_name' => 'Test Tester',
            'entity_id' => 'cont1000000000',
            'type' => 'contact',
            'ifsc_code' => 'SBIN0007105',
            'account_number' => '111000',
            'merchant_id' => $this->merchantId,
        ];
        $bankAccount = array_merge($bankAccount, (array) $params);
        return $bankAccount;
    }

    // DO NOT Modify, Create Contact and VPA before calling this
    protected function createVpaFundAccountEntityArray($params = [])
    {
        $fundAccount = [
            'id' => 'fa100000000000',
            'merchant_id' => $this->merchantId,
            'source_type' => 'contact',
            'source_id' => 'cont1000000000',
            'account_type' => 'vpa',
            'account_id' => 'vpa10000000000',
            'active' => 1,
        ];
        $fundAccount = array_merge($fundAccount, (array) $params);
        return $fundAccount;
    }

    // Create Contact and Bank Account before calling this
    protected function createBankingFundAccountEntityArray($params = []) // DO NOT Modify
    {
        $fundAccount = [
            'id' => 'fa100000000000',
            'merchant_id' => $this->merchantId,
            'source_type' => 'contact',
            'source_id' => 'cont1000000000',
            'account_type' => 'bank_account',
            'account_id' => 'bnk10000000000',
            'active' => 1,
        ];
        $fundAccount = array_merge($fundAccount, (array) $params);
        return $fundAccount;
    }

    protected function createPayoutEntityArray($params = []) // DO NOT Modify
    {
        $payout = [
            'id' => '10000000000001',
            'merchant_id' => $this->merchantId,
            'fund_account_id' => 'fa100000000000',
            'balance_id' => $this->balanceId,
            'amount' => 100,
            'mode' => 'UPI',
            'currency' => 'INR',
            'purpose' => 'test',
        ];
        $payout = array_merge($payout, (array) $params);
        return $payout;
    }

    protected function createTransactionEntityArray($params = [])
    {
        $txn = [
            'id' => 'txn10000000000',
            'entity_id' => 'pout1000000000',
            'type' => 'payout',
            'merchant_id' => $this->merchantId,
            'amount' => 100,
            'fee' => 0,
            'debit' => 100,
            'credit' => 0,
            'currency' => 'INR',
            'balance' => 99900,
            'balance_id' => $this->balanceId,
        ];
        $txn = array_merge($txn, (array) $params);

        return $txn;
    }

    // Check if the Route is included in the list of request log routes
    protected function checkIfRouteNameIsIncluded(string $route)
    {
        if(in_array($route, Route::REQUEST_LOG_ROUTES))
        {
            return true;
        }
        return false;
    }


    // --------------------------------- Fund Accounts Tests -----------------------------------
    public function testFetchAllFundAccounts()
    {
        /*  1. Create Contact using fixtures
            2. Create VPA using fixtures
            3. Create 2 fund accounts using fixtures
            4. Fetch all Fund accounts.
            5. Check if the entity type is 'collection' in both response and log record.
        */

        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
        $this->fixtures->on('live')->create('vpa', $this->createVpaEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createVpaFundAccountEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createVpaFundAccountEntityArray(['id' => 'fa100000000001']));

        // Request to hit 'Get all Fund Accounts' API endpoint
        $request = [
            'url' => '/fund_accounts',
            'method' => 'GET',
        ];

        // Get response
        $responseContent = $this->makeRequestAndGetContent($request);

        $route = $this->app['api.route']->getCurrentRouteName();

        if(! $this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        // Fetch record from DB
        $dbContent = $this->getDbLastEntity('request_log', 'live');

        // Assert the required condition
        $this->assertEquals($responseContent['entity'], $dbContent->entity_type,
                            'RequestLogsTest: Entity Types do not match when fetching all fund accounts');
        $this->assertEquals($request['method'], $dbContent->request_method,
                            'RequestLogsTest: Request methods do not match when fetching all fund accounts');
        $this->assertEquals($route, $dbContent->route_name,
                            'RequestLogsTest: Route names do not match when fetching all fund accounts');
    }

    public function testFetchFundAccountById()
    {
        /*  1. Create Contact using fixtures
            2. Create VPA using fixtures
            3. Create 2 fund accounts using fixtures
            4. Request to fetch the second fund account by ID.
            5. Check if the entity type is 'collection' in both response and log record
                and if the entity IDs are the same in both
        */

        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
        $this->fixtures->on('live')->create('vpa', $this->createVpaEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createVpaFundAccountEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createVpaFundAccountEntityArray(['id' => 'fa100000000001']));

        // Request to fetch a fund account by ID
        $request = [
            'url' => '/fund_accounts/fa_fa100000000001',
            'method' => 'GET',
        ];

        // Get the response content
        $responseContent = $this->makeRequestAndGetContent($request);

        $route = $this->app['api.route']->getCurrentRouteName();

        if(! $this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        // Get record from DB
        $dbContent = $this->getDbLastEntity('request_log', 'live');

        // Check the required conditions
        $this->assertEquals($responseContent['entity'], $dbContent->entity_type,
                            'RequestLogsTest: Entity Types do not match when fetching one fund account');
        $this->assertEquals($responseContent['id'], $dbContent->entity_id,
                            'RequestLogsTest: Entity IDs do not match when fetching one fund account');
        $this->assertEquals($route, $dbContent->route_name,
                            'RequestLogsTest: Route names do not match when one fund account');
    }

    public function testCreateBankingFundAccount()
    {
        /*
         * 1. Create Contact and Bank Account through fixtures
         * 2. Create fund_account through route request
         * 3. Check if the response and the log record match.
         */
        $contact = $this->createContactEntityArray();
        $bnkAcc = $this->createBankAccountEntityArray();
        $this->fixtures->on('live')->create('contact', $contact);
        $this->fixtures->on('live')->create('bank_account', $bnkAcc);

        // Request to create a banking fund account
        $request = [
            'url' => '/fund_accounts',
            'method' => 'POST',
            'content' =>
            [
                'account_type' => 'bank_account',
                'contact_id' => 'cont_' . $contact['id'],
                'bank_account' =>
                [
                    'name' => $bnkAcc['beneficiary_name'],
                    'ifsc' => $bnkAcc['ifsc_code'],
                    'account_number' => $bnkAcc['account_number'],
                ],
            ],
        ];

        // Get response
        $responseContent = $this->makeRequestAndGetContent($request);

        $route = $this->app['api.route']->getCurrentRouteName();

        if(! $this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        // Get record from DB table
        $dbContent = $this->getDbLastEntity('request_log', 'live');

        // Check relevant conditions
        $this->assertEquals($responseContent['entity'], $dbContent->entity_type,
                            'RequestLogsTest: Entity Types do not match when creating Banking Fund Account');
        $this->assertEquals($responseContent['id'], $dbContent->entity_id,
                            'RequestLogsTest: Entity IDs do not match when creating Banking Fund Account');
        $this->assertEquals($route, $dbContent->route_name,
                            'RequestLogsTest: Route names do not match when creating Banking Fund Account');
    }

    public function testCreateVpaFundAccount()
    {
        /*
         * 1. Create Contact and VPA through fixtures
         * 2. Create fund_account through route request
         * 3. Check if the response and the log record match.
         */
        $contact = $this->createContactEntityArray();
        $vpa = $this->createVpaEntityArray();
        $this->fixtures->on('live')->create('vpa', $vpa);
        $this->fixtures->on('live')->create('contact', $contact);

        // Request to create a banking fund account
        $request = [
            'url' => '/fund_accounts',
            'method' => 'POST',
            'content' =>
                [
                    'account_type' => 'vpa',
                    'contact_id' => 'cont_' . $contact['id'],
                    'vpa' =>
                        [
                            'address' => $vpa['username'] . '@' . $vpa['handle'],
                        ],
                ],
        ];

        // Get response
        $responseContent = $this->makeRequestAndGetContent($request);

        $route = $this->app['api.route']->getCurrentRouteName();

        if(! $this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        // Get record from DB table
        $dbContent = $this->getDbLastEntity('request_log', 'live');

        // Check relevant conditions
        $this->assertEquals($responseContent['entity'], $dbContent->entity_type,
                            'RequestLogsTest: Entity Types do not match when creating VPA Fund Account');
        $this->assertEquals($responseContent['id'], $dbContent->entity_id,
                            'RequestLogsTest: Entity IDs do not match when creating VPA Fund Account');
        $this->assertEquals($route, $dbContent->route_name,
                            'RequestLogsTest: Route names do not match when creating VPA fund account');
    }

    public function testEditFundAccount()
    {
        /*
         * 1. Create 1 Contact and 2 Fund Accounts through fixtures (one active and one inactive)
         * 2. Edit both fund_accounts through route request
         * 3. Check if the response and the log record match.
         */
        $contact = $this->createContactEntityArray();
        $vpa = $this->createVpaEntityArray();
        $bankAcc = $this->createBankAccountEntityArray();
        $fundAcc1 = $this->createVpaFundAccountEntityArray(['account_id' => $vpa['id']]);
        $fundAcc2 = $this->createBankingFundAccountEntityArray(
            ['id' => 'fa100000000001','account_id' => $bankAcc['id'], 'active' => 0]);
        $this->fixtures->on('live')->create('vpa', $vpa);
        $this->fixtures->on('live')->create('contact', $contact);
        $this->fixtures->on('live')->create('bank_account', $bankAcc);
        $this->fixtures->on('live')->create('fund_account', $fundAcc1);
        $this->fixtures->on('live')->create('fund_account', $fundAcc2);

        // Requests section
        $requestArr = [
            [
                'url' => '/fund_accounts/' . 'fa_' . $fundAcc1['id'],
                'method' => 'PATCH',
                'content' =>
                    [
                        'active' => 0,
                    ],
            ],
            [
                'url' => '/fund_accounts/' . 'fa_' . $fundAcc2['id'],
                'method' => 'PATCH',
                'content' =>
                    [
                        'active' => 1,
                    ],
            ],
            [
                'url' => '/fund_accounts/' . 'fa_' . $fundAcc1['id'],
                'method' => 'PATCH',
                'content' =>
                    [
                        'active' => 1,
                    ],
            ],
            [
                'url' => '/fund_accounts/' . 'fa_' . $fundAcc2['id'],
                'method' => 'PATCH',
                'content' =>
                    [
                        'active' => 0,
                    ],
            ],
        ];

        foreach($requestArr as $request)
        {
            // Get response
            $responseContent = $this->makeRequestAndGetContent($request);

            $route = $this->app['api.route']->getCurrentRouteName();

            if(! $this->checkIfRouteNameIsIncluded($route))
            {
                return;
            }

            // Get record from DB table
            $dbContent = $this->getDbLastEntity('request_log', 'live');

            // Check relevant conditions
            $this->assertEquals($responseContent['entity'], $dbContent->entity_type,
                                'RequestLogsTest: Entity Types do not match when Editing Fund Account');
            $this->assertEquals($responseContent['id'], $dbContent->entity_id,
                                'RequestLogsTest: Entity IDs do not match when Editing Fund Account');
            $this->assertEquals($route, $dbContent->route_name,
                                'RequestLogsTest: Route names do not match when Editing fund account');
        }
    }

    // ------------------------- Fund Accounts Tests End -----------------------------


    // ------------------------- Contacts Tests -------------------------------

    public function testFetchAllContacts()
    {
        /*
         * 1. Create 5 contacts
         * 2. Request all contacts
         * 3. Check if entity type, request method and route name match
         */

        $defaultContactId = 'cont1000000000';

        for($x = 0; $x <= 4; $x++)
        {
            $contact = $this->createContactEntityArray(['id' => substr_replace($defaultContactId, chr(48 + $x), -1)]);
            $this->fixtures->on('live')->create('contact', $contact);
        }

        $request = array(
            'url' => '/contacts',
            'method' => 'GET',
        );

        // Get response
        $responseContent = $this->makeRequestAndGetContent($request);

        $route = $this->app['api.route']->getCurrentRouteName();

        if(! $this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        // Fetch record from DB
        $dbContent = $this->getDbLastEntity('request_log', 'live');

        // Assert the required condition
        $this->assertEquals($responseContent['entity'], $dbContent->entity_type,
                            'RequestLogsTest: Entity Types do not match when fetching all Contacts');
        $this->assertEquals($request['method'], $dbContent->request_method,
                            'RequestLogsTest: Request methods do not match when fetching all Contacts');
        $this->assertEquals($route, $dbContent->route_name,
                            'RequestLogsTest: Route names do not match when fetching all Contacts');
    }

    public function testFetchContactById()
    {
        /*
         * 1. Create 5 contacts
         * 2. Request to fetch 3rd contact (id: 'cont1000000002')
         * 3. Check if entity type, entity id and route name match
         */

        $defaultContactId = 'cont1000000000';

        for($x = 0; $x <= 4; $x++)
        {
            $contact = $this->createContactEntityArray(['id' => substr_replace($defaultContactId, chr(48 + $x), -1)]);
            $this->fixtures->on('live')->create('contact', $contact);
        }

        // Request to access contact with ID cont1000000002
        $request = [
            'url' => '/contacts/' . 'cont_' . substr_replace($defaultContactId, chr(50), -1),
            'method' => 'GET',
        ];

        // Get response
        $responseContent = $this->makeRequestAndGetContent($request);

        $route = $this->app['api.route']->getCurrentRouteName();

        if(! $this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        // Fetch record from DB
        $dbContent = $this->getDbLastEntity('request_log', 'live');

        // Assert the required condition
        $this->assertEquals($responseContent['entity'], $dbContent->entity_type,
                            'RequestLogsTest: Entity Types do not match when fetching one Contact by ID');
        $this->assertEquals($responseContent['id'], $dbContent->entity_id,
                            'RequestLogsTest: Entity IDs do not match when fetching one Contact by ID');
        $this->assertEquals($route, $dbContent->route_name,
                            'RequestLogsTest: Route names do not match when one Contact by ID');
    }

    public function testCreateContact()
    {
        /*
         * 1. Request to create a new contact
         * 2. Check if entity type, entity id and route name match
         */

        $request = [
            'url' => '/contacts',
            'method' => 'POST',
            'content' => [
                'name' => 'Test Tester',
                'email' => 'test@razorpay.com',
                'contact' => '9876543210',
                'type' => 'employee',
                'notes' => ['abc' => 'xyz']
            ],
        ];

        // Get response
        $responseContent = $this->makeRequestAndGetContent($request);

        $route = $this->app['api.route']->getCurrentRouteName();

        if(! $this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        // Fetch record from DB
        $dbContent = $this->getDbLastEntity('request_log', 'live');

        // Assert the required condition
        $this->assertEquals($responseContent['entity'], $dbContent->entity_type,
                            'RequestLogsTest: Entity Types do not match when creating a contact');
        $this->assertEquals($responseContent['id'], $dbContent->entity_id,
                            'RequestLogsTest: Entity IDs do not match when creating a contact');
        $this->assertEquals($route, $dbContent->route_name,
                            'RequestLogsTest: Route names do not match when creating a contact');
    }

    public function testEditContact()
    {
        /*
         * 1. Create Contact
         * 2. Request to edit that contact
         * 3. Check if entity type, entity id and route name match
         */

        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());

        $request = [
            'url' => '/contacts/' . 'cont_' . 'cont1000000000',
            'method' => 'PATCH',
            'content' => [
                'email' => 'test2@razorpay.com',
                'contact' => '999999999',
                'type' => 'employee',
                'notes' => ['abc' => 'xyz']
            ],
        ];

        // Get response
        $responseContent = $this->makeRequestAndGetContent($request);

        $route = $this->app['api.route']->getCurrentRouteName();

        if(! $this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        // Fetch record from DB
        $dbContent = $this->getDbLastEntity('request_log', 'live');

        // Assert the required condition
        $this->assertEquals($responseContent['entity'], $dbContent->entity_type,
                            'RequestLogsTest: Entity Types do not match when creating a contact');
        $this->assertEquals($responseContent['id'], $dbContent->entity_id,
                            'RequestLogsTest: Entity IDs do not match when creating a contact');
        $this->assertEquals($route, $dbContent->route_name,
                            'RequestLogsTest: Route names do not match when creating a contact');
    }

    // ------------- Contacts Tests End ----------------------

    // -------------- Payouts Tests -----------------------

    public function testFetchAllPayouts()
    {
        /*
         * 1. Create a fund account 5 payouts
         * 2. Request to list all payouts
         * 3. Check if entity type, request method and route name match
         */

        $this->fixtures->on('live')->create('fund_account', $this->createVpaFundAccountEntityArray());
        $defaultPayoutId = '10000000000001';

        for($x = 0; $x <= 4; $x++)
        {
            $payout = $this->createPayoutEntityArray(['id' => substr_replace($defaultPayoutId, chr(49 + $x), -1), 'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',]);
            $this->fixtures->on('live')->create('payout', $payout);
        }

        $request = [
            'url' => '/payouts?account_number=2224440041626905',
            'method' => 'GET',
        ];

        // Get response
        $responseContent = $this->makeRequestAndGetContent($request);

        // Get current route name
        $route = $this->app['api.route']->getCurrentRouteName();

        if(! $this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        // Fetch record from DB
        $dbContent = $this->getDbLastEntity('request_log', 'live');

        // Assert the required condition
        $this->assertEquals($responseContent['entity'], $dbContent->entity_type,
                            'RequestLogsTest: Entity Types do not match when fetching all Payouts');
        $this->assertEquals($request['method'], $dbContent->request_method,
                            'RequestLogsTest: Request methods do not match when fetching all Payouts');
        $this->assertEquals($route, $dbContent->route_name,
                            'RequestLogsTest: Route names do not match when fetching all Payouts');
    }

    public function testFetchPayoutById()
    {
        /*
         * 1. Create a fund account and 5 payouts
         * 2. Request to get payout with ID pout_10000000000003
         * 3. Check if entity type, entity ID and route name match
         */

        $this->fixtures->on('live')->create('fund_account', $this->createVpaFundAccountEntityArray());

        $defaultPayoutId = '10000000000001';

        for($x = 0; $x <= 4; $x++)
        {
            $payout = $this->createPayoutEntityArray(['id' => substr_replace($defaultPayoutId, chr(49 + $x), -1),'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',]);
            $this->fixtures->on('live')->create('payout', $payout);
        }

        $request = [
            'url' => '/payouts/' . 'pout_' . substr_replace($defaultPayoutId, chr(51), -1),
            'method' => 'GET',
        ];

        // Get response
        $responseContent = $this->makeRequestAndGetContent($request);

        $route = $this->app['api.route']->getCurrentRouteName();

        if(! $this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        // Fetch record from DB
        $dbContent = $this->getDbLastEntity('request_log', 'live');

        // Assert the required condition
        $this->assertEquals($responseContent['entity'], $dbContent->entity_type,
                            'RequestLogsTest: Entity Types do not match when fetching one Payout by ID');
        $this->assertEquals($responseContent['id'], $dbContent->entity_id,
                            'RequestLogsTest: Entity IDs do not match when fetching one Payout by ID');
        $this->assertEquals($route, $dbContent->route_name,
                            'RequestLogsTest: Route names do not match when fetching one Payout by ID');
    }

    public function testCreatePayoutWithCorrectRequestBody()
    {
        /*
         * 1. Create a contact, vpa and fund account
         * 2. Request to create payout
         * 3. Check if entity type, entity ID and route name match
         */

        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
        $this->fixtures->on('live')->create('vpa', $this->createVpaEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createVpaFundAccountEntityArray());

        $request = [
            'url'     => '/payouts',
            'method'  => 'POST',
            'content' => [
                'fund_account_id' => 'fa_fa100000000000',
                'amount'          => 100,
                'mode'            => 'UPI',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ];

        // Get response
        $responseContent = $this->makeRequestAndGetContent($request);

        $route = $this->app['api.route']->getCurrentRouteName();

        if(! $this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        // Fetch record from DB
        $dbContent = $this->getDbLastEntity('request_log', 'live');
        // Assert the required condition
        $this->assertEquals($responseContent['entity'], $dbContent->entity_type,
                            'RequestLogsTest: Entity Types do not match when creating a payout');
        $this->assertEquals($responseContent['id'], $dbContent->entity_id,
                            'RequestLogsTest: Entity IDs do not match when creating a payout');
        $this->assertEquals($route, $dbContent->route_name,
                            'RequestLogsTest: Route names do not match when creating a payout');
    }

    public function testCreatePayoutWithIncorrectRequestBody()
    {
        /*
         * 1. Create a contact, vpa and fund account
         * 2. Request to create payout, but send a wrong fund_account_id in request body
         * 3. Check if entity type, entity ID and route name match
         */

        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
        $this->fixtures->on('live')->create('vpa', $this->createVpaEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createVpaFundAccountEntityArray());

        $this->startTest();

        $route = $this->app['api.route']->getCurrentRouteName();

        if (!$this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        // Fetch record from DB
        $dbRecordsCount = count($this->getDbEntities('request_log', [], 'live'));

        // Assert the required condition
        $this->assertEquals(0, $dbRecordsCount,
                            'RequestLogsTest: A wrong entry was created in logs despite wrong request body in payout Create test');
    }

    public function testReversePayout()
    {
        /*
         * 1. Create a contact, vpa, fund account and payout thru fixtures
         * 2. Reverse the payout
         * 3. Check if entity type, entity ID and route name match
         */

        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
        $this->fixtures->on('live')->create('vpa', $this->createVpaEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createVpaFundAccountEntityArray());
        $this->fixtures->on('live')->create('payout', $this->createPayoutEntityArray(['status' => 'queued','pricing_rule_id'   =>      '1nvp2XPMmaRLxb',]));

        $request = [
            'url' => '/payouts/pout_' . '10000000000001' . '/cancel',
            'method' => 'POST'
        ];

        $responseContent = $this->makeRequestAndGetContent($request);

        $route = $this->app['api.route']->getCurrentRouteName();

        if(! $this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        // Fetch record from DB
        $dbContent = $this->getDbLastEntity('request_log', 'live');

        // Assert the required condition
        $this->assertEquals($responseContent['entity'], $dbContent->entity_type,
                            'RequestLogsTest: Entity Types do not match when reversing a payout');
        $this->assertEquals($responseContent['id'], $dbContent->entity_id,
                            'RequestLogsTest: Entity IDs do not match when reversing a payout');
        $this->assertEquals($route, $dbContent->route_name,
                            'RequestLogsTest: Route names do not match when reversing a payout');
    }

    public function testCreatePayoutWithOTP()
    {
        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
        $this->fixtures->on('live')->create('vpa', $this->createVpaEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createVpaFundAccountEntityArray());

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'fund_account_id' => 'fa_fa100000000000',
                'amount'          => 100,
                'mode'            => 'UPI',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund',
                'token'           => 'BUIj3m2Nx2VvVj',
                'otp'             => '0007',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ];

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $responseContent = $this->makeRequestAndGetContent($request);

        $route = $this->app['api.route']->getCurrentRouteName();

        if(! $this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        $dbContent = $this->getDbLastEntity('request_log', 'live');

        $this->assertEquals($responseContent['entity'], $dbContent->entity_type,
                            'RequestLogsTest: Entity Types do not match when reversing a payout');
        $this->assertEquals($responseContent['id'], $dbContent->entity_id,
                            'RequestLogsTest: Entity IDs do not match when reversing a payout');
        $this->assertEquals($route, $dbContent->route_name,
                            'RequestLogsTest: Route names do not match when reversing a payout');
    }

    // -------------- Payouts Tests End -----------------------

    public function testCreateLowBalanceConfig()
    {
        /* 1. Send request to create a low balance config.
         * 2. Fetch the last entity from RequestLogs table.
         * 3. Check if the results in the entity match the expected result.
         */

        $this->makeCreateLowBalanceConfigRequestAndGetContent();

        $route = $this->app['api.route']->getCurrentRouteName();

        if(! $this->checkIfRouteNameIsIncluded($route))
        {
            return;
        }

        // Fetch the last record from request_logs table
        $observedDbContent = $this->getDbLastEntity('request_log', 'live');
        $expectedDbContent = [
            'merchant_id' => '10000000000000',
            'route_name' => 'create_low_balance_config',
            'request_method' => 'POST',
        ];
        $this->assertArraySelectiveEquals($expectedDbContent, $observedDbContent->toArray());
    }

    protected function makeCreateLowBalanceConfigRequestAndGetContent()
    {
        $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'owner', 'live');
        $this->ba->proxyAuth('rzp_live_10000000000000', $user->getId());

        // Request for Low Balance Config
        $request = [
            'url'     => '/low_balance_configs',
            'method'  => 'POST',
            'content' => [
                'account_number'      => '2224440041626905',
                'threshold_amount'    => 1000,
                'notification_emails' => ['pullak.barik@razorpay.com', 'test@razorpay.com'],
                'notify_after'        => 21600 // 6hrs
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }
}
