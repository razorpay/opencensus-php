<?php

namespace RZP\Tests\Functional\VirtualAccount;

use Mockery;
use Closure;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factory;

use RZP\Models\BankTransfer;
use RZP\Models\Terminal\Type;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Webhook;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\VirtualAccount\Core;
use RZP\Models\VirtualAccount\Entity;
use RZP\Models\VirtualAccount\Status;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class UpiQrTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;

    /**
     * @var Entity
     */
    protected $va;

    protected $input = [
        'customer_id'       => 'cust_100000customer',
        'description'       => 'Upi Transaction',
        'amount_expected'   => 12388,
        'receivers'         => [
            'qr_code'       => [
                'method'    => [
                    'card'  => false,
                    'upi'   => true,
                ]
            ],
            'types'         => [
                'qr_code',
            ],
        ]
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->fixtures->create('terminal:shared_sharp_terminal');
    }

    public function testCreate()
    {
        $this->markTestSkipped('Need to see why the VA is not getting created');

        $this->createVirtualAccount($this->input);
        $this->va = $this->getDbLastEntity('virtual_account');

        $qrCode = $this->getDbLastEntity('qr_code');

        $this->assertStringStartsWith('upi://pay?pa=upi@razopay&pn=TestMerchant&tr=', $qrCode->getQrString());
        $this->assertStringEndsWith('&tn=razorpay&am=123.88&cu=INR&mc=5411', $qrCode->getQrString());

        $content = [
            'reference' => $this->va->qrCode->getPublicId(),
            'method'    => 'upi',
            'amount'    => '100',
        ];

        $request['content'] = $content;

        $request['method'] = 'post';

        $request['url'] = '/bharatqr/pay/test';

        $response = $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastPayment();

        $this->va->refresh();

        $this->assertArraySubset([
            Entity::AMOUNT_PAID         => 100,
            Entity::AMOUNT_RECEIVED     => 100,
        ], $this->va->toArray());
    }

    public function testFailureOnNoAmountExpected()
    {
        unset($this->input['amount_expected']);

        $this->makeRequestAndCatchException(
        function()
        {
            $this->createVirtualAccount($this->input);
        },
        BadRequestValidationFailureException::class,
        'Amount expected is required for UPI QR receivers');
    }
}
