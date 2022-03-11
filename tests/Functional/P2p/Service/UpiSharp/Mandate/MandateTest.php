<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Mandate;

use Carbon\Carbon;
use RZP\Models\Base\PublicEntity;
use RZP\Models\P2p\Mandate\Entity;
use RZP\Gateway\P2p\Upi\Sharp\Fields;
use RZP\Exception\BadRequestException;
use RZP\Http\Controllers\P2p\Requests;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;
use RZP\Gateway\P2p\Upi\Sharp\Actions\UpiAction;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;
use RZP\Tests\P2p\Service\Base\Traits\MandateTrait;

class MandateTest extends TestCase
{
    use MandateTrait;

    public function testFetchAll()
    {
        $helper = $this->getMandateHelper();

        $response = $helper->fetchAll();

        $this->assertArrayHasKey(Entity::ENTITY, $response);

        $this->assertArrayHasKey('count', $response);

        $this->assertArrayHasKey('items', $response);
    }

    public function testFetch()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('The id provided does not exist');

        $response = $helper->fetch('IlS1WhGL84jAoR');

        $this->assertIsArray($response);

        // Assert response has id key, and it is not empty
        $this->assertArrayHasKey(Entity::ID, $response);
    }

    /**
     * Test incoming mandate collect request from gateway.
     */
    public function testIncomingCollect()
    {
        $helper = $this->getMandateHelper();

        $request = [
            Fields::TYPE                    => UpiAction::INCOMING_MANDATE_CREATE,
            Fields::AMOUNT                  => 100,
            Fields::AMOUNT_RULE             => 'MAX',
            Fields::PAYER_VPA               => $this->fixtures->vpa(Fixtures::DEVICE_1)->getAddress(),
            Fields::PAYEE_VPA               => 'username@randompsp',
            Fields::VALIDITY_START          => Carbon::now()->getTimestamp(),
            Fields::VALIDITY_END            => Carbon::now()->addDays(365)->getTimestamp(),
            Fields::TRANSACTION_NOTE        => 'UPI',
        ];

        $content = [
            'content' => json_encode($request)
        ];

        $response = $helper->callback($this->gateway, $content);
        $this->assertTrue($response['success']);

        $lastMandate = $this->getPspxLastMandate(Fixtures::DEVICE_1);

        $expectedMandateSubset = [
            Entity::AMOUNT      => $request[Fields::AMOUNT],
            Entity::AMOUNT_RULE => $request[Fields::AMOUNT_RULE],
            Entity::START_DATE  => $request[Fields::VALIDITY_START],
            Entity::END_DATE    => $request[Fields::VALIDITY_END],
            Entity::DESCRIPTION => $request[Fields::TRANSACTION_NOTE],
        ];

        $actualMandateSubset = array_only($lastMandate, array_keys($expectedMandateSubset));

        $this->assertEquals($expectedMandateSubset, $actualMandateSubset);

        $this->assertTrue(PublicEntity::verifyUniqueId($lastMandate[Entity::ID], false));
        $this->assertTrue(PublicEntity::verifyUniqueId($lastMandate[Entity::PAYER_ID], false));
        $this->assertTrue(PublicEntity::verifyUniqueId($lastMandate[Entity::PAYEE_ID], false));
    }

    public function testInitiateAuthorize()
    {
        $helper = $this->getMandateHelper();

        $helper->createMandate($this->gateway);

        $response = $helper->fetchAll();

        $mandateId = $response['items'][0]['id'];

        $request = $helper->initiateAuthorize(substr($mandateId, 5), []);

        $this->handleNpciClRequest(
            $request,
            'getCredential',
            $this->expectedCallback(Requests::P2P_CUSTOMER_MANDATE_AUTHORIZE, [$mandateId]),
            [],
            null);
    }

    public function testInitiateReject()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('The id provided does not exist');

        $response = $helper->initiateReject('IlS1WhGL84jAoR', []);
    }

    public function testInitiatePause()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('The id provided does not exist');

        $response = $helper->initiatePause('IlS1WhGL84jAoR', []);
    }

    public function testInitiateUnPause()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('The id provided does not exist');

        $response = $helper->initiateUnPause('IlS1WhGL84jAoR', []);
    }

    public function testInitiateRevoke()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('The id provided does not exist');

        $response = $helper->initiateRevoke('IlS1WhGL84jAoR', []);
    }

    public function testAuthorizeMandate()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('The id provided does not exist');

        $response = $helper->authorizeMandate('IlS1WhGL84jAoR', []);
    }

    public function testRejectMandate()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('The id provided does not exist');

        $response = $helper->rejectMandate('IlS1WhGL84jAoR', []);
    }


    public function testPauseMandate()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('The id provided does not exist');

        $response = $helper->pauseMandate('IlS1WhGL84jAoR', []);
    }

    public function testUnPauseMandate()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('The id provided does not exist');

        $response = $helper->unpauseMandate('IlS1WhGL84jAoR', []);

    }

    public function testRevokeMandate()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('The id provided does not exist');

        $response = $helper->revokeMandate('IlS1WhGL84jAoR', []);
    }
}
