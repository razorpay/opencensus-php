<?php

namespace RZP\Tests\P2p\Service\UpiAxisOlive\Complaint;

use RZP\Models\P2p\Complaint\Entity;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Gateway\P2p\Upi\AxisOlive\Fields;
use RZP\Tests\P2p\Service\UpiAxisOlive\TestCase;
use RZP\Gateway\P2p\Upi\AxisOlive\Actions\TurboAction;

class ComplaintTest extends TestCase
{
    use TestsWebhookEvents;
    public function testRaiseComplaintCallBack()
    {
        $this->expectWebhookEvent(
            'merchant.complaint.notification',
            function(array $event)
            {
                $this->assertArraySubset([
                     Fields::CRN                => "UPI2005138765432",
                     Entity::GATEWAY_DATA    =>[
                         Fields::GATEWAY_TRANSACTION_ID => "AXI4a69d250abe6433899c2f5a081000033882",
                         Fields::RRN => "013100747891",
                     ],
                 ], $event['payload']);
            });
        $helper = $this->getClientHelper();

        $helper->withSchemaValidated();

        $this->mockCallback()->setCallback('REQUEST_COMPLAINT_CALLBACK', [
            Fields::ORG_TXN_ID         => "AXI4a69d250abe6433899c2f5a081000033882",
            Fields::ORG_RRN            => "013100747891",
            Fields::ORG_TXN_DATE       => "2020-05-11T00:00:41+05:30",
            Fields::REF_ADJ_FLAG       => "RRC",
            Fields::REF_ADJ_CODE       => "501",
            Fields::REF_ADJ_AMOUNT     => "500.00",
            Fields::REF_ADJ_REMARKS    => "RET Issued",
            Fields::CRN                => "UPI2005138765432",
            Fields::REF_ADJ_TS         => "2020-05-11T00:00:41+05:30",
            Fields::REF_ADJ_REF_ID     => "P1705110000338829745321",
        ]);

        $request = $this->mockCallback()->initiateCallback();

        $response = $helper->turboCallback($this->gateway, $request);

        $this->assertTrue($response['success']);
    }

    public function testNotificationComplaintCallBack()
    {
        $this->expectWebhookEvent(
            'merchant.complaint.notification',
            function(array $event)
            {
                $this->assertArraySubset([
                         Fields::CRN                => "UPI2005138765432",
                         Entity::GATEWAY_DATA       => [
                             Fields::GATEWAY_TRANSACTION_ID => "AXI4a69d250abe6433899c2f5a081000033882",
                             Fields::RRN => "013100747891",
                             Fields::TYPE => "Complaint",
                         ],
                 ], $event['payload']);
            });
        $helper = $this->getClientHelper();

        $helper->withSchemaValidated();

        $this->mockCallback()->setCallback('NOTIFICATION_COMPLAINT_CALLBACK', [
            Fields::INIT_MODE          => "U1",
            Fields::SUBTYPE            => "BENEFICIARY",
            Fields::ORG_TXN_ID         => "AXI4a69d250abe6433899c2f5a081000033882",
            Fields::ORG_RRN            => "013100747891",
            Fields::ORG_TXN_DATE       => "2020-05-11T00:00:41+05:30",
            Fields::REQ_ADJ_FLAG       => "RRC",
            Fields::REQ_ADJ_CODE       => "501",
            Fields::REQ_ADJ_AMOUNT     => "500.00",
            Fields::REF_ADJ_REMARKS    => "RET Issued",
            Fields::CRN                => "UPI2005138765432",
            Fields::REF_ADJ_TS         => "2020-05-11T00:00:41+05:30",
            Fields::REF_ADJ_REF_ID     => "P1705110000338829745321",
            Fields::TYPE               => 'Complaint',
        ]);

        $request = $this->mockCallback()->initiateCallback();

        $response = $helper->turboCallback($this->gateway, $request);

        $this->assertTrue($response['success']);
    }
}
