<?php

namespace RZP\Tests\Functional\Merchant;

use Config;
use RZP\Constants\Mode;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Partner\Commission\CommissionTrait as CommissionBaseTrait;

trait CommissionTrait
{
    use AttemptTrait;
    use CommissionBaseTrait;

    public function createCommissionIndex()
    {
        $esMock = Config::get('database.es_mock');

        if ($esMock === true)
        {
            $this->markTestSkipped('ES_MOCK needs to be set to true for the test to be successful');
            return;
        }

        $this->fixtures->createEsIndex('commission', Mode::TEST);
        $this->fixtures->createEsIndex('commission', Mode::LIVE);
    }

    public function createSampleCommission(
        $partnerAttributes = [],
        $appAttributes = [],
        $subMerchantAttributes = [],
        $commissionAttributes = [])
    {
        list($partner, $app) = $this->createPartnerAndApplication($partnerAttributes, $appAttributes);

        $config = $this->createConfigForPartnerApp($app->getId());

        list($subMerchant) = $this->createSubMerchant($partner, $app, $subMerchantAttributes);

        $payment = $this->createPaymentEntities(1, $subMerchant->getId());

        $defaultCommissionAttributes = [
            'source_id'         => $payment->getId(),
            'partner_id'        => $partner->getId(),
            'partner_config_id' => $config->getId(),
        ];

        $commissionAttributes = array_merge($defaultCommissionAttributes, $commissionAttributes);

        $commission = $this->fixtures->create('commission:commission_and_sync_es', $commissionAttributes);

        return [$partner, $subMerchant, $payment, $config, $commission];
    }
}
