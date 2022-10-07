<?php

namespace Unit\Models\Offers;

use RZP\Models\Offer\Checker as Checker;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Merchant\Account;

class CoreTest extends TestCase
{
    use PaymentTrait;

   public function testCheckOfferApplicabilityDuringPaymentUsingSavedCard()
    {

        $this->mockCardVaultWithCryptogram();

        $offer1 = $this->fixtures->create('offer', [
            'iins' => ['461786'],
            'block' => false,
            'type' => 'instant',
        ]);

        $offer2 = $this->fixtures->create('offer', [
            'iins' => ['403776','400782'],
            'block' => false,
            'type' => 'instant',
        ]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        // for this card, actual iin = 400782 and token iin = 404464916
        $savedCard = $this->fixtures->create('card', [
            'name'              => 'Harshil',
            'token_iin'         => '404464916',
        ]);

        $savedCardWithRandomToken = $this->fixtures->create('card', [
            'name'              => 'Harshil',
            'token_iin'         => '123456789',
        ]);

        $payment = $this->fixtures->create('payment', [
            PaymentEntity::MERCHANT_ID => Account::TEST_ACCOUNT,
        ]);

        $checker1 = (new Checker($offer1,true));
        $checker2 = (new Checker($offer2,true));

        $payment->card()->associate($savedCard);

        $response = $checker1->checkApplicabilityForPayment($payment, $order);
        // for this saved card, offer iin doesn't match
        $this->assertEquals(false, $response);

        $response = $checker2->checkApplicabilityForPayment($payment, $order);
        // for this saved card, one of the offer iin matches. i.e 400782
        $this->assertEquals(true, $response);

        $payment->card()->associate($savedCardWithRandomToken);

        $response = $checker1->checkApplicabilityForPayment($payment, $order);
        $this->assertEquals(false, $response);

        $response = $checker2->checkApplicabilityForPayment($payment, $order);
        $this->assertEquals(false, $response);
    }

    public function testCheckOfferApplicabilityDuringPaymentUsingNewCard()
    {
        $this->mockCardVaultWithCryptogram();

        $offer = $this->fixtures->create('offer', [
            'iins' => ['461786', '400782'],
            'block' => false,
            'type' => 'instant',
        ]);

        $order = $this->fixtures->order->createWithOffers([
            $offer,
        ]);

        $newCard = $this->fixtures->create('card', [
            'name'              => 'Harshil',
            'iin'               => '400782',
            'last4'             => '1111',
        ]);

        $payment = $this->fixtures->create('payment', [
            PaymentEntity::MERCHANT_ID => Account::TEST_ACCOUNT,
        ]);

        $checker = (new Checker($offer,true));

        $payment->card()->associate($newCard);

        $response = $checker->checkApplicabilityForPayment($payment, $order);
        // for this new card, offer iin matches for 400782
        $this->assertEquals(true, $response);
    }
}
