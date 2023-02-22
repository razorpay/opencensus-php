<?php

namespace RZP\Tests\Functional\Offer;

use Carbon\Carbon;
use RZP\Constants\Entity;
use RZP\Constants\Entity as E;
use RZP\Models\Base\DbMigrationMetricsObserver;
use RZP\Models\Merchant\Account;
use RZP\Models\Offer\Core;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use Illuminate\Support\Facades\Config;
use RZP\Services\DbRequestsBeforeMigrationMetric;
use Illuminate\Support\Facades\App;
use Razorpay\Trace\Facades\Trace;

class OffersTest extends TestCase
{
    use RazorxTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/OffersTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        // This is set to 1 Jan 2018
        // Because in test cases offers start date is set
        // to Feb 2018 and it should always be in future
        Carbon::setTestNow("1-1-2018 00:00:00");

        //Creating observer in set up for cases where request is sampled out.
        $entityClass = E::getEntityClass( Entity::OFFER);
        $entityClass::observe(DbMigrationMetricsObserver::class);
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

    public function testCreateDcCardOfferWithIin()
    {
        $this->startTest();
    }

    public function testCreateHDFCDebitCardNoCostEMIOffer(): void
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $this->startTest();
    }

    public function testCreateHDFCDebitCardEMIOffer(): void
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $this->startTest();
    }

    public function testPaymentMethodTypeForCreditCardOfferCreation(): void
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $this->startTest();
    }


    public function testCreateCardOfferWithMaxPaymentCount()
    {
        $this->startTest();
    }

//    public function testOfferPrivateAuth()
//    {
//        $this->fixtures->merchant->addFeatures(['offer_private_auth']);
//
//        $this->ba->privateAuth();
//
//        $this->startTest();
//    }
//
//    public function testOfferPrivateAuthWithoutFeature()
//    {
//        $this->ba->privateAuth();
//
//        $this->startTest();
//    }

    public function testOfferCreateBulk()
    {
        $this->ba->adminAuth();

        $this->startTest();

        $offers = $this->getEntities('offer', [], true);

        $this->assertEquals('100000Razorpay', $offers['items'][0]['merchant_id']);
        $this->assertEquals('10000000000000', $offers['items'][1]['merchant_id']);
    }

    public function testBulkDeactivateOffer()
    {

        $offer1 = $this->fixtures->create('offer:card');
        $offer2 = $this->fixtures->create('offer:wallet');
        $invalidOffer = 'invalid_offer_id';
        $offersArray = [$offer1->getPublicId(),$offer2->getPublicId(),$invalidOffer];

        $response = new Core();
        $response = $response->bulkDeactivateOffers($offersArray);

        $this->assertEquals(2, count($response['successful']));
        $this->assertEquals(1, count($response['failed']));
        $this->assertEquals($offer1->getPublicId(), $response['successful'][0]);
        $this->assertEquals($offer2->getPublicId(), $response['successful'][1]);
        $this->assertEquals($invalidOffer, $response['failed'][0]);


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

    public function testCreateEmiSubventionOffer()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->startTest();
    }

    public function testEmiSubventionOfferWithInvalidAmount()
    {
        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->startTest();
    }

    public function testEmiSubventionWithDuration()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $this->startTest();
    }

    public function testEmiSubventionWithIssuerAndNetwork()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->startTest();
    }

    public function testOfferWithInvalidIssuer()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->startTest();
    }

    public function testInvalidEmiDuration()
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

    public function testConflictingEmiSubOffers()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->create('offer:emi_subvention', [
            'payment_method_type'=>'credit'
        ]);

        $this->startTest();
    }

    public function testCreateOfferBajaj()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

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

    public function testFetchSubscriptionOfferById()
    {
        $this->mockRazorX(__FUNCTION__, 'offer_on_subscription', 'on');

        $offer = $this->fixtures->create('offer:card',
            [
                'active'       => 1,
                'product_type' => 'subscription',
            ]);

        $subOffer = $this->fixtures->create('subscription_offers_master', [
            'redemption_type' => 'cycle',
            'applicable_on'   => 'both',
            'no_of_cycles'    => 10,
            'offer_id'        => $offer->getId(),
        ]);

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

        $this->ba->cronAuth();

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

    public function testCreateOfferWithSameIIN()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData);

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('Offer already exists. Please check the values and try again');

        $this->startTest();
    }

    //The following test case is not related to offer, but adding it here because it has been tested for the get offers route
    public function testDbRequestsBeforeMigrationMetric()
    {
        Trace::shouldReceive('histogram')->zeroOrMoreTimes();

        Trace::shouldReceive('info', 'debug', 'addRecord', 'error')->zeroOrMoreTimes();

        $actualData = [];

        Trace::shouldReceive('count')->andReturnUsing(function ($metric, $data, $count = 1) use (&$actualData)
        {
            $data['count'] = $count;
            $actualData[$metric] = $data;
        });

        $offer = $this->fixtures->create('offer:card');
        $this->startTest();
        App::forgetInstance(DbRequestsBeforeMigrationMetric::class);

        $this->assertArrayHasKey('db_requests_before_migration', $actualData);
        $this->assertEquals('offer_fetch_multiple', $actualData['db_requests_before_migration']['route']);
        $this->assertEquals('offer', $actualData['db_requests_before_migration']['table_name']);
        $this->assertEquals('read', $actualData['db_requests_before_migration']['action']);
        $this->assertGreaterThanOrEqual(1, $actualData['db_requests_before_migration']['count']);

    }

    public function testCreateCardlessEmiOfferWithIssuer()
    {
        $this->fixtures->merchant->enableCardlessEmi(Account::TEST_ACCOUNT);
        $this->startTest();
    }

    public function testCreateCardlessEmiOfferWithoutIssuer()
    {
        $this->fixtures->merchant->enableCardlessEmi(Account::TEST_ACCOUNT);
        $this->startTest();
    }
}
