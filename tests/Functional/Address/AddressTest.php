<?php

namespace RZP\Tests\Functional\Address;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use Mockery;

class AddressTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
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
        $this->markTestSkipped("SuperCheckout requires more than 3 addresses");
        $this->fixtures->times(3)->create('address');

        $this->startTest();
    }

    public function testCreateShippingAddressWithPrimaryFalse()
    {
        $this->startTest();
    }

    public function testCreateShippingAddressPrimarySwitch()
    {
        $this->fixtures->create('address');

        $this->startTest();
    }

    public function testCreateShippingAddressNoPrimarySwitch()
    {
        $this->fixtures->create('address');

        $this->startTest();
    }

    public function testCreateWithSourceDetails()
    {
        $this->startTest();
    }

    public function testCreateWithoutSourceDetails()
    {
        $this->startTest();
    }

    public function testSetPrimaryAddressForNonPrimaryAddressWithNoSwitch()
    {
        $address = $this->fixtures->create('address');

        $this->fixtures->address->edit($address->getId(), ['primary' => false]);

        $requestContent = $this->getRequestContentForSetPrimaryAddress($address->getPublicId());

        $this->startTest($requestContent);
    }

    public function testSetPrimaryAddressForNonPrimaryAddressWithSwitch()
    {
        $this->fixtures->create('address');

        $nonPrimaryAddress = $this->fixtures->create('address');

        $this->fixtures->address->edit($nonPrimaryAddress->getId(), ['primary' => false]);

        $requestContent = $this->getRequestContentForSetPrimaryAddress($nonPrimaryAddress->getPublicId());

        $this->startTest($requestContent);
    }

    /**
     * There's one non primary address and one primary address.
     * We delete the non primary address. The customer's address is unchanged.
     */
    public function testDeleteNonPrimaryAddress()
    {
        $nonPrimaryAddress = $this->fixtures->create('address');

        $this->fixtures->address->edit($nonPrimaryAddress->getId(), ['primary' => false]);

        $this->fixtures->create('address');

        $requestContent = $this->getRequestContentForDeleteAddress($nonPrimaryAddress->getPublicId());

        $this->startTest($requestContent);
    }

    /**
     * There's one primary address. We delete it. The customer's address is now null.
     */
    public function testDeletePrimaryAddressWithNoSwitch()
    {
        $primaryAddress = $this->fixtures->create('address');

        $requestContent = $this->getRequestContentForDeleteAddress($primaryAddress->getPublicId());

        $this->startTest($requestContent);
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

        $primaryAddressFour = $this->fixtures->create('address');
        $this->fixtures->address->edit(
            $primaryAddressFour->getId(),
            ['primary' => false, 'created_at' => $currentTime + 3]);

        $requestContent = $this->getRequestContentForDeleteAddress($primaryAddressThree->getPublicId());

        $this->startTest($requestContent);
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
