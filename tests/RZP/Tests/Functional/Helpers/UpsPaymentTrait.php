<?php


namespace RZP\Tests\Functional\Helpers;


use RZP\Models\Payment\Entity;

trait UpsPaymentTrait
{
    protected function makeCallbackForPayment(Entity $entity, bool $success): array
    {
        $gateway = $this->gateway;
        $this->gateway = 'upi_mozart';
        $this->setMockGatewayTrue();
        $this->gateway = $gateway;

        // These gateways are fully live on UPS and Mozart, And in tests the callback response is going to be
        // delivered by mocked UPS, which is not even touching mozart. Thus there is no need to add exact
        // callback response to UPS, only few mandatory fields are required to make pre-process work.
        // NOTE: All fully ramped gateways can follow the same pattern
        $content = json_encode($entity->only(
            [Entity::ID, Entity::AMOUNT, Entity::DESCRIPTION, Entity::GATEWAY, Entity::TERMINAL_ID, Entity::VPA]));
        $response = $this->makeS2SCallbackAndGetContent($content, $this->gateway);
        // This assertion makes sure that gateway is response properly
        $this->assertArrayHasKey('success', $response);
        $this->assertEquals($success, $response['success']);

        return $response;
    }
}
