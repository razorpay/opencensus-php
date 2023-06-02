<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Hash;
use Config;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

use RZP\Models\Feature;
use RZP\Models\Card\Network;
use RZP\Models\Card\SubType;
use RZP\Models\Terminal\Type;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Methods;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Credits;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Payment\Processor\App as AppMethod;
use RZP\Models\Merchant\Methods\Entity as MerchantMethodEntity;

class Merchant extends Base
{
    use OAuthTrait;

    public function setUp()
    {
        $this->fixtures->create('merchant:nodal_account');
        $this->fixtures->create('merchant:atom_account');
        $this->fixtures->create('merchant:api_fee_account');

        $this->setUpHiemdallHierarcyForRazorpayOrg();
    }

    public function create(array $attributes = [])
    {
        $merchant = parent::create($attributes);

        if (isset($attributes[MerchantEntity::ORG_ID]) === true)
        {
            $org = OrgEntity::find($attributes[MerchantEntity::ORG_ID]);
        }
        else
        {
            $org = OrgEntity::find('100000razorpay');
        }

        $merchant->org()->associate($org);

        $merchant->saveOrFail();

        return $merchant;
    }

    public function createDefaultTestMerchant()
    {
        // Default merchant to be used for tests
        $this->fixtures->create('merchant',
                                [
                                    'id'                    => '10000000000000',
                                    'email'                 => 'test@razorpay.com',
                                    'billing_label'         => 'Test Merchant',
                                    'activated_at'          => time(),
                                    'category'              => '5399',
                                    'product_international' => '1111000000'
                                ]);

        //
        // TODO (probably never): A merchant_detail record should have been created for every merchant record.
        // Since that wasn't done in the beginning, testcases are creating the record themselves, and it's probably
        // too late to address this here.
        //
        // If the below line is uncommented, those 100s of testcases that create the merchant_detail fixture for the
        // '10000000000000' merchant id will fail with `Duplicate entry '10000000000000' for key 'merchant_details.PRIMARY'`
        // errors
        //
        //$this->fixtures->create('merchant_detail:sane', ['merchant_id' => '10000000000000', 'activation_status' => 'activated', 'business_type' => '2']);

        // Merchant on whom all shared terminals are created
        $this->fixtures->create('merchant', ['id' => '1MercShareTerm']);

        //Merchant for creating shared emi terminals
        $this->fixtures->create('merchant', ['id' => '100000Razorpay', 'pricing_plan_id' => '1hDYlICobzOCYt',]);

        $this->fixtures->on('test')->create('terminal', ['id' => '1n25f6uN5S1Z5a', 'merchant_id' => '10000000000000']);
        $this->fixtures->on('live')->create('terminal', ['id' => '1n25f6uN5S1Z5a', 'merchant_id' => '10000000000000']);
        $this->fixtures->on('test')->create('terminal',
                                            [
                                                'id'                   => 'BANKACC3DSN3DT',
                                                'merchant_id'          => '10000000000000',
                                                'gateway'              => Gateway::BT_YESBANK,
                                                'gateway_merchant_id'  => '222444',
                                                'gateway_merchant_id2' => '00',
                                                'card'                 => 0,
                                                'recurring'            => 0,
                                                'gateway_acquirer'     => null,
                                                'type'                 => [
                                                    Type::NON_RECURRING   => '1',
                                                    Type::NUMERIC_ACCOUNT => '1',
                                                    'business_banking'    => '1',
                                                ],
                                                'bank_transfer'        => '1',
                                            ]);
        $this->fixtures->on('live')->create('terminal',
                                            [
                                                'id'                   => 'BANKACC3DSN3DT',
                                                'merchant_id'          => '10000000000000',
                                                'gateway'              => Gateway::BT_YESBANK,
                                                'gateway_merchant_id'  => '222444',
                                                'gateway_merchant_id2' => '00',
                                                'card'                 => 0,
                                                'recurring'            => 0,
                                                'gateway_acquirer'     => null,
                                                'type'                 => [
                                                    Type::NON_RECURRING   => '1',
                                                    Type::NUMERIC_ACCOUNT => '1',
                                                    'business_banking'    => '1',
                                                ],
                                                'bank_transfer'        => '1',
                                            ]);
        $this->fixtures->on('test')->create('terminal',
                                            [
                                                'id'                   => 'BANKACC3DSN3DZ',
                                                'merchant_id'          => '10000000000000',
                                                'gateway'              => Gateway::BT_YESBANK,
                                                'gateway_merchant_id'  => '232323',
                                                'gateway_merchant_id2' => '00',
                                                'card'                 => 0,
                                                'recurring'            => 0,
                                                'gateway_acquirer'     => null,
                                                'type'                 => [
                                                    Type::NON_RECURRING   => '1',
                                                    Type::NUMERIC_ACCOUNT => '1',
                                                    'business_banking'    => '1',
                                                ],
                                                'bank_transfer'        => '1',
                                            ]);
        $this->fixtures->on('live')->create('terminal',
                                            [
                                                'id'                   => 'BANKACC3DSN3DZ',
                                                'merchant_id'          => '10000000000000',
                                                'gateway'              => Gateway::BT_YESBANK,
                                                'gateway_merchant_id'  => '232323',
                                                'gateway_merchant_id2' => '00',
                                                'card'                 => 0,
                                                'recurring'            => 0,
                                                'gateway_acquirer'     => null,
                                                'type'                 => [
                                                    Type::NON_RECURRING   => '1',
                                                    Type::NUMERIC_ACCOUNT => '1',
                                                    'business_banking'    => '1',
                                                ],
                                                'bank_transfer'        => '1',
                                            ]);

        $this->fixtures->on('test')->create('balance', ['id' => '10000000000000', 'balance' => '1000000', 'merchant_id' => '10000000000000']);
        $this->fixtures->on('live')->create('balance', ['id' => '10000000000000', 'balance' => '0', 'merchant_id' => '10000000000000']);
        $this->fixtures->on('test')->create('balance', ['id' => '100000Balance1', 'balance' => '500', 'merchant_id' => '100000Razorpay']);
        $this->fixtures->on('live')->create('balance', ['id' => '100000Balance1', 'balance' => '500', 'merchant_id' => '100000Razorpay']);
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
        $apiBalance = $this->createEntityInTestAndLive('balance', ['id' => Account::NODAL_ACCOUNT, 'balance' => '1000000', 'merchant_id' => Account::NODAL_ACCOUNT]);
    }

