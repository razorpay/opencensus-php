<?php

namespace RZP\Tests\Unit\Gateway\BilldeskSiHub;

use RZP\Tests\TestCase;

use RZP\Models\CardMandate\MandateHubs\Mandate;
use RZP\Models\CardMandate\MandateHubs\BillDeskSIHub;

class SIHubRegistrationPayloadTest extends TestCase
{
    public function testGetMandateFromSIHubResponse()
    {
        $response = [
            'id' => 'WjH78lJQ',
        ];

        $mandate = BillDeskSIHub\BillDeskSIHub::getMandateFromSIHubResponse($response, null);

        $this->assertInstanceOf(Mandate::class, $mandate);
        $this->assertEquals('WjH78lJQ', $mandate->getAttribute(Mandate::MANDATE_ID));
    }
}

