<?php
namespace RZP\Tests\Unit\Models\Payment;

use Carbon\Carbon;
use Mockery;
use RZP\Constants\Timezone;
use RZP\Http\Middleware\AdminAccess;
use RZP\Models\Customer\Repository;
use RZP\Models\Payment;
use RZP\Models\Payment\Method;
use RZP\Models\RewardPoint\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\PublicCollection;

class EntityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = new \RZP\Models\Payment\Entity;

        $this->payment->setNotes([
            'order_link'    =>  'https://github.com',
            'merchant_order_id' =>  '1235'
        ]);
    }

    function testGetOrderId()
    {
        $this->assertEquals('1235', $this->payment->getOrderId());
    }

    function testGetOrderIdForOpenCart()
    {
        $payment = $this->payment;
        $payment->setNotes([
            'opencart_order_id' => 'opencart_123'
        ]);
        $this->assertEquals('opencart_123', $payment->getOrderId());
    }

    function getGetOrderIdForMagento()
    {
        $payment = $this->payment;
        $payment->setNotes([
            'magento_order_id' => 'magento_123'
        ]);
        $this->assertEquals('magento_123', $payment->getOrderId());
    }

    function testGetOrderIdForPrestashop()
    {
        $payment = $this->payment;
        $payment->setNotes([
            'prestashop_order_id' => 'prestashop_123'
        ]);
        $this->assertEquals('prestashop_123', $payment->getOrderId());
    }

    public function testGetOrderIdForCsCart()
    {
        $payment = $this->payment;
        $payment->setNotes([
            'cs_order_id' => 'cascart_123'
        ]);
        $this->assertEquals('cascart_123', $payment->getOrderId());
    }

    public function testGetOrderIdForWooCommerce()
    {
        $payment = $this->payment;
        $payment->setNotes([
            'woocommerce_order_id' => 'wc_123'
        ]);
        $this->assertEquals('wc_123', $payment->getOrderId());
    }

    public function testGetMethodWithDetailFpx()
    {
        $payment = $this->payment;
        $payment -> setMethod(Method::FPX);
        $payment -> setBank(Payment\Processor\Fpx::ARBK);
        $methodWithDetail = $payment->getMethodWithDetail();
        $this->assertEquals('Financial Process Exchange', $methodWithDetail[0]);
        $this->assertEquals('AmBank Malaysia Berhad', $methodWithDetail[1]);
    }

    public function testGetMethodWithDetailGiftCards()
    {
        $payment = $this->payment;
        $payment -> setMethod(Method::GIFT_CARDS);
        $payment -> setGateway(Payment\Gateway::WALLET_RAZORPAYWALLET);
        $methodWithDetail = $payment->getMethodWithDetail();
        $this->assertEquals('Giftcards', $methodWithDetail[0]);
        $this->assertEquals('Razorpay Gift Card', $methodWithDetail[1]);
    }

    public function testGetBankNameFpx()
    {
        $payment = $this->payment;
        $payment -> setMethod(Method::FPX);
        $payment -> setBank(Payment\Processor\Fpx::ARBK);
        $this->assertEquals('AmBank Malaysia Berhad', $payment->getBankName());
    }

    public function testGetBankNameNetBanking()
    {
        $payment = $this->payment;
        $payment -> setMethod(Method::NETBANKING);
        $payment -> setBank(Payment\Processor\Netbanking::HDFC_C);
        $this->assertEquals('HDFC Bank - Corporate Banking', $payment->getBankName());
    }

    public function testToArrayHosted()
    {
        $payment = $this->fixtures->create('payment:captured');

        $actual = $payment->toArrayHosted();

        $createdAt = Carbon::createFromTimestamp($payment->getCreatedAt(), Timezone::IST);

        $formattedCreatedAt = $createdAt->format(Payment\Entity::HOSTED_TIME_FORMAT);

        $expected = [
            'id'                   => $payment->getPublicId(),
            'method'               => 'card',
            'amount'               => 1000000,
            'status'               => 'captured',
            'created_at'           => $payment->getCreatedAt(),
            'formatted_amount'     => '₹ 10000',
            'formatted_created_at' => $formattedCreatedAt,
        ];

        $this->assertEquals($expected, $actual);

        // Assert over collection as well

        $payments = (new PublicCollection)->push($payment);

        $actual = $payments->toArrayHosted();

        $this->assertEquals([$expected], $actual);
    }

    public function testRewardAttributePresent() {
        $payment = $this->fixtures->create('payment:captured');
        $reward =  (new Entity())->forceFill([
            'id' => '100000000000',
            'amount' => 500,
            'points' => 50
        ]);
        $payment->reward()->associate($reward);

        $this->assertEquals(true, $payment->hasReward());
    }

    public function testRewardAttributeAbsent()
    {
        $payment = $this->fixtures->create('payment:captured');

        $this->assertEquals(false, $payment->hasReward());
    }

    public function testCustomerLazyRead()
    {
        $mockCustomerRepo = Mockery::mock('\RZP\Models\Customer\Repository', [$this->app]);
        $mockCustomerEntity = new \RZP\Models\Customer\Entity();
        $mockCustomerId = '100000customer';
        $mockCustomerEntity->fill([
            'id' => $mockCustomerId,
            'name' => 'RzpCustomerName',
            'email' => 'RzpCustomer@email.com',
            'contact' => '9999999999',
            'merchant_id' => '100000Razorpay'
        ]);
        $mockCustomerRepo->shouldReceive('find')->with($mockCustomerId)->andReturn($mockCustomerEntity);

        $mockRepoManager = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app]);
        $this->app->instance('repo', $mockRepoManager);
        $mockRepoManager->shouldReceive('driver')->with('customer')->andReturn($mockCustomerRepo);


        $payment = $this->payment;
        $payment->setAttribute('customer_id', $mockCustomerId);
        $this->assertEquals($mockCustomerEntity, $payment->customer);
    }

    public function testGlobalCustomerLazyRead()
    {
        $mockCustomerRepo = Mockery::mock('\RZP\Models\Customer\Repository', [$this->app]);
        $mockCustomerEntity = new \RZP\Models\Customer\Entity();
        $mockCustomerId = '100000customer';
        $mockCustomerEntity->fill([
            'id' => $mockCustomerId,
            'name' => 'RzpCustomerName',
            'email' => 'RzpCustomer@email.com',
            'contact' => '9999999999',
            'merchant_id' => '100000Razorpay'
        ]);
        $mockCustomerRepo->shouldReceive('find')->with($mockCustomerId)->andReturn($mockCustomerEntity);

        $mockRepoManager = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app]);
        $this->app->instance('repo', $mockRepoManager);
        $mockRepoManager->shouldReceive('driver')->with('customer')->andReturn($mockCustomerRepo);


        $payment = $this->payment;
        $payment->setAttribute('global_customer_id', $mockCustomerId);
        $this->assertEquals($mockCustomerEntity, $payment->globalCustomer);
    }
}