    public function createAtomAccount()
    {
        $apiMerchant = $this->fixtures->create('merchant', ['id' => Account::ATOM_ACCOUNT]);
        $apiBalance = $this->createEntityInTestAndLive('balance', ['id' => Account::ATOM_ACCOUNT, 'balance' => '1000000', 'merchant_id' => Account::ATOM_ACCOUNT]);
    }

    public function createAccount($merchantId, $addKeys = true, $countryCode = 'IN')
    {
        $apiMerchant = $this->fixtures->create('merchant', ['id' => $merchantId, 'country_code' => $countryCode]);
        $apiBalance = $this->createEntityInTestAndLive('balance', ['id' => $merchantId, 'balance' => '1000000', 'merchant_id' => $merchantId]);

        $this->fixtures->on('test')->create('terminal', ['id' => $merchantId, 'merchant_id' => $merchantId]);
        $this->fixtures->on('live')->create('terminal', ['id' => $merchantId, 'merchant_id' => $merchantId]);

        if ($addKeys === true) {
            $this->fixtures->on('test')->create('key', ['merchant_id' => $merchantId, 'id' => $merchantId], 'test');
            $this->fixtures->on('live')->create('key', ['merchant_id' => $merchantId, 'id' => $merchantId], 'live');
        }

        $this->fixtures->on('test')->create('bank_account', ['merchant_id' => $merchantId, 'entity_id' => $merchantId]);

        $this->fixtures->on('test')->create('merchant:add_payment_banks', ['merchant_id' => $merchantId]);

        $this->fixtures->create('merchant:schedule_task', ['merchant_id' => $merchantId]);

        $this->fixtures->merchant->enableInternational($merchantId);

        return $apiMerchant;
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
        $apiBalance = $this->createEntityInTestAndLive('balance', ['id' => Account::API_FEE_ACCOUNT, 'balance' => '1000000', 'merchant_id' => Account::API_FEE_ACCOUNT]);
    }

