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

        $this->ba->privateAuth();
    }

    public function testCreateCardOffer()
    {
        $this->startTest();
    }

    public function testCreateCardOfferWithIin()
    {
        $this->startTest();
    }

    public function testCreateWalletOffer()
    {
        $this->startTest();
    }

    public function testCreateNetbankingOffer()
    {
        $this->startTest();
    }

    public function testCreateFlatCashbackOffer()
    {
        $this->startTest();
    }

    public function testCreateIdenticalOffers()
    {
        $this->fixtures->offer->createCardOffer();

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
        $this->startTest();
    }

    public function testCreateNetbankingOfferWithInvalidBankCode()
    {
        $this->startTest();
    }

    public function testCreateOfferWithInvalidPaymentMethod()
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

    public function testUpdateExistingOffer()
    {
        $offer = $this->fixtures->offer->createCardOffer();

        $request = [
            'content' => [
                'name' => 'Updated name'
            ],
            'url' => '/offers/' . $offer->getPublicId(),
            'method' => 'PATCH'
        ];

        $expectedResponse = [
            'id'                  => $offer->getPublicId(),
            'name'                => "Updated name",
            "payment_method"      => "card",
            "payment_method_type" => "credit",
            "payment_network"     => "VISA",
            "issuer"              => "HDFC",
            "active"              => TRUE,
            "percent_rate"        => 1000,
            "payment_count"       => 2,
            "processing_time"     => 86400,
            "starts_at"           => Carbon::today('Asia/Kolkata')->timestamp,
            "ends_at"             => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals($expectedResponse, $content);
    }

    public function testGetMultipleOffers()
    {
        $offer = $this->fixtures->offer->createCardOffer();

        $request = [
            'url' => '/offers',
            'method' => 'GET'
        ];

        $data = [
            'entity' => 'collection',
            'count'  => 1,
            'items'  => [
                [
                    'id'                  => $offer->getPublicId(),
                    'name'                => 'Test Offer',
                    'payment_method'      => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network'     => 'VISA',
                    'issuer'              => 'HDFC',
                    'active'              => TRUE,
                    'percent_rate'        => 1000,
                    'payment_count'       => 2,
                    'processing_time'     => 86400,
                    "starts_at"           => Carbon::today('Asia/Kolkata')->timestamp,
                    "ends_at"             => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
                ]
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals($data, $content);
    }

    public function testFetchOfferById()
    {
        $offer = $this->fixtures->offer->createCardOffer();

        $request = [
            'url' => '/offers/' . $offer->getPublicId(),
            'method' => 'GET'
        ];

        $data = [
            'id'                  => $offer->getPublicId(),
            'name'                => 'Test Offer',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => 'VISA',
            'issuer'              => 'HDFC',
            'active'              => TRUE,
            'percent_rate'        => 1000,
            'payment_count'       => 2,
            'processing_time'     => 86400,
            "starts_at"           => Carbon::today('Asia/Kolkata')->timestamp,
            "ends_at"             => Carbon::today('Asia/Kolkata')->addMonth()->timestamp,
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals($data, $content);
    }
}
