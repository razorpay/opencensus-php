<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use Mockery;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;


class PaymentMarketplaceTransferLAWithExistingEmailTest extends PaymentMarketplaceTransferTest
{
    protected function initializeTestSetup()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $existingMerchant = $this->fixtures->create('merchant');

        $account = $this->fixtures->create(
            'merchant:marketplace_account',
            [
                MerchantEntity::EMAIL => $existingMerchant[MerchantEntity::EMAIL]
            ]
        );

        $merchantDetailAttributes =  [
            MerchantDetailEntity::MERCHANT_ID                       => $account[MerchantEntity::ID],
            MerchantDetailEntity::CONTACT_EMAIL                     => $account[MerchantEntity::EMAIL],
            MerchantDetailEntity::ACTIVATION_STATUS                 => "activated",
            MerchantDetailEntity::BANK_DETAILS_VERIFICATION_STATUS  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $this->ba->privateAuth();

        $pgService = Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('fetchOrderPayments')
            ->with(Mockery::type('string'), Mockery::type('string'))
            ->andReturnUsing(function (string $orderId, string $merchantId)
            {
                return [];
            });
    }
}