    public function createMarketplaceAccount($data = null)
    {
        $accountId = $data['id'] ?? '10000000000001';

        $parentId = $data['parent_id'] ?? '10000000000000';

        $merchantData = [
            MerchantEntity::ID                => $accountId,
            MerchantEntity::PARENT_ID         => $parentId,
            MerchantEntity::PRICING_PLAN_ID   => '1hDYlICobzOCYt',
            MerchantEntity::HOLD_FUNDS_REASON => $data[MerchantEntity::HOLD_FUNDS_REASON] ?? null,
            MerchantEntity::SUSPENDED_AT      => $data[MerchantEntity::SUSPENDED_AT] ?? null,
            MerchantEntity::LIVE              => $data[MerchantEntity::LIVE] ?? true,
            MerchantEntity::HOLD_FUNDS        => $data[MerchantEntity::HOLD_FUNDS] ?? false,
        ];

        if(array_key_exists(MerchantEntity::EMAIL, $data))
        {
            $merchantData[MerchantEntity::EMAIL] = $data[MerchantEntity::EMAIL];
        }

        $merchant = $this->fixtures->create('merchant', $merchantData);

        $balance = 0;

        if (isset($data['balance']) === true)
        {
            $balance = $data['balance'];
        }

        $this->fixtures->on('test')->create('balance', ['id' => $accountId, 'balance' => $balance, 'merchant_id' => $accountId,]);

        $this->fixtures->on('live')->create('balance', ['id' => $accountId, 'balance' => $balance, 'merchant_id' => $accountId]);

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

        $balance = $this->fixtures->create('balance', ['id' => $merchantId, 'merchant_id' => $merchantId]);

        $this->fixtures->create('terminal', ['merchant_id' => $merchantId]);
        $this->fixtures->create('terminal:atom_terminal', ['merchant_id' => $merchantId]);

        return $merchant;
    }

    public function createWithKeys($attributes)
    {
        $merchant = $this->fixtures->create(
            'merchant',
            array_merge(['pricing_plan_id' => '1hDYlICobzOCYt'], $attributes));

        $merchantId = $merchant->getId();

        $balance = $this->fixtures->create('balance', ['id' => $merchantId, 'merchant_id' => $merchantId]);

        $this->createAddPaymentBanks(['merchant_id' => $merchantId]);

        $this->fixtures->on('test')->create('key', ['merchant_id' => $merchantId, 'id' => 'AltTestAuthKey'], 'test');
        $this->fixtures->on('live')->create('key', ['merchant_id' => $merchantId, 'id' => 'AltLiveAuthKey'], 'live');
        $this->fixtures->setDefaultConn();

        return $merchant;
    }

    public function createWithBalance(array $attributes = [])
    {
        $merchant = $this->fixtures->create('merchant', $attributes);

        $merchantId = $merchant->getId();

        $balance = $this->fixtures->create('balance', ['id' => $merchantId, 'merchant_id' => $merchantId]);

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
        array $detailsAttributes = [],
        array $businessDetailsAttributes = []): MerchantEntity
    {
        $attributes = array_merge(['id' => $id, 'org_id' => $orgId], $attributes);

        $detailsAttributes = array_merge(['merchant_id' => $id], $detailsAttributes);

        $businessDetailsAttributes = array_merge(['merchant_id' => $id], $businessDetailsAttributes);

        $merchant = $this->fixtures->create('merchant', $attributes);

        $org = OrgEntity::find($orgId);

        $merchant->org()->associate($org);

        $merchant->saveOrFail();

        $this->fixtures->create('merchant_detail:sane', $detailsAttributes);

        $this->fixtures->create('merchant_business_detail', $businessDetailsAttributes);

        return $merchant;
    }

