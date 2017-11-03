<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

use Config;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Credits;
use RZP\Models\Merchant\Repository;
use RZP\Models\Merchant\EsRepository;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Methods\Entity as MerchantMethodEntity;

class Merchant extends Base
{
    public function setUp()
    {
        $this->fixtures->create('merchant:nodal_account');
        $this->fixtures->create('merchant:atom_account');
        $this->fixtures->create('merchant:api_fee_account');

        $this->setUpHiemdallHierarcyForRazorpayOrg();
    }

    public function createDefaultTestMerchant()
    {
        // Default merchant to be used for tests
        $this->fixtures->create('merchant',
                                [
                                    'id'            => '10000000000000',
                                    'email'         => 'test@razorpay.com',
                                    'billing_label' => 'Test Merchant'
                                ]);

        // Merchant on whom all shared terminals are created
        $this->fixtures->create('merchant', ['id' => '1MercShareTerm']);

        //Merchant for creating shared emi terminals
        $this->fixtures->create('merchant', ['id' => '100000Razorpay']);

        $this->fixtures->on('test')->create('terminal', ['id' => '1n25f6uN5S1Z5a', 'merchant_id' => '10000000000000']);
        $this->fixtures->on('live')->create('terminal', ['id' => '1n25f6uN5S1Z5a', 'merchant_id' => '10000000000000']);
        $this->fixtures->on('test')->create('balance', ['id' => '10000000000000', 'balance' => '1000000']);
        $this->fixtures->on('live')->create('balance', ['id' => '10000000000000', 'balance' => '0']);
        $this->fixtures->on('test')->create('key', ['merchant_id' => '10000000000000', 'id' => 'TheTestAuthKey'], 'test');
        $this->fixtures->on('live')->create('key', ['merchant_id' => '10000000000000', 'id' => 'TheLiveAuthKey'], 'live');
        $this->fixtures->on('live')->create('bank_account', ['merchant_id' => '10000000000000']);

        $this->fixtures->on('test')->create('merchant:add_payment_banks', ['merchant_id' => '10000000000000']);

        $this->fixtures->on('test')->create('merchant:bank_account');

        $this->fixtures->create('merchant:schedule_task');

        $this->fixtures->merchant->enableInternational();
    }

    public function createNodalAccount()
    {
        $apiMerchant = $this->fixtures->create('merchant', ['id' => Account::NODAL_ACCOUNT]);
        $apiBalance = $this->createEntityInTestAndLive('balance', ['id' => Account::NODAL_ACCOUNT, 'balance' => '1000000']);
    }

    public function createAtomAccount()
    {
        $apiMerchant = $this->fixtures->create('merchant', ['id' => Account::ATOM_ACCOUNT]);
        $apiBalance = $this->createEntityInTestAndLive('balance', ['id' => Account::ATOM_ACCOUNT, 'balance' => '1000000']);
    }

    public function createEventAccount()
    {
        $merchant = $this->fixtures->create('merchant', [
            'id'        => '100001Razorpay',
            'name'      => 'TestMerchant',
            'email'     => 'abc.def@gmail.com',
            'website'   => 'http://goyette.net/',
            'category'  => 1100,
        ]);

        return $merchant;
    }

    public function createApiFeeAccount()
    {
        $apiMerchant = $this->fixtures->create('merchant', ['id' => Account::API_FEE_ACCOUNT]);
        $apiBalance = $this->createEntityInTestAndLive('balance', ['id' => Account::API_FEE_ACCOUNT, 'balance' => '1000000']);
    }

    public function createMarketplaceAccount($data = null)
    {
        $accountId = $data['id'] ?? '10000000000001';

        $merchant = $this->fixtures->create(
            'merchant',
            [
                'id' => $accountId,
                'parent_id' => '10000000000000',
                'pricing_plan_id' => '1hDYlICobzOCYt'
            ]);

        $balance = 0;

        if (isset($data['balance']) === true)
        {
            $balance = $data['balance'];
        }

        $this->fixtures->on('test')->create('balance', ['id' => $accountId, 'balance' => $balance]);

        $this->fixtures->on('live')->create('balance', ['id' => $accountId, 'balance' => $balance]);

        $this->fixtures->on('live')->create('bank_account', ['merchant_id' => $accountId, 'entity_id' => $accountId]);

        $this->fixtures->on('test')->create('bank_account', ['merchant_id' => $accountId, 'entity_id' => $accountId]);

        $this->fixtures->create(
            'merchant:schedule_task',
            [
                'merchant_id' => $accountId,
                'schedule'    => [
                    'interval' => 1,
                    'delay'    => 3,
                    'hour'     => 0,
                ],
            ]);

        return $merchant;
    }

