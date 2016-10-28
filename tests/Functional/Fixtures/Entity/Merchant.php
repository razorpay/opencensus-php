<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Credits;
use RZP\Models\Merchant\Methods\Entity as MerchantMethodEntity;

class Merchant extends Base
{
    public function setUp()
    {
        $this->fixtures->create('merchant:nodal_account');
        $this->fixtures->create('merchant:atom_account');
        $this->fixtures->create('merchant:api_fee_account');
    }

    public function createDefaultTestMerchant()
    {
        // Default merchant to be used for tests
        $this->fixtures->create('merchant', ['id' => '10000000000000', 'email' => 'test@razorpay.com']);

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

    public function createApiFeeAccount()
    {
        $apiMerchant = $this->fixtures->create('merchant', ['id' => Account::API_FEE_ACCOUNT]);
        $apiBalance = $this->createEntityInTestAndLive('balance', ['id' => Account::API_FEE_ACCOUNT, 'balance' => '1000000']);
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
        $banks = \RZP\Models\Payment\Processor\Netbanking::getAllBanks();

        $defaultValues = array(
            'merchant_id' => '10000000000000',
            'banks' => $banks,
        );

        $attributes = array_merge($defaultValues, $attributes);

        $this->fixtures->create('methods', $attributes);
    }

    public function activate($id = '10000000000000')
    {
        return $this->edit($id, ['activated' => 1, 'live' => 1]);
    }

    public function holdFunds($id, $hold = true)
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
        return $this->fixtures->edit('methods', $id, ['card' => true]);
    }

    public function disableCard($id = '10000000000000')
    {
        return $this->fixtures->edit('methods', $id, ['card' => false]);
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

    public function editFeatures($features, $id = '10000000000000')
    {
        $attributes = [
            'name' => $features,
            'toggleable_id' => $id
        ];

        return $this->fixtures->create('feature', $attributes);
    }

    public function setCategory($category, $id = '10000000000000')
    {
        return $this->edit($id, ['category' => $category]);
    }

    public function editCategory2($category, $id='10000000000000')
    {
        return $this->edit($id, ['category2' => $category]);
    }

    public function editPricingPlanId($planId, $id = '10000000000000')
    {
        return $this->edit($id, ['pricing_plan_id' => $planId]);
    }

    public function enableTPV($id = '10000000000000')
    {
        return $this->edit($id, ['category' => 9999]);
    }

    public function disableTPV($id = '10000000000000')
    {
        return $this->edit($id, ['category' => 9990]);
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
}
