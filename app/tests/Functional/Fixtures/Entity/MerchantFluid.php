<?php

namespace Tests\Functional\Fixtures\Entity;

use Models\Merchant\Account;

class MerchantFluid extends Base
{
    protected $merchant = null;

    protected $repo = null;

    public function getMerchant($id)
    {
        $merchant = $this->getRepo()->findOrFail($id);

        $this->setMerchant($merchant);

        return $this;
    }

    public function setMerchant($merchant)
    {
        $this->merchant = $merchant;

        return $this;
    }

    public function createEntity(array $attributes = array())
    {
        $attributes['pricing_plan_id'] = '1hDYlICobzOCYt';

        $merchant = parent::create('merchant', $attributes);

        $this->setMerchant($merchant);

        $this->fixtures->on('test')->create('balance', ['id' => $this->getId(), 'balance' => '0']);
        $this->fixtures->on('live')->create('balance', ['id' => $this->getId(), 'balance' => '0']);

        $this->fixtures->setDefaultConn();

        return $this;
    }

    public function addBalance()
    {
        $this->fixtures->on('test')->create('balance', ['id' => $this->getId(), 'balance' => '0']);
        $this->fixtures->on('live')->create('balance', ['id' => $this->getId(), 'balance' => '0']);

        $this->fixtures->setDefaultConn();

        return $this;
    }

    public function pricingPlan($planId)
    {
        $this->merchant->setPricingPlan($planId);

        $this->getRepo()->saveOrFail($merchant);

        return $this;
    }

    public function addTerminal($gateway = 'hdfc', array $attributes = array())
    {
        $defaultAttributes = array(
            'merchant_id'           => $this->getId(),
            'gateway'               => $gateway,
            'gateway_merchant_id'   => 'gateway_merchant_random',
            'gateway_terminal_id'   => 'gateway_terminal_random',
            'gateway_terminal_password' => 'abcdef',
            'card'                  => 1);

        $attributes = array_merge($defaultAttributes, $attributes);

        $terminal = $this->create('terminal', $attributes);

        $terminal->merchant()->associate($this->merchant);

        $this->merchant->terminals->add($terminal);

        return $this;
    }

    public function addKeys()
    {
        $id = $this->getId();

        $this->fixtures->on('test')->create('key', ['merchant_id' => $id, 'id' => 'AltTestAuthKey'], 'test');
        $this->fixtures->on('live')->create('key', ['merchant_id' => $id, 'id' => 'AltLiveAuthKey'], 'live');

        $this->fixtures->setDefaultConn();

        return $this;
    }

    public function activate()
    {
        $merchant->activated = 1;

        $this->getRepo()->saveOrFail($merchant);

        return $this;
    }

    public function addPaymentBanks(array $attributes = array())
    {
        $banks = \Models\Payment\Processor\NetBanking::getAllBanks();

        $defaultValues = array(
            'merchant_id' => $this->getId(),
            'banks' => $banks,
        );

        $attributes = array_merge($defaultValues, $attributes);

        $this->fixtures->create('merchant_banks', $attributes);

        return $this;
    }

    public function addBankAccount(array $attributes = array())
    {
        $name = random_alpha_string(10);

        $code = substr(strtoupper($name), 0, 4);

        $defaultValues = array(
            'merchant_id'      => $this->getId(),
            'beneficiary_name' => $name,
            'beneficiary_code' => $code,
        );

        $attributes = array_merge($defaultValues, $attributes);

        $this->fixtures->create('bank_account', $attributes);

        return $this;
    }

    protected function getRepo()
    {
        if ($this->repo !== null)
        {
            return $this->repo;
        }

        $this->repo = new \Models\Merchant\Repository;

        return $this->repo;
    }

    protected function getId()
    {
        return $this->merchant->getId();
    }

    public function get()
    {
        return $this->merchant;
    }

    public function createInstance()
    {
        return $this;
    }
}