    public function createWithBalanceTerminalsStandardPricing()
    {
        $merchant = $this->fixtures->create('merchant', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);
        $merchantId = $merchant->getId();

        $balance = $this->fixtures->create('balance', ['id' => $merchantId]);

        $this->fixtures->create('terminal', ['merchant_id' => $merchantId]);
        $this->fixtures->create('terminal:atom_terminal', ['merchant_id' => $merchantId]);

        return $merchant;
    }

    public function createWithKeys()
    {
        $merchant = $this->fixtures->create('merchant', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $merchantId = $merchant->getId();

        $balance = $this->fixtures->create('balance', ['id' => $merchantId]);

        $this->createAddPaymentBanks(['merchant_id' => $merchantId]);

        $this->fixtures->on('test')->create('key', ['merchant_id' => $merchantId, 'id' => 'AltTestAuthKey'], 'test');
        $this->fixtures->on('live')->create('key', ['merchant_id' => $merchantId, 'id' => 'AltLiveAuthKey'], 'live');
        $this->fixtures->setDefaultConn();

        return $merchant;
    }

    public function createWithBalance()
    {
        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant->getId();

        $balance = $this->fixtures->create('balance', ['id' => $merchantId]);

        return $merchant;
    }

    /**
     * Creates merchant and merchant details entity with given attributes.
     *
     * @param string $orgId
     * @param string $id
     * @param array  $attributes
     * @param array  $detailsAttributes
     *
     * @return MerchantEntity
     */
    public function createMerchantWithDetails(
        string $orgId,
        string $id,
        array $attributes = [],
        array $detailsAttributes = []): MerchantEntity
    {
        $attributes = array_merge(['id' => $id, 'org_id' => $orgId], $attributes);

        $detailsAttributes = array_merge(['merchant_id' => $id], $detailsAttributes);

        $merchant = $this->fixtures->create('merchant', $attributes);

        $this->fixtures->create('merchant_detail:sane', $detailsAttributes);

        return $merchant;
    }

    public function createBankAccount(array $attributes = array())
    {
        $name = random_alpha_string(10);

        $code = substr(strtoupper($name), 0, 4);

        $defaultValues = array(
            'merchant_id' => '10000000000000',
            'beneficiary_name' => $name,
        );

        $attributes = array_merge($defaultValues, $attributes);

        return $this->fixtures->create('bank_account', $attributes);
    }

    public function createAddPaymentBanks(array $attributes = array())
    {
        $defaultValues = array(
            'merchant_id'    => '10000000000000',
            'disabled_banks' => [],
            'banks'          => '[]',
        );

        $attributes = array_merge($defaultValues, $attributes);

        $this->fixtures->create('methods', $attributes);
    }

    public function createScheduleTask(array $attributes = array())
    {
        $scheduleAttributes = [];

        if (isset($attributes['schedule']) === true)
        {
            $scheduleAttributes = $attributes['schedule'];

            unset ($attributes['schedule']);
        }

        $schedule = $this->fixtures->create('schedule', $scheduleAttributes);

        $defaultValues = ['schedule_id' => $schedule->getId()];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->fixtures->create('schedule_task', $attributes);
    }

    public function activate($id = '10000000000000')
    {
        return $this->edit($id, ['activated' => 1, 'live' => 1]);
    }

    public function holdFunds($id = '10000000000000', $hold = true)
    {
        return $this->edit($id, ['hold_funds' => $hold]);
    }

    public function enableRisky($id = '10000000000000')
    {
        return $this->edit($id, ['risk_rating' => 4]);
    }

    public function disableRisky($id = '10000000000000')
    {
        return $this->edit($id, ['risk_rating' => 3]);
    }

    public function enableMethod($id = '10000000000000', $method)
    {
        return $this->fixtures->edit('methods', $id, [$method => true]);
    }

    public function disableMethod($id = '10000000000000', $method)
    {
        return $this->fixtures->edit('methods', $id, [$method => false]);
    }

    public function enableWallet($id = '10000000000000', $wallet)
    {
        return $this->fixtures->edit('methods', $id, [$wallet => true]);
    }

    public function enablePaytm($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['paytm' => true]);
    }

