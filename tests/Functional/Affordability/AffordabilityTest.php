<?php

namespace RZP\Tests\Functional\Affordability;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use RZP\Models\Offer\Entity as OfferEntity;
use RZP\Services\Mock\DataLakePresto as DataLakePrestoMock;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class AffordabilityTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->testDataFilePath = __DIR__ . '/helpers/AffordabilityTestData.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->affordabilityInternalAppAuth();

//        $this->fixtures->merchant->enablePayLater('10000000000000');
    }

    public function testFetchedOffersAreSortedByPopularity(): void
    {
        $offerAttributes = [
            OfferEntity::DEFAULT_OFFER => true,
            OfferEntity::ACTIVE => true,
        ];
        $offer1 = $this->fixtures->create('offer:card', $offerAttributes);
        $offer1->saveOrFail();
        $offer1->refresh();

        $offer2 = $offer = $this->fixtures->create('offer:wallet', $offerAttributes);
        $offer2->saveOrFail();
        $offer2->refresh();

        $offer3 = $offer = $this->fixtures->create('offer:emi_subvention', $offerAttributes);
        $offer3->saveOrFail();
        $offer3->refresh();

        $prestoService = $this->getMockBuilder(DataLakePrestoMock::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods([ 'getDataFromDataLake'])
            ->getMock();

        $prestoServiceData = [
            [
                'offer_id' => $offer1->getId(),
                'offer_usage' => 100,
            ],
            [
                'offer_id' => $offer2->getId(),
                'offer_usage' => 200,
            ],
            [
                'offer_id' => $offer3->getId(),
                'offer_usage' => 150,
            ],
        ];

        $prestoService->method( 'getDataFromDataLake')
            ->willReturn($prestoServiceData);

        $this->app->instance('datalake.presto', $prestoService);

        $visible = OfferEntity::getVisibleForAffordability();
        $offerItems = [
            Arr::only($offer2->toArray(), $visible), // Most Popular
            Arr::only($offer3->toArray(), $visible),
            Arr::only($offer1->toArray(), $visible), // Least Popular
        ];

        $this->testData[__FUNCTION__]['response']['content']['entities']['offers']['items'] = $offerItems;

        $response = $this->startTest();

        $this->assertEquals($offerItems, $response['entities']['offers']['items']);
    }

    public function testFetchOffersReturnsAllActiveOffersIrrespectiveOfTheirDefaultCheckoutVisibility(): void
    {
        $offerAttributes = [
            OfferEntity::STARTS_AT => Carbon::now()->subMonth()->getTimestamp(),
            OfferEntity::ENDS_AT => Carbon::now()->addMonth()->getTimestamp(),
            OfferEntity::ACTIVE => true,
        ];
        // Create an offer with default checkout visibility turned off.
        $offerAttributes[OfferEntity::DEFAULT_OFFER] = false;
        $offer1 = $this->fixtures->create('offer:card', $offerAttributes);
        $offer1->saveOrFail();
        $offer1->refresh();

        // Create an offer with default checkout visibility turned on.
        $offerAttributes[OfferEntity::DEFAULT_OFFER] = true;
        $offer2 = $this->fixtures->create('offer:wallet', $offerAttributes);
        $offer2->saveOrFail();
        $offer2->refresh();

        $prestoService = $this->getMockBuilder(DataLakePrestoMock::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods([ 'getDataFromDataLake'])
            ->getMock();

        $prestoServiceData = [
            [
                'offer_id' => $offer1->getId(),
                'offer_usage' => 100,
            ],
            [
                'offer_id' => $offer2->getId(),
                'offer_usage' => 200,
            ],
        ];

        $prestoService->method( 'getDataFromDataLake')
            ->willReturn($prestoServiceData);

        $this->app->instance('datalake.presto', $prestoService);

        $visible = OfferEntity::getVisibleForAffordability();
        $offerItems = [
            Arr::only($offer2->toArray(), $visible), // Most Popular
            Arr::only($offer1->toArray(), $visible), // Least Popular
        ];

        $this->testData[__FUNCTION__]['response']['content']['entities']['offers']['items'] = $offerItems;

        $response = $this->startTest();

        $this->assertEquals($offerItems, $response['entities']['offers']['items']);
    }

    public function testFetchOffersReturnsEmptyResponseWhenThereIsNoActiveOffer(): void
    {
        // Create an Offer but don't mark it as active
        $offer1 = $this->fixtures->create(
            'offer:card',
            [
                OfferEntity::STARTS_AT => Carbon::now()->subMonth()->getTimestamp(),
                OfferEntity::ENDS_AT => Carbon::now()->addMonth()->getTimestamp(),
                OfferEntity::DEFAULT_OFFER => true,
                OfferEntity::ACTIVE => false,
            ]
        );
        $offer1->saveOrFail();

        $response = $this->startTest();

        $this->assertEquals([], $response['entities']['offers']['items']);
    }
}
