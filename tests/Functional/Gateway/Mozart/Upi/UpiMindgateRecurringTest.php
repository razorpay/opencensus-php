<?php

namespace RZP\Tests\Functional\Gateway\Mozart\Upi;

class UpiMindgateRecurringTest extends UpiInitialRecurringTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->terminal = $this->fixtures->create('terminal:dedicated_mindgate_recurring_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->payment = $this->getDefaultUpiRecurringPaymentArray();

        $this->setMockGatewayTrue();

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'upi_autopay_hybrid_encryption', 'on');
        });
    }

    public function testRevokeMandate()
    {
        $this->markTestSkipped();
    }

    public function testPauseMandate()
    {
        $this->markTestSkipped();
    }

    public function testRevokeMandateViaCallback()
    {
        $this->markTestSkipped();
    }

    // Skipping for now since mindgate is not set up completely
    // TODO: Revisit this test case during Mindgate recurring implementation
    public function testSequenceNumberOnFirstDebitRetry()
    {
        $this->markTestSkipped();
    }

    public function testRecurringMandateCreateOnDark(&$requestSentToDark = false)
    {
        $this->mockServerContentFunction(function (&$content, $action) use (&$requestSentToDark)
        {
            if ($action === 'redirectToDark')
            {
                $requestSentToDark = true;
            }
        }, 'upi_mindgate');

        parent::testRecurringMandateCreateOnDark($requestSentToDark);
    }
}