    public function disablePaytm($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['paytm' => false]);
    }

    public function enableCard($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['debit_card' => true, 'credit_card' => true]);
    }

    public function disableCard($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['debit_card' => false, 'credit_card' => false]);
    }

    public function disableCreditCard($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['credit_card' => false]);
    }

    public function enableDebitCard($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['debit_card' => true]);
    }

    public function enableCreditCard($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['credit_card' => true]);
    }

    public function enableNetbanking($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['netbanking' => true]);
    }

    public function disableNetbanking($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['netbanking' => false]);
    }

    public function enableEmi($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['emi' => true]);
    }

    public function disableEmi($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['emi' => false]);
    }

    public function enableMobikwik($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['mobikwik' => true]);
    }

    public function disableMobikwik($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['mobikwik' => false]);
    }

    public function editCredits($credits, $id = '10000000000000')
    {
        return $this->fixtures->edit('balance', $id, ['credits' => $credits]);
    }

    public function editFeeCredits($credits, $id = '10000000000000')
    {
        return $this->fixtures->edit('balance', $id, ['fee_credits' => $credits]);
    }

    public function editCreditsforNodalAccount($credits, $type = Credits\Type::AMOUNT)
    {
        if ($type === Credits\Type::AMOUNT)
        {
            return $this->editCredits($credits, '10NodalAccount');
        }
        else if ($type === Credits\Type::FEE)
        {
            return $this->editFeeCredits($credits, '10NodalAccount');
        }
    }

    public function enableConvenienceFeeModel($id = '10000000000000')
    {
        return $this->edit($id, ['fee_bearer' => 'customer']);
    }

    public function disableConvenienceFeeModel($id = '10000000000000')
    {
        return $this->edit($id, ['fee_bearer' => 'platform']);
    }

    public function enableInternational($id = '10000000000000')
    {
        return $this->edit($id, ['international' => '1']);
    }

    public function disableInternational($id = '10000000000000')
    {
        return $this->edit($id, ['international' => '0']);
    }

    public function addFeatures($featureNames, $id = '10000000000000')
    {
        $features = collect();

        foreach ((array) $featureNames as $featureName) {
            $attributes = [
                'name'      => $featureName,
                'entity_id' => $id
            ];
            $features->push($this->fixtures->create('feature', $attributes));
        }

        return $features;
    }

    public function editFeatures($features, $id = '10000000000000')
    {
        return $this->edit($id, ['features' => $features]);
    }

    public function editAutoRefundDelay($delay, $id = '10000000000000')
    {
        return $this->edit($id, ['auto_refund_delay' => $delay]);
    }

    public function setCategory($category, $id = '10000000000000')
    {
        return $this->edit($id, ['category' => $category]);
    }

    public function editCategory2($category, $id = '10000000000000')
    {
        return $this->edit($id, ['category2' => $category]);
    }

    public function editPricingPlanId($planId, $id = '10000000000000')
    {
        return $this->edit($id, ['pricing_plan_id' => $planId]);
    }

    public function enableTPV($id = '10000000000000')
    {
        $this->addFeatures(['tpv'], $id);

        return true;
    }

    public function disableTPV($id = '10000000000000')
    {
        //
    }

    public function disableAllMethods($id = '10000000000000')
    {
        $methodNames = MerchantMethodEntity::getAllMethodNames();

        foreach ($methodNames as $method)
        {
            $this->disableMethod($id, $method);
        }

        $this->disableInternational();
    }

    public function setLogoUrl($url_path, $id = '10000000000000')
    {
        return $this->edit($id,['logo_url' => $url_path]);
    }

    public function setHandle($handle, $id = '10000000000000')
    {
        return $this->edit($id, ['handle' => $handle]);
    }

    /**
     * Setups up a hierarchy of groups, admins and merchants under the
     * test razorpay's organization. This can be very useful in many tests.
     *
     * Ref: https://gist.github.com/jitendra-1217/0d8f74c1bf3683aad112fa7e97dc527c
     *
     */
    public function setUpHiemdallHierarcyForRazorpayOrg()
    {
        $this->createGroups();
        $this->createAdmins();
        $this->createMerchantsAndSyncToEs();
    }

    private function createGroups()
    {
        $groups = [];

        foreach (range(11, 39) as $i)
        {
            $attributes = [
                'id'     => "100000000000{$i}",
                'org_id' => Org::RZP_ORG,
            ];

            $groups[$i] = $this->fixtures->create('group', $attributes);
        }

        //
        // Assigns parents to many of the groups to create hierarchy as depicted
        // in diagram link above.
        //
        // - Groups 11 - 27 are used in fetch tests.
        // - Groups 28 - 39 are used in edit tests.
        //

        $groups[27]->parents()->sync(['10000000000026']);
        $groups[26]->parents()->sync(['10000000000020', '10000000000021']);
        $groups[25]->parents()->sync(['10000000000020']);
        $groups[24]->parents()->sync(['10000000000019']);
        $groups[23]->parents()->sync(['10000000000018']);
        $groups[22]->parents()->sync(['10000000000018']);
        $groups[21]->parents()->sync(['10000000000015']);
        $groups[20]->parents()->sync(['10000000000014', '10000000000015']);
        $groups[19]->parents()->sync(['10000000000013']);
        $groups[18]->parents()->sync(['10000000000012']);
        $groups[17]->parents()->sync(['10000000000012']);
        $groups[16]->parents()->sync(['10000000000011']);
        $groups[15]->parents()->sync(['10000000000011']);
        $groups[14]->parents()->sync(['10000000000011']);

        $groups[39]->parents()->sync(['10000000000037']);
        $groups[38]->parents()->sync(['10000000000034']);
        $groups[37]->parents()->sync(['10000000000033']);
        $groups[36]->parents()->sync(['10000000000033']);
        $groups[35]->parents()->sync(['10000000000030', '10000000000031', '10000000000032']);
        $groups[34]->parents()->sync(['10000000000030']);
        $groups[33]->parents()->sync(['10000000000029']);
        $groups[32]->parents()->sync(['10000000000028']);
        $groups[31]->parents()->sync(['10000000000028']);
        $groups[30]->parents()->sync(['10000000000028']);

        unset($groups);
    }

    private function createAdmins()
    {
        $admins = [];

        foreach (range(11, 20) as $i)
        {
            $attributes = [
                'id'     => "100000000000{$i}",
                'org_id' => Org::RZP_ORG,
            ];

            $admins[$i] = $this->fixtures->create('admin', $attributes);

            $now       = Carbon::now();
            $createdAt = $now->timestamp;
            $expiresAt = $now->addDay()->timestamp;

            $attributes = [
                'admin_id'   => "100000000000{$i}",
                'token'      => "100000000000{$i}",
                'created_at' => $createdAt,
                'expires_at' => $expiresAt,
            ];

            $this->fixtures->create('admin_token', $attributes);
        }

        //
        // Assign groups to admins. And admins if has access to G1, G2 that
        // basically means he has access to all merchants under that group
        // hierarchy.
        //
        // Admins from ids suffix 16 to 20 aren't assigned to any groups and these
        // will mostly be used in edit tests.
        //

        $admins[11]->groups()->sync(['10000000000011', '10000000000012', '10000000000013']);
        $admins[12]->groups()->sync(['10000000000018']);
        $admins[13]->groups()->sync(['10000000000020', '10000000000024']);
        $admins[14]->groups()->sync(['10000000000014', '10000000000015']);
        $admins[15]->groups()->sync(['10000000000026']);

        unset($admins);
    }

    private function createMerchantsAndSyncToEs()
    {
        //
        // - Creates a total of 10 merchants with different set of attributes
        //   so that it serves well for all the test cases.
        // - Also, assigns admins and groups to the created merchants.
        //

        //
        // - Merchants 11 - 15 are used in fetch tests
        // - Merchants 16 - 18 are used in edit tests
        //

        $now       = Carbon::now()->timestamp;
        $merchants = [];

        $merchants[11] = $this->createMerchantWithDetails(
                                    Org::RZP_ORG,
                                    '10000000000011',
                                    [
                                        'name'          => 'jitendra ojha',
                                        'activated'     => 1,
                                        'live'          => 1,
                                        'activated_at'  => $now,
                                        'email'         => 'email.ojha@test.com',
                                        'website'       => 'www.ojha.test',
                                        'billing_label' => 'Ojha Label',
                                    ]);

        $merchants[11]->groups()->sync(['10000000000027']);

        $merchants[12] = $this->createMerchantWithDetails(
                                    Org::RZP_ORG,
                                    '10000000000012',
                                    [
                                        'name'          => 'jitendra selva',
                                        'activated'     => 1,
                                        'live'          => 1,
                                        'activated_at'  => $now,
                                        'email'         => 'email.selva@test.com',
                                        'website'       => 'www.selva.test',
                                        'billing_label' => 'Selva Label',
                                    ]);

        $merchants[12]->groups()->sync(['10000000000021']);
        $merchants[12]->admins()->sync(['10000000000012']);

        $merchants[13] = $this->createMerchantWithDetails(
                                    Org::RZP_ORG,
                                    '10000000000013',
                                    [
                                        'name'        => 'jitendra amit',
                                        'archived_at' => $now,
                                    ]);

        $merchants[13]->groups()->sync(['10000000000024']);

        $merchants[14] = $this->createMerchantWithDetails(
                                    Org::RZP_ORG,
                                    '10000000000014',
                                    [
                                        'name'         => 'prashanth yv',
                                        'parent_id'    => '10000000000012',
                                        'activated'    => 1,
                                        'live'         => 1,
                                        'activated_at' => $now,
                                    ]);

        $merchants[14]->groups()->sync(['10000000000021']);

        $merchants[15] = $this->createMerchantWithDetails(
                                    Org::RZP_ORG,
                                    '10000000000015',
                                    [
                                        'name'         => 'shashank kumar',
                                        'parent_id'    => '10000000000013',
                                        'activated'    => 1,
                                        'live'         => 1,
                                        'activated_at' => $now,
                                    ]);

        $merchants[15]->groups()->sync(['10000000000024']);

        $merchants[16] = $this->createMerchantWithDetails(
                                    Org::RZP_ORG,
                                    '10000000000016',
                                    [
                                        'pricing_plan_id' => '1hDYlICobzOCYt',
                                    ]);

        $merchants[16]->retag(['First', 'Second']);

        $merchants[17] = $this->createMerchantWithDetails(Org::RZP_ORG, '10000000000017');

        $merchants[17]->groups()->sync(['10000000000038']);

        $merchants[18] = $this->createMerchantWithDetails(Org::RZP_ORG, '10000000000018');

        $merchants[18]->groups()->sync(['10000000000035', '10000000000036']);

        $merchants[19] = $this->createMerchantWithDetails(Org::RZP_ORG, '10000000000019');

        $merchants[19]->groups()->sync(['10000000000032']);

        //
        // - Create index by calling the artisan command
        // - Sync these merchants created just now via fixtures to ES.
        //
        // Also only need to do this if es_mock is false, because the index_create
        // and index commands expect ES service to be running.
        //

        $esMock = Config::get('database.es_mock');

        if ($esMock === false)
        {
            Artisan::call('rzp:index_create', ['entity' => 'merchant', 'index' => 'testing_merchant_test', '--reindex' => true]);
            Artisan::call('rzp:index_create', ['entity' => 'merchant', 'index' => 'testing_merchant_live', '--reindex' => true]);

            Artisan::call('rzp:index', ['--mode' => 'test', '--entity' => 'merchant', '--index' => 'testing_merchant_test']);
            Artisan::call('rzp:index', ['--mode' => 'live', '--entity' => 'merchant', '--index' => 'testing_merchant_live']);
        }

        unset($merchants);
    }
}
