<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Mandate;

use RZP\Exception\RuntimeException;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;

class MandateTest extends TestCase
{
    use TransactionTrait;

    public function testFetchAll()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage('Not implemented, Core Implementation is on the way');

        $helper->fetchAll();
    }

    public function testFetch()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage('Not implemented, Core Implementation is on the way');

        $response = $helper->fetch("20");
    }

    public function testInitiateAuthorize()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage('Not implemented, Core Implementation is on the way');

        $response = $helper->initiateAuthorize("20", []);
    }

    public function testInitiateReject()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage('Not implemented, Core Implementation is on the way');

        $response = $helper->initiateReject("20", []);
    }

    public function testInitiatePause()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage('Not implemented, Core Implementation is on the way');

        $response = $helper->initiatePause("20", []);
    }

    public function testInitiateUnPause()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage('Not implemented, Core Implementation is on the way');

        $response = $helper->initiateUnPause("20", []);
    }

    public function testInitiateRevoke()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage('Not implemented, Core Implementation is on the way');

        $response = $helper->initiateRevoke("20", []);
    }

    public function testAuthorizeMandate()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage('Not implemented, Core Implementation is on the way');

        $response = $helper->authorizeMandate("20", []);
    }

    public function testRejectMandate()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage('Not implemented, Core Implementation is on the way');

        $response = $helper->rejectMandate("20", []);
    }


    public function testPauseMandate()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage('Not implemented, Core Implementation is on the way');

        $response = $helper->pauseMandate("20", []);
    }

    public function testUnPauseMandate()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage('Not implemented, Core Implementation is on the way');

        $response = $helper->unpauseMandate("20", []);

    }

    public function testRevokeMandate()
    {
        $helper = $this->getMandateHelper();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage('Not implemented, Core Implementation is on the way');

        $response = $helper->revokeMandate("20", []);
    }
}
