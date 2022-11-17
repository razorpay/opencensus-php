<?php

namespace RZP\Tests\Functional\Partner\Commission\Base;

use RZP\Constants\Mode;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Constants as MerchantConstants;

class Setup
{
    use SettlementTrait;
    use DbEntityFetchTrait;

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
        $data = array_merge($this->getDefaultCreatePartnerData(), $data);

        $account = $this->fixtures->merchant->createAccount($data['id']);

        $this->fixtures->merchant->edit($account->getId(), ['partner_type' => $data['type']]);

        $data['merchant_detail']['merchant_id'] = $data['id'];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $data['merchant_detail']);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $data['merchant_detail']);

        $appData = [
            'merchant_id' => $account->getId(),
            'partner_type' => $data['type'],
        ];

        $managedApp = $this->fixtures->merchant->createDummyPartnerApp($appData);

        if (($data['type'] === MerchantConstants::AGGREGATOR) or ($data['type'] === MerchantConstants::FULLY_MANAGED))
        {
            $appData['partner_type'] = 'reseller';
            $referredApp = $this->fixtures->merchant->createDummyReferredAppForManaged($appData);

            $output['referred_app_id'] = $referredApp['id'];
        }

        $output['application_id'] = $managedApp['id'];
    }

    public function addFeature(array $data, array &$output)
    {
        foreach ($data as $featureData)
        {
            if (isset($featureData['merchant_id']) === true)
            {
                $this->fixtures->merchant->addFeatures($featureData['feature_name'], $featureData['merchant_id']);
            }
            else
            {
                $this->fixtures->merchant->addFeatures($featureData['feature_name'], $output['merchant_id']);
            }
        }
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

    public function editPlans(array $data, array & $output)
    {
        foreach ($data as $planId => $planData)
        {
            $this->fixtures->pricing->editAllPricingPlanRules($planId, $planData);
        }
    }

    public function attachSubmerchant(array $data, array & $output)
    {
        $partnerId = $data['partner_id'];
        unset($data['partner_id']);

        $appType = $data['submerchant_type'] ?? 'managed';
        unset($data['submerchant_type']);

        $applicationId = $appType === 'managed' ? $output['application_id'] : $output['referred_app_id'];

        $merchant = $this->fixtures->create('merchant:with_balance', $data);

        $accessMapArray = [
            'entity_type'     => 'application',
            'entity_id'       => $applicationId,
            'merchant_id'     => $merchant->getId(),
            'entity_owner_id' => $partnerId,
        ];

        $this->fixtures->create('merchant_access_map', $accessMapArray);

        $output['merchant_id'] = $merchant->getId();
    }

    /**
     * Attributes required ($output) - partner_id
     * Attributes required ($data) - merchant_id
     *
     * @param array $data
     * @param array $output
     */
    public function deleteSubmerchantAccessMap(array $data, array & $output)
    {
        $accessMap = $this->getDbEntity(
            'merchant_access_map',
            [
                'merchant_id'     => $output['merchant_id'],
                'entity_owner_id' => $data['partner_id'],
            ]
        );

        $this->fixtures->edit('merchant_access_map', $accessMap->getId(), ['deleted_at' => round(microtime(true))]);
    }

    public function attachPartnerAsSubmerchant(array $data, array & $output)
    {
        $partnerId = $data['partner_id'];
        unset($data['partner_id']);

        $appType = $data['submerchant_type'] ?? 'managed';
        unset($data['submerchant_type']);

        $applicationId = $appType === 'managed' ? $output['application_id'] : $output['referred_app_id'];

        $accessMapArray = [
            'entity_type'     => 'application',
            'entity_id'       => $applicationId,
            'merchant_id'     => $partnerId,
            'entity_owner_id' => $partnerId,
        ];

        $this->fixtures->create('merchant_access_map', $accessMapArray);

        $output['merchant_id'] = $partnerId;
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

    public function defineConfigForReferredApp(array $data, array & $output)
    {
        if ($data['type'] === 'partner')
        {
            $data['entity_type'] = 'application';
            $data['entity_id'] = $output['referred_app_id'];
        }
        else
        {
            $data['entity_type'] = 'merchant';
            $data['entity_id'] = $data['merchant_id'];
            $data['origin_type'] = 'application';
            $data['origin_id'] = $output['referred_app_id'];
        }

        unset($data['type']);

        $data = array_merge($this->getDefaultPartnerConfig(), $data);

        $this->fixtures->create('partner_config', $data);
    }

    public function createPayment(array $data, array & $output)
    {
        $merchantId = $output['merchant_id'] ?? $data['merchant_id'];

        // get auth context
        $auth = $data['auth'] ?? null;
        unset($data['auth']);

        $defaultAttributes = [
            'merchant_id' => $merchantId,
            'amount'      => $data['amount'],
        ];
        $data = array_merge($defaultAttributes, $data);

        $payment = $this->fixtures->create('payment:authorized', $data);

        // build entity_origin
        if (empty($auth) === false)
        {
            $defaultAttributes = $this->getEntityOriginData();
            $entityOriginAttributes['entity_id'] = $payment->getId();
            $entityOriginAttributes['origin_id'] = $output['application_id'];
            $attributes                          = array_merge($defaultAttributes, $entityOriginAttributes);

            switch ($auth)
            {
                case 'partner':
                    $this->fixtures->create('entity_origin', $attributes);
            }
        }

        $output['source_entity'] = $payment;
    }

    public function createTransfer(array $data, array & $output)
    {
        $this->fixtures->merchant->edit('10000000000000');
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $payment = $this->createPaymentEntities(1);

        $account = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $transfer = $this->fixtures->create(
            'transfer:to_account',
            [
                'account'       => $account,
                'source_id'     => $payment->getId(),
                'source_type'   => 'payment',
                'amount'        => 2500,
                'currency'      => 'INR',
                'on_hold'       => '0',
            ]);

        $output['source_entity'] = $transfer;
    }

    protected function getDefaultCreatePartnerData(): array
    {
        return [
            'id'   => 'DefaultPartner',
            'type' => 'fully_managed',
            'merchant_detail' => [
                'gstin' => '27APIPM9598J1ZW',
            ],
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
            'default_plan_id'        => '1hDYlICobzOCYt',
            'implicit_plan_id'       => null,
            'explicit_plan_id'       => null,
            'commissions_enabled'    => true,
            'explicit_should_charge' => 0,
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
