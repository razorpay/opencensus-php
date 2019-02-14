<?php

namespace RZP\Tests\Functional\Partner\Commission\Base;

class Setup
{
    protected $fixtures;

    public function __construct($fixtures)
    {
        $this->fixtures = $fixtures;
    }

    /**
     * Attributes required - type
     * Attributes accepted - id
     *
     * @param array $data
     * @param array $output
     */
    public function createPartner(array $data, array & $output)
    {
        $data = array_merge($data, $this->getDefaultCreatePartnerData());

        $account = $this->fixtures->merchant->createAccount($data['id']);

        $this->fixtures->merchant->edit($account->getId(), ['partner_type' => $data['type']]);

        $appData = [
            'merchant_id' => $account->getId(),
        ];

        $app = $this->fixtures->merchant->createDummyPartnerApp($appData);

        $output['application_id'] = $app['id'];
    }

    public function createPlan(array $data, array & $output)
    {
        $defaultPricingPlan = $this->getDefaultPricingPlan();

        $data = array_merge($defaultPricingPlan, $data);

        $this->fixtures->create('pricing', $data);
    }

    public function createPlans(array $data, array & $output)
    {
        foreach ($data as $planData)
        {
            $this->createPlan($planData, $output);
        }
    }

    public function attachSubmerchant(array $data, array & $output)
    {
        $merchant = $this->fixtures->create('merchant:with_balance', ['pricing_plan_id' => $data['plan_id']]);

        $accessMapArray = [
            'entity_type'     => 'application',
            'entity_id'       => $output['application_id'],
            'merchant_id'     => $merchant->getId(),
            'entity_owner_id' => $data['partner_id'],
        ];

        $this->fixtures->create('merchant_access_map', $accessMapArray);

        $output['merchant_id'] = $merchant->getId();
    }

    public function defineConfig(array $data, array & $output)
    {
        if ($data['type'] === 'partner')
        {
            $data['entity_type'] = 'application';
            $data['entity_id'] = $output['application_id'];
        }
        else
        {
            $data['entity_type'] = 'merchant';
            $data['entity_id'] = $data['merchant_id'];
            $data['origin_type'] = 'application';
            $data['origin_id'] = $output['application_id'];
        }

        unset($data['type']);

        $data = array_merge($this->getDefaultPartnerConfig(), $data);

        $this->fixtures->create('partner_config', $data);
    }

    public function createPayment(array $data, array & $output)
    {
        $payment = $this->fixtures->create(
                        'payment:authorized',
                        [
                            'merchant_id' => $output['merchant_id'],
                            'amount'      => $data['amount'],
                        ]);
        unset($data['amount']);

        if (isset($data['auth']))
        {
            $attributes = $this->getEntityOriginData();

            $auth = $data['auth'];
            unset($data['auth']);

            $data['entity_id'] = $payment->getId();
            $data['origin_id'] = $output['application_id'];
            $attributes = array_merge($attributes, $data);

            switch($auth)
            {
                case 'partner':
                    $this->fixtures->create('entity_origin', $attributes);
            }
        }

        $output['source_entity'] = $payment;
    }

    protected function getDefaultCreatePartnerData(): array
    {
        return [
            'id'   => 'DefaultPartner',
            'type' => 'fully_managed',
        ];
    }

    protected function getDefaultPricingPlan(): array
    {
        return [
            'percent_rate'        => 200,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
            'payment_method_type' => 'debit',
        ];
    }

    protected function getDefaultPartnerConfig()
    {
        return [
            'default_plan_id' => '1hDYlICobzOCYt',
            'implicit_plan_id' => '',
            'commissions_enabled' => true,
        ];
    }

    protected function getEntityOriginData()
    {
        return [
            'entity_id'   => 'RandomPayment0',
            'entity_type' => 'payment',
            'origin_id'   => 'RandomApp10000',
            'origin_type' => 'application',
        ];
    }
}