    public function createBankAccount(array $attributes = array())
    {
        $name = random_string_special_chars(10);

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
        // TODO: To check for better ways of solving this issue
        $mode = Config::get('database.default');

        $scheduleAttributes = [];

        if (isset($attributes['schedule']) === true)
        {
            $scheduleAttributes = $attributes['schedule'];

            unset ($attributes['schedule']);
        }

        $schedule = $this->fixtures->on($mode)->create('schedule', $scheduleAttributes);

        $defaultValues = ['schedule_id' => $schedule->getId()];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->fixtures->on($mode)->create('schedule_task', $attributes);
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

    public function enableCardNetwork($id = '10000000000000', $network)
    {
        $cardNetworks = Network::getEnabledCardNetworks(Network::DEFAULT_CARD_NETWORKS);

        $cardNetworks[strtoupper($network)] = 1;

        $hexValue = Network::getHexValue($cardNetworks);

        return $this->fixtures->edit('methods', $id, ['card_networks' => $hexValue]);
    }

    public function enableApp($id = '10000000000000', $app)
    {
        $apps = AppMethod::getEnabledApps(AppMethod::DEFAULT_APPS);

        $apps[$app] = 1;

        $hexValue = AppMethod::getHexValue($apps);

        return $this->fixtures->edit('methods', $id, ['apps' => $hexValue]);
    }

    public function enableCardSubType($id = '10000000000000', $subtype)
    {
        $subTypes = SubType::getEnabledCardSubTypes(1);

        $subTypes[$subtype] = 1;

        return $this->fixtures->edit('methods', $id, ['card_subtype' => $subTypes]);
    }

    public function disableCardSubType($id = '10000000000000', $subtype)
    {
        $subTypes = SubType::getEnabledCardSubTypes(1);

        $subTypes[$subtype] = 0;

        return $this->fixtures->edit('methods', $id, ['card_subtype' => $subTypes]);
    }

    public function enableCardNetworks($id = '10000000000000', $networks)
    {
        $cardNetworks = Network::getEnabledCardNetworks(Network::DEFAULT_CARD_NETWORKS);

        foreach ($networks as $network)
        {
            $cardNetworks[strtoupper($network)] = 1;
        }

        $hexValue = Network::getHexValue($cardNetworks);

        return $this->fixtures->edit('methods', $id, ['card_networks' => $hexValue]);
    }

    public function disableCardNetworks($id = '10000000000000', $networks)
    {
        $cardNetworks = Network::getEnabledCardNetworks(Network::DEFAULT_CARD_NETWORKS);

        foreach ($networks as $network)
        {
            $cardNetworks[strtoupper($network)] = 0;
        }

        $hexValue = Network::getHexValue($cardNetworks);

        return $this->fixtures->edit('methods', $id, ['card_networks' => $hexValue]);
    }

    public function disableMethod($id = '10000000000000', $method)
    {
        if ($method === Methods\Entity::EMI)
        {
            return $this->fixtures->edit('methods', $id, [$method => [Methods\EmiType::CREDIT => '0', Methods\EmiType::DEBIT => '0']]);
        }

        return $this->fixtures->edit('methods', $id, [$method => false]);
    }

    public function enableWallet($id = '10000000000000', $wallet)
    {
        return $this->fixtures->edit('methods', $id, [$wallet => true]);
    }

    public function enableAdditionalWallets(array $wallet, $id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['additional_wallets' => $wallet]);
    }

