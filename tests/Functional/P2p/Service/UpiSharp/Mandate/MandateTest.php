<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Mandate;

use RZP\Models\P2p\Mandate\Entity;
use RZP\Exception\RuntimeException;
use RZP\Exception\BadRequestException;
use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;

class MandateTest extends TestCase
{
    use TransactionTrait;

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

    public function testInitiateAuthorize()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('The id provided does not exist');

        $response = $helper->initiateAuthorize('IlS1WhGL84jAoR', []);
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
