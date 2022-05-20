<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Mandate;

use Carbon\Carbon;
use RZP\Models\Base\PublicEntity;
use RZP\Models\P2p\Mandate\Entity;
use RZP\Models\P2p\Mandate\Status;
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
            Fields::TYPE             => UpiAction::INCOMING_MANDATE_CREATE,
            Fields::AMOUNT           => 100,
            Fields::AMOUNT_RULE      => 'MAX',
            Fields::PAYER_VPA        => $this->fixtures->vpa(Fixtures::DEVICE_1)->getAddress(),
            Fields::PAYEE_VPA        => 'username@randompsp',
            Fields::VALIDITY_START   => Carbon::now()->getTimestamp(),
            Fields::VALIDITY_END     => Carbon::now()->addDays(365)->getTimestamp(),
            Fields::TRANSACTION_NOTE => 'UPI',
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

        $helper->createMandate($this->gateway);

        $response = $helper->fetchAll();

        $mandateId = $response['items'][0]['id'];

        $request = $helper->initiateReject($mandateId, []);

        $this->assertRequestResponse(
            'redirect',
            ['time' => $this->fixtures->device->getCreatedAt()],
            $this->expectedCallback(
                Requests::P2P_CUSTOMER_MANDATE_AUTHORIZE,
                [$mandateId],
                ['f' => 'initiateReject']),
            $request);
    }

    public function testInitiatePause()
    {
        $helper = $this->getMandateHelper();

        $helper->createMandate($this->gateway);

        $response = $helper->fetchAll();

        $mandateId = $response['items'][0]['id'];

        $lastMandate[Entity::PAUSE_START] = 1646721840;
        $lastMandate[Entity::PAUSE_END]   = 1646921840;

        $request = $helper->initiatePause(substr($mandateId, 5), $lastMandate);

        $this->handleNpciClRequest(
            $request,
            'getCredential',
            $this->expectedCallback(Requests::P2P_CUSTOMER_MANDATE_PAUSE, [$mandateId]),
            [],
            null);

    }

    public function testInitiateUnPause()
    {
        $helper = $this->getMandateHelper();

        $helper->createMandate($this->gateway);

        $response = $helper->fetchAll();

        $mandateId = $response['items'][0]['id'];

        $lastMandate[Entity::PAUSE_START] = 1646721840;
        $lastMandate[Entity::PAUSE_END]   = 1646921840;

        $request = $helper->initiatePause($mandateId, $lastMandate);

        $helper->pauseMandate($request['callback'], []);

        $request = $helper->initiateUnPause($mandateId, []);

        $this->handleNpciClRequest(
            $request,
            'getCredential',
            $this->expectedCallback(Requests::P2P_CUSTOMER_MANDATE_UNPAUSE, [$mandateId]),
            [],
            null);
    }

    public function testInitiateRevoke()
    {
        $helper = $this->getMandateHelper();

        $helper->createMandate($this->gateway);

        $response = $helper->fetchAll();

        $mandateId = $response['items'][0]['id'];

        $request = $helper->initiateRevoke($mandateId, []);

        $this->assertRequestResponse(
            'redirect',
            ['time' => $this->fixtures->device->getCreatedAt()],
            $this->expectedCallback(
                Requests::P2P_CUSTOMER_MANDATE_REVOKE,
                [$mandateId],
                ['f' => 'initiateRevoke']),
            $request);

    }

    public function testAuthorizeMandate()
    {
        $helper = $this->getMandateHelper();

        $helper->createMandate($this->gateway);

        $lastMandate = $this->getPspxLastMandate(Fixtures::DEVICE_1);

        $mandateId = $lastMandate[Entity::ID];

        $amount = $lastMandate[Entity::AMOUNT];

        $request = $helper->initiateAuthorize($mandateId, []);

        $response = $helper->authorizeMandate($request['callback'], []);

        $this->assertArraySubset([
            Entity::STATUS => Status::APPROVED,
            Entity::AMOUNT => $amount,
        ], $response);
    }

    public function testRejectMandate()
    {
        $helper = $this->getMandateHelper();

        $helper->createMandate($this->gateway);

        $lastMandate = $this->getPspxLastMandate(Fixtures::DEVICE_1);

        $mandateId = $lastMandate[Entity::ID];

        $amount = $lastMandate[Entity::AMOUNT];

        $request = $helper->initiateReject($mandateId, []);

        $this->assertRequestResponse(
            'redirect',
            ['time' => $this->fixtures->device->getCreatedAt()],
            $this->expectedCallback(
                Requests::P2P_CUSTOMER_MANDATE_AUTHORIZE,
                [Entity::getSignedId($mandateId)],
                ['f' => 'initiateReject']),
            $request);

        $response = $helper->rejectMandate($request['callback'], []);

        $this->assertArraySubset([
         Entity::STATUS => Status::REJECTED,
         Entity::AMOUNT => $amount,
         ], $response);
    }


    public function testPauseMandate()
    {
        $helper = $this->getMandateHelper();

        $helper->createMandate($this->gateway);

        $response = $helper->fetchAll();

        $mandateId = $response['items'][0]['id'];

        $lastMandate[Entity::PAUSE_START] = 1646721840;
        $lastMandate[Entity::PAUSE_END]   = 1646921840;

        $request = $helper->initiatePause($mandateId, $lastMandate);

        $response = $helper->pauseMandate($request['callback'], []);

        $this->assertArraySubset([
         'status'    => 'paused',
         ], $response);

    }

    public function testUnPauseMandate()
    {
        $helper = $this->getMandateHelper();

        $helper->createMandate($this->gateway);

        $response = $helper->fetchAll();

        $mandateId = $response['items'][0]['id'];

        $lastMandate[Entity::PAUSE_START] = 1646721840;
        $lastMandate[Entity::PAUSE_END]   = 1646921840;

        $request = $helper->initiatePause($mandateId, $lastMandate);

        $helper->pauseMandate($request['callback'], []);

        $request = $helper->initiateUnPause($mandateId, []);

        $response = $helper->unpauseMandate($request['callback'], []);

        $this->assertArraySubset([
             'status'    => 'approved',
            ], $response);
    }

    public function testRevokeMandate()
    {
        $helper = $this->getMandateHelper();

        $helper->createMandate($this->gateway);

        $response = $helper->fetchAll();

        $mandateId = $response['items'][0]['id'];

        $request = $helper->initiateRevoke($mandateId, []);

        $response = $helper->revokeMandate($request['callback'], []);

        $this->assertArraySubset([
            'status'    => 'revoked',
         ], $response);
    }
}
