<?php

namespace Functional\Payment;

use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\App;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\Invoice\InvoiceTestTrait;
use RZP\Tests\Functional\Helpers\PaymentsUpiTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class OneCcPaymentsTest extends TestCase
{
    use OAuthTrait;
    use MocksSplitz;
    use PartnerTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use InvoiceTestTrait;
    use TerminalTrait;
    use HeimdallTrait;
    use PaymentsUpiTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentCreateTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');
        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    protected function getOrderMetaValue()
    {
        $app = App::getFacadeRoot();
        $shipping_address = [
            'line1'         => 'some line one',
            'line2'         => 'some line two',
            'city'          => 'Bangalore',
            'state'         => 'Karnataka',
            'zipcode'       => '560001',
            'country'       => 'in',
            'type'          => 'shipping_address',
            'primary'       => true
        ];
        $billing_address = [
            'line1'         => 'some line one',
            'line2'         => 'some line two',
            'city'          => 'Bangalore',
            'state'         => 'Karnataka',
            'zipcode'       => '560001',
            'country'       => 'in',
            'type'          => 'billing_address',
            'primary'       => true
        ];
        $customer = [
            'contact'           =>'+9191111111111',
            'email'             =>'john.doe@razorpay.com',
            'shipping_address'  =>$shipping_address,
            'billing_address'   =>$billing_address

        ];
        return [
            'cod_fee'           => 100000,
            'net_price'         => 1100000,
            'sub_total'         => 1100000,
            'shipping_fee'      => 10000,
            'customer_details'  => $app['encrypter']->encrypt($customer),
            'line_items_total'  => 1000000,
        ];
    }


    public function test1CCOrderPaymentsWithCustomerDeatils(){
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $order = $this->fixtures->order->create(['receipt' => 'receipt']);
        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => $this->getOrderMetaValue(),
                'type'     => 'one_click_checkout',
            ]);
        $this->ba->publicAuth();

        $testData = $this->testData[__FUNCTION__];
        $payment = $this->getDefaultPaymentArray();
        $payment["order_id"] = 'order_'.$order->getId();
        $payment["amount"] = $order->getAmount();
        $testData['request']['content'] = $payment;

        $response = $this->makeRequestParent($testData['request']);

        $this->processAndAssertStatusCode($testData, $response);
        $this->processAndAssertResponseData($testData, $response);
    }

    public function test1CCOrderPaymentsWithoutCustomerDeatils()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $order = $this->fixtures->order->create(['receipt' => 'receipt']);
        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value' => [
                    'line_items_total' => $order->getAmount(),
                    'cod_fee'          => 0,
                    'shipping_fee'     => 0,
                    'line_items'       => $this->lineItems(),
                ],
                'type' => 'one_click_checkout',
            ]);
        $this->ba->publicAuth();
        $testData = $this->testData[__FUNCTION__];
        $payment = $this->getDefaultPaymentArray();
        $payment["order_id"] = 'order_' . $order->getId();
        $payment["amount"] = $order->getAmount();
        $testData['request']['content'] = $payment;

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Something went wrong, please try again after sometime.');

        $response = $this->makeRequestParent($testData['request']);

        $this->processAndAssertStatusCode($testData, $response);
        $this->processAndAssertResponseData($testData, $response);
    }

    public function test1CCOrderPaymentsForMagicX()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $order = $this->fixtures->order->create(['receipt' => 'receipt']);
        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value' => [
                    'line_items_total' => $order->getAmount(),
                    "cod_fee"          => 0,
                    "shipping_fee"     => 0,
                    "line_items"       => null,
                ],
                'type' => 'one_click_checkout',
            ]);
        $this->ba->publicAuth();
        $testData = $this->testData[__FUNCTION__];
        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = 'order_' . $order->getId();
        $payment['amount'] = $order->getAmount();
        $testData['request']['content'] = $payment;
        $response = $this->makeRequestParent($testData['request']);
        $this->processAndAssertStatusCode($testData, $response);
        $this->processAndAssertResponseData($testData, $response);
    }

    public function test1CCOrderPaymentsForMagicXWithLineItems()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $order = $this->fixtures->order->create(['receipt' => 'receipt']);
        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value' => [
                    'line_items_total' => $order->getAmount(),
                    "cod_fee"          => 0,
                    "shipping_fee"     => 0,
                    "line_items"       => $this->lineItems(),
                ],
                'type' => 'one_click_checkout',
            ]);
        $this->ba->publicAuth();
        $testData = $this->testData[__FUNCTION__];
        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = 'order_' . $order->getId();
        $payment['amount'] = $order->getAmount();
        $testData['request']['content'] = $payment;
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Something went wrong, please try again after sometime.');
        $response = $this->makeRequestParent($testData['request']);
        $this->processAndAssertStatusCode($testData, $response);
        $this->processAndAssertResponseData($testData, $response);
    }

    protected function lineItems(): array
    {
        return [
            [
                'variant_id' => '12321',
                'product_id' => '23456',
                'price'      => 1000000,
                'name'       => 'Test Product',
            ]
        ];
    }
}
