<?php

namespace Functional\P2p\Service\UpiAxis\Mandate;

use Carbon\Carbon;
use RZP\Models\P2p\Mandate\Entity;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Mandate\UpiMandate;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;

/**
 * Class MandateTest
 *
 * @package Functional\P2p\Service\UpiAxis\Mandate
 */
class MandateTest extends TestCase
{
    /**
     * Test incoming mandate collect request from gateway.
     */
    public function testIncomingCollect()
    {
        $helper = $this->getMandateHelper();

        $gatewayMandateId = str_random(35);

        $callback = [
            Fields::AMOUNT                  => '1.00',
            Fields::AMOUNT_RULE             => 'EXACT',
            Fields::MANDATE_TYPE            => 'CREATE',
            Fields::PAYER_VPA               => $this->fixtures->vpa->getAddress(),
            Fields::GATEWAY_MANDATE_ID      => $gatewayMandateId,
            Fields::MERCHANT_CUSTOMER_ID    => $this->fixtures->deviceToken(self::DEVICE_1)
                                                              ->getGatewayData()[Fields::MERCHANT_CUSTOMER_ID],
            Fields::BLOCK_FUND              => true,
            Fields::GATEWAY_REFERENCE_ID    => '809323430413',
            Fields::IS_MARKED_SPAM          => 'false',
            Fields::IS_VERIFIED_PAYEE       => 'true',
            Fields::INITIATED_BY            => 'PAYEE',
            Fields::MANDATE_NAME            => 'merchant mandate',
            Fields::MANDATE_TIMESTAMP       => '2020-06-01T15:40:42+05:30',
            Fields::MERCHANT_CHANNEL_ID     => 'BANK',
            Fields::MERCHANT_ID             => 'BANK',
            Fields::ORG_MANDATE_ID          => 'BJJMsleiuryufhuhsoisdjfadb48003sdaa0',
            Fields::PAYEE_MCC               => '4121',
            Fields::PAYEE_NAME              => 'BANKTEST',
            Fields::PAYEE_VPA               => 'test@bank',
            Fields::PAYER_REVOCABLE         => 'true',
            Fields::RECURRENCE_PATTERN      => 'MONTHLY',
            Fields::RECURRENCE_RULE         => 'ON',
            Fields::RECURRENCE_VALUE        => '5',
            Fields::REF_URL                 => 'https://www.abcxyz.com/',
            Fields::REMARKS                 => 'Sample Remarks',
            Fields::ROLE                    => 'PAYER',
            Fields::SHARE_TO_PAYEE          => 'true',
            Fields::TRANSACTION_TYPE        => 'UPI_MANDATE',
            Fields::TYPE                    => 'CUSTOMER_INCOMING_MANDATE_CREATE_REQUEST_RECEIVED',
            Fields::UMN                     => 'uniqueMandateNumber@bank',
            Fields::VALIDITY_END            => '2020/06/05',
            Fields::VALIDITY_START          => '2020/06/04',
        ];

        $this->mockSdk()->setCallback('CUSTOMER_INCOMING_MANDATE_CREATE_REQUEST_RECEIVED', $callback);

        $request = $this->mockSdk()->callback();

        $response = $helper->callback($this->gateway, $request);

        $this->assertTrue($response['success']);

        $expectedMandateSubset = [
            Entity::AMOUNT          => round(floatval($callback[Fields::AMOUNT]) * 100),
            Entity::AMOUNT_RULE     => $callback[Fields::AMOUNT_RULE],
            Entity::RECURRING_TYPE  => $callback[Fields::RECURRENCE_PATTERN],
            Entity::RECURRING_RULE  => $callback[Fields::RECURRENCE_RULE],
            Entity::RECURRING_VALUE => intval($callback[Fields::RECURRENCE_VALUE]),
            Entity::START_DATE      => Carbon::parse($callback[Fields::VALIDITY_START])->getTimestamp(),
            Entity::END_DATE        => Carbon::parse($callback[Fields::VALIDITY_END])->getTimestamp(),
        ];

        $expectedUpiMandateSubset = [
            UpiMandate\Entity::NETWORK_TRANSACTION_ID => $callback[Fields::GATEWAY_MANDATE_ID],
        ];

        $collection = $helper->fetchAll();

        $this->assertSame(1, $collection['count']);

        $actualMandate = array_only($collection['items'][0], array_keys($expectedMandateSubset));
        $this->assertEquals($expectedMandateSubset, $actualMandate);

        $actualUpiMandate = array_only($collection['items'][0][Entity::UPI], array_keys($expectedUpiMandateSubset));
        $this->assertEquals($expectedUpiMandateSubset, $actualUpiMandate);
    }
}