    public function enablePaytm($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['paytm' => true]);
    }

    public function enableIntlBankTransfer($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['addon_methods'=> ['intl_bank_transfer' => ['ach'=>1,'swift'=>1]]]);
    }

    public function disablePaytm($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['paytm' => false]);
    }

    public function enableUpi($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['upi' => true]);
    }

    public function disableUpi($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['upi' => false]);
    }

    public function enableUpiIntent($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['upi_type' => [
            'intent'=> true
        ]]);
    }

    public function enableUpiCollect($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['upi_type' => [
            'collect'=> true
        ]]);
    }

    public function disableUpiIntent($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['upi_type' => [
            'intent'=> false
        ]]);
    }

    public function disableUpiCollect($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['upi_type' => [
            'collect'=> false
        ]]);
    }

    public function enableCard($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['debit_card' => true, 'credit_card' => true]);
    }

    public function disableCard($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['debit_card' => false, 'credit_card' => false, 'prepaid_card' => false]);
    }

    public function disableCreditCard($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['credit_card' => false]);
    }

    public function enableDebitCard($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['debit_card' => true]);
    }

    public function disableDebitCard($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['debit_card' => false]);
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
    public function enableFpx($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['fpx' => true]);
    }

    public function disableFpx($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['fpx' => false]);
    }

    public function enablePrepaidCard($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['prepaid_card' => true]);
    }

    public function disablePrepaidCard($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['prepaid_card' => false]);
    }

    public function enableEmi($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['emi' => [Methods\EmiType::CREDIT => '1', Methods\EmiType::DEBIT => '1']]);
    }

    public function enableDebitEmiProviders($id = '10000000000000', $providers = ['HDFC' => 1])
    {
        return $this->fixtures->edit('methods', $id, ['debit_emi_providers' => $providers]);
    }

    public function enableCreditEmiProviders($providers ,$id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['addon_methods' => ['credit_emi' => $providers]]);
    }

    public function enablePaylaterProviders($providers, $id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['addon_methods' => ['paylater' => $providers]]);
    }

    public function enableCardlessEmiProviders($providers, $id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['addon_methods' => ['cardless_emi' => $providers]]);
    }

    public function disableDebitEmiProviders($id = '10000000000000', $providers = ['HDFC' => 0])
    {
        return $this->fixtures->edit('methods', $id, ['debit_emi_providers' => $providers]);
    }

    public function enableEmiCredit($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['emi' => [Methods\EmiType::CREDIT => '1']]);
    }

    public function enableEmiDebit($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['emi' => [Methods\EmiType::DEBIT => '1']]);
    }

    public function disableEmi($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['emi' => []]);
    }

    public function enableMobikwik($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['mobikwik' => true]);
    }

    public function disableMobikwik($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['mobikwik' => false]);
    }

    public function enableOffline($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['offline' => true]);
    }

    public function disableOffline($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['offline' => false]);
    }

    public function enableEmandate($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['emandate' => true]);
    }

    public function disableEmandate($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['emandate' => false]);
    }

    public function enableNach($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['nach' => true]);
    }

    public function disableNach($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['nach' => false]);
    }

    public function enableCardlessEmi($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['cardless_emi' => true]);
    }

    public function disableCardlessEmi($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['cardless_emi' => false]);
    }

    public function enableCred($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['cred' => true]);
    }

    public function disableTwid($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['twid' => false]);
    }

    public function enableTwid($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['twid' => true]);
    }

    public function disableCred($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['cred' => false]);
    }

    public function enablePayLater($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['paylater' => true]);
    }

    public function disablePayLater($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['paylater' => false]);
    }

    public function enablePaypal($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['paypal' => true]);
    }

    public function disablePaypal($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['paypal' => false]);
    }

    public function setDisabledBanks($id = '10000000000000', array $disabledBanks)
    {
        return $this->fixtures->edit('methods', $id, ['disabled_banks' => $disabledBanks]);
    }

    public function enableCoD($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['cod' => true]);
    }

    public function disableCoD($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['cod' => false]);
    }

    public function disableInApp($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['addon_methods' => ['upi' => ['in_app' => false]]]);
    }

    public function createBalanceOfBankingType(
        int $balance = 0,
        string $merchantId = '10000000000000',
        string $accountType = AccountType::SHARED,
        $channel = Channel::YESBANK)
    {
        return $this->fixtures->create(
            'balance',
            [
                'type'             => 'banking',
                'merchant_id'      => $merchantId,
                'balance'          => $balance,
                'account_type'     => $accountType,
                'channel'          => $channel,
            ]);
    }

    public function editBalance(int $amount, string $id = '10000000000000')
    {
        return $this->fixtures->edit('balance', $id, ['balance' => $amount]);
    }

    public function editCredits($credits, $id = '10000000000000')
    {
        return $this->fixtures->edit('balance', $id, ['credits' => $credits]);
    }

    public function editFeeCredits($credits, $id = '10000000000000')
    {
        return $this->fixtures->edit('balance', $id, ['fee_credits' => $credits]);
    }

    public function editRefundCredits($credits, $id = '10000000000000')
    {
        return $this->fixtures->edit('balance', $id, ['refund_credits' => $credits]);
    }

    public function editFeeCreditsThreshold($credits, $id = '10000000000000')
    {
        return $this->edit($id, ['fee_credits_threshold' => $credits]);
    }

    public function editAmountCreditsThreshold($credits, $id = '10000000000000')
    {
        return $this->edit($id, ['amount_credits_threshold' => $credits]);
    }

    public function editRefundCreditsThreshold($credits, $id = '10000000000000')
    {
        return $this->edit($id, ['refund_credits_threshold' => $credits]);
    }

    public function editBalanceThreshold($balance, $id = '10000000000000')
    {
        return $this->edit($id, ['balance_threshold' => $balance]);
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

    public function enableDynamicFeeModel($id = '10000000000000')
    {
        return $this->edit($id, ['fee_bearer' => 'dynamic']);
    }

    public function disableDynamicFeeModel($id = '10000000000000')
    {
        return $this->edit($id, ['fee_bearer' => 'platform']);
    }

    public function editWhitelistedIpsLive($id = '10000000000000', $ips = [])
    {
        return $this->edit($id, ['whitelisted_ips_live' => $ips]);
    }

    public function editWhitelistedIpsTest($id = '10000000000000', $ips = [])
    {
        return $this->edit($id, ['whitelisted_ips_test' => $ips]);
    }

    public function enableInternational($id = '10000000000000')
    {
        return $this->edit($id, ['international' => '1']);
    }

    public function disableInternational($id = '10000000000000')
    {
        return $this->edit($id, ['international' => '0']);
    }

    public function setCountry($country, $id = '10000000000000')
    {
        return $this->edit($id, ['country_code' => $country]);
    }

    public function markPartner($type = 'fully_managed', $id = '10000000000000')
    {
        return $this->edit($id, ['partner_type' => $type]);
    }

    public function addFeatures($featureNames, $id = '10000000000000', $entity_type = Feature\Constants::MERCHANT)
    {
        $features = collect();

        foreach ((array) $featureNames as $featureName) {
            $attributes = [
                'name'      => $featureName,
                'entity_id' => $id,
                'entity_type' => $entity_type
            ];
            $features->push($this->fixtures->create('feature', $attributes));
        }

        return $features;
    }

    public function removeFeatures(array $featureNames, string $id = '10000000000000')
    {
        Feature\Entity::where(Feature\Entity::ENTITY_ID, $id)
                      ->where(Feature\Entity::ENTITY_TYPE, 'merchant')
                      ->where(Feature\Entity::NAME, $featureNames)
                      ->delete();
    }

    public function isFeatureEnabled(array $featureNames, string $id = '10000000000000')
    {
        $features = Feature\Entity::where(Feature\Entity::ENTITY_ID, $id)
            ->where(Feature\Entity::ENTITY_TYPE, 'merchant')
            ->where(Feature\Entity::NAME, $featureNames)
            ->get();

        return sizeof($features) > 0;
    }

    public function addTags(array $tagNames, string $merchantId = '10000000000000')
    {
        $merchant = MerchantEntity::where(MerchantEntity::ID, $merchantId)
                                        ->first();

        $tagInputData = [
            'tags' => $tagNames,
        ];

        (new MerchantCore())->addTags($merchantId, $tagInputData, false);

        // This works without needing to reload from db somehow.
        $tags = $merchant->tagNames();

        // Doing this because it returns tag name with first char as capital always (not sure why).
        foreach ($tags as $key => $tag)
        {
            $tags[$key] = strtolower($tag);
        }

        return $tags;
    }

    public function reloadTags(string $merchantId = '10000000000000')
    {
        // Doing this because you need to fetch the entity from db to get the updated tags, ->reload() on entity also
        // doesn't work (not known why).
        $merchant = MerchantEntity::where(MerchantEntity::ID, $merchantId)
                                        ->first();

        $tags = $merchant->tagNames();

        // Doing this because it returns tag name with first char as capital always (not sure why).
        foreach ($tags as $key => $tag)
        {
            $tags[$key] = strtolower($tag);
        }

        return $tags;
    }

    public function addDccPaymentConfig($dccMarkupPercent, string $id = '10000000000000')
    {
        $attributes = [
            'merchant_id'   => $id,
            'type'          => 'dcc',
            'name'          => 'dcc',
            'is_default'    => '0',
            'config'     => '{
                "dcc_markup_percentage": '.$dccMarkupPercent.'
            }'
        ];
        $this->fixtures->create('config', $attributes);
    }

    public function addDccRecurringPaymentConfig($dccRecurringMarkupPercent, string $id = '10000000000000')
    {
        $attributes = [
            'merchant_id'   => $id,
            'type'          => 'dcc_recurring',
            'name'          => 'dcc_recurring',
            'is_default'    => '0',
            'config'     => '{
                "dcc_recurring_markup_percentage": '.$dccRecurringMarkupPercent.'
            }'
        ];
        $this->fixtures->create('config', $attributes);
    }

    public function addMccMarkdownPaymentConfig($mccMarkdownPercent, string $id = '10000000000000',$config=[])
    {
        $attributes = [
            'merchant_id'   => $id,
            'type'          => 'mcc_markdown',
            'name'          => 'mcc_markdown',
            'is_default'    => '0',
            'config'     => '{
                "mcc_markdown_percentage": '.$mccMarkdownPercent.'
            }'
        ];
        if(empty($config) === false) {
            $existingConfig = json_decode($attributes['config'],true);
            $config = array_merge($existingConfig,$config);
            $attributes['config'] = json_encode($config);
        }
        $this->fixtures->create('config', $attributes);
    }

    public function editAutoRefundDelay($delay, $id = '10000000000000')
    {
        return $this->edit($id, ['auto_refund_delay' => $delay]);
    }

    public function editDefaultRefundSpeed($defaultRefundSpeed, $id = '10000000000000')
    {
        return $this->edit($id, ['default_refund_speed' => $defaultRefundSpeed]);
    }

    public function editLateAuthAutoCapture($autoCapture, $id = '10000000000000')
    {
        return $this->edit($id, ['auto_capture_late_auth' => $autoCapture]);
    }

    public function setCategory($category, $id = '10000000000000')
    {
        return $this->edit($id, ['category' => $category]);
    }

    public function editCategory2($category, $id = '10000000000000')
    {
        return $this->edit($id, ['category2' => $category]);
    }

    public function setHasKeyAccess(bool $hasKeyAccess, string $id = '10000000000000')
    {
        return $this->edit($id, ['has_key_access' => $hasKeyAccess]);
    }

    public function setRestricted(bool $restricted, string $id = '10000000000000')
    {
        return $this->edit($id, ['restricted' => $restricted]);
    }

    public function editPricingPlanId($planId, $id = '10000000000000')
    {
        return $this->edit($id, ['pricing_plan_id' => $planId]);
    }

    public function editCreatedAt($createdAt, $id = '10000000000000')
    {
        return $this->edit($id, ['created_at' => $createdAt]);
    }

    public function enableMagic($id = '10000000000000')
    {
        $this->addFeatures(['magic'], $id);

        return true;
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

    public function setFeeBearer($feebearer, $id = '10000000000000')
    {
        return $this->edit($id, ['fee_bearer' => $feebearer]);
    }

    public function setFeeModel($feeModel, $id = '10000000000000')
    {
        return $this->edit($id, ['fee_model' => $feeModel]);
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

    public function createDummyPartnerApp(array $attributes = [], $createMerchantApplication = true)
    {
        $defaults = [
            'id'          => '8ckeirnw84ifke',
            'merchant_id' => '10000000000000',
            'name'        => 'Internal',
            'website'     => 'https://www.razorpay.com',
            'logo_url'    => '/logo/app_logo.png',
            'category'    => null,
            'type'        => 'partner',
        ];

        $attributes = array_merge($defaults, $attributes);

        return $this->createOAuthApplication($attributes, $createMerchantApplication);
    }

    public function createDummyCurlecPartnerApp(array $attributes = [], $createMerchantApplication = true)
    {
        $defaults = [
            'id'          => '8ckeirnw84ifke',
            'merchant_id' => '10000121212121',
            'name'        => 'Internal',
            'website'     => 'https://www.curlec.com',
            'logo_url'    => '/logo/app_logo.png',
            'category'    => null,
            'type'        => 'partner',
        ];

        $attributes = array_merge($defaults, $attributes);

        return $this->createOAuthApplication($attributes, $createMerchantApplication);
    }

    public function createDummyReferredAppForManaged(array $attributes = [], $createMerchantApplication = true)
    {
        $defaults = [
            'id'          => '8ckeirnw84ifkf',
            'merchant_id' => '10000000000000',
            'name'        => MerchantEntity::REFERRED_APPLICATION,
            'website'     => 'https://www.razorpay.com',
            'logo_url'    => '/logo/app_logo.png',
            'category'    => null,
            'type'        => 'partner',
        ];

        $attributes = array_merge($defaults, $attributes);

        return $this->createOAuthApplication($attributes, $createMerchantApplication);
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
                'token'      => Hash::make("100000000000{$i}"),
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
            ],
            [
                'activation_status' => 'activated',
                'business_type'     => '2'
            ],
            [
                'miq_sharing_date' => strtotime('yesterday midnight'. ' '. 'Asia/Kolkata'),
                'testing_credentials_date' => strtotime('yesterday midnight'. ' '. 'Asia/Kolkata'),
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
            ],
            [
                'activation_status' => 'activated',
                'business_type'     => '11'
            ]);

        $merchants[12]->groups()->sync(['10000000000021']);
        $merchants[12]->admins()->sync(['10000000000012']);

        $merchants[13] = $this->createMerchantWithDetails(
            Org::RZP_ORG,
            '10000000000013',
            [
                'name'        => 'jitendra amit',
                'archived_at' => $now,
            ],
            [
                'archived_at'   => $now,
                'business_type' => '1'
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
            ],
            [
                'business_type' => '1'
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
            Artisan::call(
                'rzp:index_create',
                [
                    'mode'         => 'test',
                    'entity'       => 'merchant',
                    'index_prefix' => env('ES_ENTITY_TYPE_PREFIX'),
                    'type_prefix'  => env('ES_ENTITY_TYPE_PREFIX'),
                    '--reindex'    => true,
                ]);
            Artisan::call(
                'rzp:index_create',
                [
                    'mode'         => 'live',
                    'entity'       => 'merchant',
                    'index_prefix' => env('ES_ENTITY_TYPE_PREFIX'),
                    'type_prefix'  => env('ES_ENTITY_TYPE_PREFIX'),
                    '--reindex'    => true,
                ]);

            Artisan::call('rzp:index', ['mode' => 'test', 'entity' => 'merchant']);
            Artisan::call('rzp:index', ['mode' => 'live', 'entity' => 'merchant']);
        }

        unset($merchants);
    }
}
