<?php

namespace RZP\Tests\Functional\Offer;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class OffersTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/OffersTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        // This is set to 1 Jan 2018
        // Because in test cases offers start date is set
        // to Feb 2018 and it should always be in future
        Carbon::setTestNow("1-1-2018 00:00:00");
    }

    public function testCreateCardOffer()
    {
        $this->startTest();
    }

    public function testCreateOfferWithNullMethod()
    {
        $this->startTest();
    }

    public function testCreateOfferWithNullMethodAndInvalidIssuer()
    {
        $this->startTest();
    }

    public function testCreateCardOfferWithIin()
    {
        $this->startTest();
    }

    public function testCreateCardOfferWithMaxPaymentCount()
    {
        $this->startTest();
    }

    public function testCreateCardOfferWithLinkedOfferIds()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['content']['linked_offer_ids'] = (array) $offer->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['linked_offer_ids'] = (array) $offer->getPublicId();

        $this->startTest();
    }

    public function testCreateCardOfferWithInvalidLinkedOfferIds()
    {
        $offer = $this->fixtures->create('offer:card', [
            'merchant_id' => '100000Razorpay'
        ]);

        $this->testData[__FUNCTION__]['request']['content']['linked_offer_ids'] = (array) $offer->getPublicId();

        $this->startTest();
    }

    public function testCreateWalletOffer()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');

        $this->startTest();
    }

    public function testCreateNetbankingOffer()
    {
        $this->startTest();
    }

    public function testCreateFlatCashbackOffer()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');

        $this->startTest();
    }

    public function testCreateIdenticalOffers()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->startTest();
    }

    public function testCreateOfferWithoutCashbackCriteria()
    {
        $this->startTest();
    }

    public function testCreateCardOfferWithInvalidNetwork()
    {
        $this->startTest();
    }

    public function testCreateCardOfferWithUnsupportedNetwork()
    {
        $this->startTest();
    }

    public function testCreateWalletOfferWithInvalidWallet()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');

        $this->startTest();
    }

    public function testCreateNetbankingOfferWithInvalidBankCode()
    {
        $this->startTest();
    }

    public function testCreateOfferWithInvalidPaymentMethod()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');

        $this->startTest();
    }

    public function testCreateOfferWithInvalidIssuer()
    {
        $this->startTest();
    }

    public function testCreateOfferWithPercentRateAndFlatCashback()
    {
        $this->startTest();
    }

    public function testCreateOfferWithInvalidOfferPeriod()
    {
        $this->startTest();
    }

    public function testCreateOfferWithCorporateOrRetailIssuer()
    {
        $this->startTest();
    }

    public function testCreateOfferValidateMaxCashback()
    {
        $this->startTest();
    }

    public function testCreateOfferInternationalEmi()
    {
        $this->fixtures->merchant->enableEmi();
        $this->startTest();
    }

    public function testCreateOfferValidateMethodType()
    {
        $this->startTest();
    }

    public function testAddIinsToCardOffer()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $offer->getPublicId();

        $this->startTest();
    }

    public function testAddIinsInvalidFormat()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->startTest();
    }

    public function testAddIinsToNonCardOffer()
    {
        $offer = $this->fixtures->create('offer:wallet');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->startTest();
    }

    public function testUpdateExistingOffer()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $offer->getPublicId();

        $this->startTest();
    }

    public function testUpdateWalletOfferWithMaxPaymentCount()
    {
        $offer = $this->fixtures->create('offer:wallet');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->startTest();
    }

    public function testUpdateCardOfferWithNoMaxPaymentCount()
    {
        $offer1 = $this->fixtures->create('offer:card');

        $offer2 = $this->fixtures->create('offer:card', [
            'max_payment_count' => null,
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer2->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['linked_offer_ids'] = (array) $offer1->getPublicId();

        $this->startTest();
    }

    public function testUpdateCardOfferWithInvalidLinkedOfferIds()
    {
        $offer1 = $this->fixtures->create('offer:card', [
            'merchant_id' => '100000Razorpay'
        ]);

         $offer2 = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer2->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['linked_offer_ids'] = (array) $offer1->getPublicId();

        $this->startTest();
    }

    public function testGetMultipleOffers()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->startTest();
    }

    public function testFetchOfferById()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $offer->getPublicId();

        $this->startTest();
    }

    public function testDeactivateOffer()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $offer->getPublicId();

        $this->startTest();
    }

    public function testDeactivateAllOffer()
    {
        $offer = $this->fixtures->create('offer:expired');

        $this->ba->appAuth();

        $this->testData[__FUNCTION__]['response']['content'] = [$offer->getPublicId()];

        $this->startTest();
    }

    public function testCreateOfferValidateMerchant()
    {
        $this->fixtures->merchant->disableCard('10000000000000');

        $this->startTest();
    }

    public function testCreateCardOfferWithInvalidIinLength()
    {
        $this->startTest();
    }

    public function testCreateCardOfferWithInvalidFullNetworkName()
    {
        $this->startTest();
    }

    public function testCreateOfferMinAmount()
    {
        $this->startTest();
    }
}
