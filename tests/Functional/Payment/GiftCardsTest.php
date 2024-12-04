<?php
namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Entity as Merchant;
use Mockery;


class GiftCardsTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ba->publicAuth();
    }

    public function testGiftCardsPaymentCreate()
    {
        $this->enablePgRouterConfig();

        $payment = [
            'amount'            => '50000',
            'currency'          => 'INR',
            'email'             => 'a@b.com',
            'contact'           => '9918899029',
            'notes'             => [
                'merchant_order_id' => 'random order id',
            ],
            'description'       => 'random description',
            'method' => 'gift_cards',
            'gift_cards' => [
                [
                    'card_number' => '1234567890',
                    'amount' => '50000'
                ]
            ]
        ];


        $pgService = Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout)
            {
                return [
                    'body' => [
                        'data' => [
                            'pg_router' => 'true'
                        ]
                    ]

                ];
            });

        $request = [
            'content' => $payment,
            'url'     => '/payments/create/ajax',
            'method'  => 'post'
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($content['data']['pg_router'], 'true');
    }
}
