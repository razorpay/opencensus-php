<?php
namespace RZP\Tests\Unit\Models\Payment;

use RZP\Tests\Functional\TestCase;

class EntityTest extends TestCase
{
    function setUp()
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

    function testGetOrderIdForCsCart()
    {

        $payment = $this->payment;
        $payment->setNotes([
            'cs_order_id' => 'cascart_123'
        ]);
        $this->assertEquals('cascart_123', $payment->getOrderId());
    }

    function testGetOrderIdForWooCommerce()
    {

        $payment = $this->payment;
        $payment->setNotes([
            'woocommerce_order_id' => 'wc_123'
        ]);
        $this->assertEquals('wc_123', $payment->getOrderId());
    }
}
