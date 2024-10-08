<?php

namespace RZP\Tests\Functional\Order\Product;

use App;
use Mockery;
use RZP\Models\Order;
use RZP\Exception\BadRequestException;
use RZP\Error\ErrorCode;
use RZP\Models\Offer\EntityOffer\Repository as EntityOfferRepository;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Fixtures\Entity\Merchant;

class ProductDecompTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/ProductTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function forceFillNonAdminEntites($order)
    {
        if (isset($response['body']['merchant_id']) === true)
        {
            $merchant = Merchant\Entity::findOrFail($response['body']['merchant_id']);

            $order = $order->merchant()->associate($merchant);
        }

        return $order;
    }
    public function setOffersInOrder($entityOffers, mixed $offersFromPGRouter, Order\Entity $order): void
    {
        $offerIDsFromAPI = [];

        foreach ($entityOffers as $entityOffer)
        {
            $offerIDsFromAPI[] = $entityOffer->offer_id;
        }

        if (!empty($offerIDsFromAPI))
        {
            $differenceFromAPI = $offerIDsFromAPI;

            if (!empty($offersFromPGRouter))
            {
                Offer\Entity::verifyIdAndSilentlyStripSignMultiple($offersFromPGRouter);

                $differenceFromAPI = array_diff($offerIDsFromAPI, $offersFromPGRouter);

            }

            if (!empty($differenceFromAPI))
            {
                $this->trace->info(TraceCode::OFFER_RESPONSE_PARITY, [
                    "orderID" => $order->getId(),
                    "offerIDsFromAPI" => $offerIDsFromAPI,
                    "offersFromPGRouter" => $offersFromPGRouter,
                    "differenceFromAPI" => $differenceFromAPI,
                ]);
            }
            // If there are no offers from PG Router, use $offerIDsFromAPI directly
            $offerIDs = $offerIDsFromAPI;

            $order->setAttribute('offers_data', $offerIDs);
        }
    }

    public function setNotificationInOrder($response,  Order\Entity $order)
    {
        if(isset($response['body']['notification']) === true)
        {
            $order->setAttribute('notification_data', $response['body']['notification']);
        }
        else if((isset($response['body']['order_relationships'])) and
            (count($response['body']['order_relationships']) > 0))
        {
            foreach ($response['body']['order_relationships'] as $relationship) {

                $entityType = $relationship['entity_type'];

                if($entityType === 'notification')
                {
                    try
                    {
                        $notificationRepo = new CardMandateNotification\Repository();

                        $notificationData = $notificationRepo->findByOrderId($relationship['order_id']);

                        if ($notificationData !== null)
                        {
                            $notificationDataArray = $notificationData->toNotificationArray();

                            $order->setAttribute('notification', $notificationDataArray);

                            $order->setAttribute('notification_data', $notificationDataArray);
                        }
                    }
                    catch (\Throwable $e)
                    {
                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::CARD_RECURRING_NOTIFICATION_ENTITY_FETCH_FAILED);
                    }
                }
            }
        }
    }

    private function forceFillOrderFromResponse($response)
    {
        if ((empty($response) === false) and
            (isset($response['body']) === true))
        {
            if (isset($response['body']['notes']) === true and is_array($response['body']['notes']) === false)
            {
                $response['body']['notes'] = json_decode($response['body']['notes']);
            }

            $offersFromPGRouter = $response['body']['offers'] ?? [];

            unset($response['body']['offers']);

            $response['body']['bank_account_data'] = $response['body']['bank_account'] ?? [];
            $response['body']['products_data'] = $response['body']['products'] ?? [];
            $order = (new Order\Entity())->forceFill($response['body']);
            $this->setNotificationInOrder($response, $order);

            $entityOffers = (new EntityOfferRepository())->findByEntityIdAndType($order->getId(), 'offer');

            if (isset($response['body']['order_metas']) === true)
            {
                foreach ($response['body']['order_metas'] as $meta)
                {
                    $order_meta = (new Order\OrderMeta\Entity)->forceFill($meta);

                    $order->orderMetas->add($order_meta);
                }
            }

            $this->setOffersInOrder($entityOffers, $offersFromPGRouter, $order);

            $order->setExternal(true);

            if (strpos($this->request->getRequestUri(), '/v1/admin/') !== 0)
            {
                return $this->forceFillNonAdminEntites($order);
            }
            return $order;
        }

        return null;
    }

    public function testCreateOrderWithProductsInPGRouter()
    {
        $this->enablePgRouterConfig();
        $pgService = Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();
        $this->app->instance('pg_router', $pgService);
        $app = App::getFacadeRoot();
        $this->request = $app['request'];
        $response = [
            'body' => [
                'id' => "Odgdjdh2446754",
                'amount' => 50000,
                'currency' => 'INR',
                'products' => [
                    [
                        'type' => 'mutual_fund',
                        'receipt' => 'dummy_receipt1',
                        'plan' => 'dummy_plan1',
                        'scheme' => 'dummy_scheme1',
                        'option' => 'dummy_option1',
                        'amount' => '12345',
                        'folio' => 'dummy_folio1',
                        'mf_member_id' => 'dummy_mf_member_id',
                        'mf_user_id' => 'dummy_mf_user_id',
                        'mf_partner' => 'dummy_mf_partner',
                        'mf_investment_type' => 'dummy_mf_investment_type',
                        'mf_amc_code' => 'dummy_mf_amc_code',
                        'notes' => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ]
                    ],
                    [
                        'type' => 'loan',
                        'loan_number' => '1234556',
                        'amount' => '6789',
                        'receipt' => 'dummy_receipt2',
                    ],
                ],
            ],
        ];

        $order = $this->forceFillOrderFromResponse($response);

        $pgService->shouldReceive('createOrder')
            ->with(Mockery::type('array'), Mockery::type('bool'))
            ->andReturnUsing(function (array $input, bool $throwExceptionOnFailure = false) use ($order) {
                return $order;
            });
        return $this->startTest();
    }

    public function testCreateOrderErrorScenarioWithProductsInPGRouterWithInvalidProductType()
    {
        $this->enablePgRouterConfig();
        $pgService = Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();
        $this->app->instance('pg_router', $pgService);

        $description = 'test is not a valid product type';

        $pgService->shouldReceive('createOrder')
            ->with(Mockery::type('array'), Mockery::type('bool'))
            ->andThrow(new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                null,
                [],
                $description
            ));

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage("test is not a valid product type");

        $this->startTest();
    }


    public function testCreateOrderErrorScenarioWith5xxResponseFromPgRouter()
    {
        $this->enablePgRouterConfig();
        $pgService = Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();
        $this->app->instance('pg_router', $pgService);

        $description = 'Error with PG Router service';

        $pgService->shouldReceive('createOrder')
            ->with(Mockery::type('array'), Mockery::type('bool'))
            ->andThrow(new BadRequestException(
                ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE,
                null,
                [],
                $description
            ));

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage("Error with PG Router service");

        $this->startTest();
    }
}
