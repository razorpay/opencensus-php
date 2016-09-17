<?php

namespace RZP\Tests\Functional\Address;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use Mockery;

class AddressTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/AddressTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testCreateShippingAddress()
    {
        $this->startTest();
    }

    public function testCreateMoreShippingAddressThanMaxAllowed()
    {
        $this->fixtures->times(3)->create('address');

        $this->startTest();
    }

    public function testCreateShippingAddressWithPrimaryFalse()
    {
        $this->startTest();
    }

    public function testCreateShippingAddressPrimarySwitch()
    {
        $primaryAddress = $this->fixtures->create('address');

        //$this->fixtures->customer->edit('100000customer', ['shipping_address_id' => $primaryAddress->getId()]);

        $response = $this->startTest();

        //$customer = $this->getEntityById('customer', '100000customer', true);

        //$this->assertEquals($response['id'], 'addr_' . $customer['shipping_address_id']);
    }

    public function testCreateShippingAddressNoPrimarySwitch()
    {
        $primaryAddress = $this->fixtures->create('address');

        //$this->fixtures->customer->edit('100000customer', ['shipping_address_id' => $primaryAddress->getId()]);

        $this->startTest();

        //$customer = $this->getEntityById('customer', '100000customer', true);

        //$this->assertEquals($primaryAddress['id'], $customer['shipping_address_id']);
    }

    public function testSetPrimaryAddressForNonPrimaryAddressWithNoSwitch()
    {
        $address = $this->fixtures->create('address');

        $this->fixtures->address->edit($address->getId(), ['primary' => false]);

        $requestContent = $this->getRequestContentForSetPrimaryAddress($address->getPublicId());

        $response = $this->startTest($requestContent);

        //$customer = $this->getEntityById('customer', '100000customer', true);

        //$this->assertEquals($response['id'], $customer['shipping_address_id']);
    }

    public function testSetPrimaryAddressForNonPrimaryAddressWithSwitch()
    {
        $primaryAddress = $this->fixtures->create('address');

        //$this->fixtures->customer->edit('100000customer', ['shipping_address_id' => $primaryAddress->getId()]);

        $nonPrimaryAddress = $this->fixtures->create('address');

        $this->fixtures->address->edit($nonPrimaryAddress->getId(), ['primary' => false]);

        $requestContent = $this->getRequestContentForSetPrimaryAddress($nonPrimaryAddress->getPublicId());

        $this->startTest($requestContent);

        //$customer = $this->getEntityById('customer', '100000customer', true);

        //$this->assertEquals($nonPrimaryAddress->getId(), $customer['shipping_address_id']);
    }

    /**
     * There's one non primary address and one primary address.
     * We delete the non primary address. The customer's address is unchanged.
     */
    public function testDeleteNonPrimaryAddress()
    {
        $nonPrimaryAddress = $this->fixtures->create('address');

        $this->fixtures->address->edit($nonPrimaryAddress->getId(), ['primary' => false]);

        $primaryAddress = $this->fixtures->create('address');

        //$this->fixtures->customer->edit('100000customer', ['shipping_address_id' => $primaryAddress->getId()]);

        $requestContent = $this->getRequestContentForDeleteAddress($nonPrimaryAddress->getPublicId());

        $this->startTest($requestContent);

        //$customer = $this->getEntityById('customer', '100000customer', true);

        //$this->assertEquals($primaryAddress->getId(), $customer['shipping_address_id']);
    }

    /**
     * There's one primary address. We delete it. The customer's address is now null.
     */
    public function testDeletePrimaryAddressWithNoSwitch()
    {
        $primaryAddress = $this->fixtures->create('address');

        //$this->fixtures->customer->edit('100000customer', ['shipping_address_id' => $primaryAddress->getId()]);

        $requestContent = $this->getRequestContentForDeleteAddress($primaryAddress->getPublicId());

        $this->startTest($requestContent);

        //$customer = $this->getEntityById('customer', '100000customer', true);

        //$this->assertNull($customer['shipping_address_id']);
    }

    public function testDeletePrimaryAddressWithSwitch()
    {
        $currentTime = time();

        $primaryAddressOne = $this->fixtures->create('address');
        $this->fixtures->address->edit(
            $primaryAddressOne->getId(),
            ['primary' => false, 'created_at' => $currentTime]);

        $primaryAddressTwo = $this->fixtures->create('address');
        $this->fixtures->address->edit(
            $primaryAddressTwo->getId(),
            ['primary' => false, 'created_at' => $currentTime + 1]);

        $primaryAddressThree = $this->fixtures->create('address');
        $this->fixtures->address->edit(
            $primaryAddressThree->getId(),
            ['created_at' => $currentTime + 2]);
        //$this->fixtures->customer->edit('100000customer', ['shipping_address_id' => $primaryAddressThree->getId()]);

        $primaryAddressFour = $this->fixtures->create('address');
        $this->fixtures->address->edit(
            $primaryAddressFour->getId(),
            ['primary' => false, 'created_at' => $currentTime + 3]);

        $requestContent = $this->getRequestContentForDeleteAddress($primaryAddressThree->getPublicId());

        $this->startTest($requestContent);

        //$customer = $this->getEntityById('customer', '100000customer', true);

        //$this->assertEquals($primaryAddressFour->getId(), $customer['shipping_address_id']);
    }

    public function testCreateTwoShippingAddressesForCustomer()
    {
        $this->startTest();

        $this->startTest();
    }

    public function testGetCustomerAddresses()
    {
        $this->fixtures->times(4)->create('address');

        $this->startTest();
    }

    protected function getRequestContentForDeleteAddress($addressPublicId)
    {
        $url = '/customers/cust_100000customer/addresses/' . $addressPublicId;

        return [
            'request'   => [
                'url'       => $url,
                'method'    => 'delete',
                'content'   => []
            ]
        ];
    }

    protected function getRequestContentForSetPrimaryAddress($addressPublicId)
    {
        $url = '/customers/cust_100000customer/addresses/' . $addressPublicId . '/primary';

        return [
            'request'   => [
                'url'       => $url,
                'method'    => 'put',
                'content'   => []
            ]
        ];
    }
}