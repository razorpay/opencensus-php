<?php
namespace Functional\Payment;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Entity as Merchant;
use Mockery;


class GiftCardTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ba->publicAuth();
    }

    public function testGiftCardPaymentCreate()
    {
        $this->fixtures->merchant->edit('10000000000000',[
            Merchant::COUNTRY_CODE => 'MY'
        ]);

        $this->enablePgRouterConfig();

        $payment = $this->getDefaultPaymentArray();
        $payment['currency'] = 'MYR';
        $payment['method'] = "gift_cards";
        $payment['bank'] = 'PICA';

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
