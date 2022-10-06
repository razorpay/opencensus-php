<?php

namespace Functional\P2p\Service\UpiAxis\Mandate;

use Carbon\Carbon;
use RZP\Models\P2p\Mandate\Status;
use RZP\Models\P2p\Mandate\Entity;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Mandate\UpiMandate;
use RZP\Models\P2p\Mandate\Patch\Action;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Gateway\P2p\Upi\Axis\Actions\UpiAction;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;
use RZP\Tests\P2p\Service\Base\Traits\MandateTrait;
use RZP\Tests\P2p\Service\Base\Traits\MetricsTrait;
use RZP\Models\P2p\Mandate\Patch\Entity as PatchEntity;

/**
 * Class MandateTest
 *
 * @package Functional\P2p\Service\UpiAxis\Mandate
 */
class MandateTest extends TestCase
{
    use MandateTrait;
    use MetricsTrait;

    /**
     * Test incoming mandate collect request from gateway.
     */
    public function testIncomingCollect()
    {
        $helper = $this->getMandateHelper();

        $gatewayMandateId = str_random(35);

        $callback = [
            Fields::NAME                    => 'test mandates',
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
            Fields::UMN                     => 'b3cecfd8c7654c66af13fc439aca1256@bajaj',
            Fields::VALIDITY_END            => Carbon::now()->addDays(365)->getTimestamp(),
            Fields::VALIDITY_START          => Carbon::now()->getTimestamp(),
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

        $mandate = $this->fixtures->getDbLastMandate();

        $actualMandate = array_only($mandate->toArrayPublic(), array_keys($expectedMandateSubset));
        $this->assertEquals($expectedMandateSubset, $actualMandate);

        $actualUpiMandate = array_only($mandate->toArrayPublic()[Entity::UPI], array_keys($expectedUpiMandateSubset));
        $this->assertEquals($expectedUpiMandateSubset, $actualUpiMandate);

        $patch = $this->fixtures->getDbLastPatch()->toArray();

        $this->assertEquals($patch[PatchEntity::MANDATE_ID],$mandate->toArray()[Entity::ID]);
    }

    /**
     * Test incoming mandate collect request from gateway.
     */
    public function testIncomingUpdate()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate();

        $request = $helper->initiateAuthorize($lastMandate[Entity::ID], []);

        $content = $this->handleSdkRequest($request);

        $helper->authorizeMandate($request['callback'], $content);

        $patch = $this ->fixtures->getDbLastPatch()->toArray();

        $lastMandate = $this->fixtures->getDbLastMandate();

        $this->mockSdk()->setCallback('CUSTOMER_INCOMING_MANDATE_UPDATE_REQUEST_RECEIVED', [
            Fields::ACCOUNT_REFERENCE_ID                  => $lastMandate[Entity::BANK_ACCOUNT_ID],
            Fields::UMN                                   => $lastMandate[Entity::UMN],
            Fields::MANDATE_TYPE                          => 'UPDATE',
            Fields::MANDATE_ID                            => $lastMandate[Entity::ID],
            Fields::TYPE                                  => UpiAction::INCOMING_MANDATE_UPDATE,
            Fields::AMOUNT                                => 90,
            Fields::AMOUNT_RULE                           => 'MAX',
            Fields::PAYER_VPA                             => $this->fixtures->vpa->getAddress(),
            Fields::PAYEE_VPA                             => 'username@randompsp',
            Fields::VALIDITY_START                        => Carbon::now()->getTimestamp(),
            Fields::VALIDITY_END                          => Carbon::now()->addDays(365)->getTimestamp()]);

        $request = $this->mockSdk()->callback();

        $response = $helper->callback($this->gateway, $request);

        $this->assertTrue($response['success']);

        $lastMandate = $this->fixtures->getDbLastMandate();;

        $this->assertEquals($lastMandate[Entity::AMOUNT], 9000);

        $patch = $this ->fixtures->getDbLastPatch()->toArray();

        $this->assertEquals($patch[PatchEntity::MANDATE_ID], $lastMandate->getId());
    }

    /**
     * Test incoming mandate collect request from gateway.
     */
    public function testIncomingPause()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate();

        $request = $helper->initiateAuthorize($lastMandate[Entity::ID], []);

        $content = $this->handleSdkRequest($request);

        $helper->authorizeMandate($request['callback'], $content);

        $gatewayMandateId = str_random(35);

        $timeNow = Carbon::now()->getTimestamp();

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
            Fields::TYPE                    => 'CUSTOMER_INCOMING_MANDATE_PAUSE_REQUEST_RECEIVED',
            Fields::UMN                     => $lastMandate->toArray()[Entity::UMN],
            Fields::VALIDITY_END            => Carbon::now()->addDays(365)->getTimestamp(),
            Fields::VALIDITY_START          => $timeNow,
            Fields::PAUSE_START             => $timeNow,
            Fields::PAUSE_END               => Carbon::now()->addDays(365)->getTimestamp(),
        ];

        $this->mockSdk()->setCallback('CUSTOMER_INCOMING_MANDATE_PAUSE_REQUEST_RECEIVED', $callback);

        $request = $this->mockSdk()->callback();

        $response = $helper->callback($this->gateway, $request);

        $this->assertTrue($response['success']);

        $lastMandate = $this->fixtures->getDbLastMandate();

        $this->assertEquals($lastMandate[Entity::PAUSE_START], $timeNow);

        $patch = $this ->fixtures->getDbLastPatch()->toArray();

        $this->assertEquals($patch[PatchEntity::MANDATE_ID], $lastMandate->getId());
    }

    public function testMandateStatusUpdate()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate();

        $request = $helper->initiateAuthorize($lastMandate[Entity::ID], []);

        $content = $this->handleSdkRequest($request);

        $helper->authorizeMandate($request['callback'], $content);

        $gatewayMandateId = str_random(35);

        $callback = [
            Fields::ACCOUNT_REFERENCE_ID    => $lastMandate[Entity::BANK_ACCOUNT_ID],
            Fields::AMOUNT                  => '1.00',
            Fields::AMOUNT_RULE             => 'EXACT',
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
            Fields::MANDATE_TYPE            => 'PAUSE',
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
            Fields::TYPE                    => 'MANDATE_STATUS_UPDATE',
            Fields::UMN                     => $lastMandate->toArray()[Fields::UMN],
            Fields::VALIDITY_END            => Carbon::now()->addDays(365)->getTimestamp(),
            Fields::VALIDITY_START          => Carbon::now()->getTimestamp(),
            Fields::PAUSE_START             => Carbon::now()->getTimestamp(),
            Fields::PAUSE_END               => Carbon::now()->addDays(365)->getTimestamp(),
        ];

        $this->mockSdk()->setCallback('MANDATE_STATUS_UPDATE', $callback);

        $request = $this->mockSdk()->callback();

        $response = $helper->callback($this->gateway, $request);

        $this->assertTrue($response['success']);

        $lastMandate = $this->fixtures->getDbLastMandate();

        $this->assertEquals($lastMandate[Entity::STATUS],Status::PAUSED);

        $patch = $this ->fixtures->getDbLastPatch()->toArray();

        $this->assertEquals($patch[PatchEntity::MANDATE_ID], $lastMandate->getId());
    }


    public function testFetchAll()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);


        $collection = $helper->fetchAll([
                        'expand'    => ['payer', 'payee']
                      ]);

        $this->assertCollection($collection, 1, [
            [
                'status'    => 'requested',
                'type'      => 'collect',
                'flow'      => 'debit',
            ],
        ]);
    }

    public function testFetch()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate();

        $mandate = $helper->fetch($lastMandate[Entity::ID]);

        $this->assertStringContainsString($mandate[Entity::STATUS], Status::REQUESTED);
    }

    public function testInitiateAuthorize()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate();

        $request = $helper->initiateAuthorize($lastMandate[Entity::ID], []);

        $this->assertStringContainsString($lastMandate[Entity::ID], $request['callback']);
    }


    public function testAuthorize()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate();

        $request = $helper->initiateAuthorize($lastMandate[Entity::ID], []);

        $content = $this->handleSdkRequest($request);

        $response = $helper->authorizeMandate($request['callback'], $content);

        $this->assertArraySubset([
             Entity::STATUS => Status::APPROVED,
         ], $response);

        $patch = $this ->fixtures->getDbLastPatch()->toArray();

        $this->assertEquals($patch[PatchEntity::MANDATE_ID], $lastMandate->getId());
    }

    public function testInitiateReject()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate();

        $request = $helper->initiateReject($lastMandate[Entity::ID], []);

        $this->assertStringContainsString($lastMandate[Entity::ID],$request['callback']);
    }

    public function testReject()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate();

        $request = $helper->initiateReject($lastMandate[Entity::ID], []);

        $content = $this->handleSdkRequest($request);

        $response = $helper->authorizeMandate($request['callback'], $content);

        $this->assertArraySubset([
                Entity::STATUS => Status::REJECTED,
         ], $response);

        $patch = $this ->fixtures->getDbLastPatch();

        $this->assertEquals($patch[PatchEntity::MANDATE_ID], $lastMandate->toArray()[Entity::ID]);
    }

    public function testInitiatePause()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate()->toArray();

        $request = $helper->initiateAuthorize($lastMandate[Entity::ID], []);

        $content = $this->handleSdkRequest($request);

        $response = $helper->authorizeMandate($request['callback'], $content);

        $this->assertArraySubset([Entity::STATUS => Status::APPROVED], $response);

        $lastMandate[Entity::PAUSE_START] = Carbon::now()->getTimestamp();
        $lastMandate[Entity::PAUSE_END]   = Carbon::now()->getTimestamp();

        $request = $helper->initiatePause($lastMandate[Entity::ID], $lastMandate);

        $this->assertStringContainsString($lastMandate[Entity::ID], $request['callback']);
    }

    public function testPauseMandate()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate()->toArray();

        $request = $helper->initiateAuthorize($lastMandate[Entity::ID], []);

        $content = $this->handleSdkRequest($request);

        $helper->authorizeMandate($request['callback'], $content);

        $lastMandate = $this->fixtures->getDbLastMandate()->toArray();

        $lastMandate[Entity::PAUSE_START] = Carbon::now()->getTimestamp();
        $lastMandate[Entity::PAUSE_END]   = Carbon::now()->getTimestamp();

        $request = $helper->initiatePause($lastMandate[Entity::ID], $lastMandate);

        $this->assertStringContainsString($lastMandate[Entity::ID], $request['callback']);

        $content = $this->handleSdkRequest($request);

        $response = $helper->pauseMandate($request['callback'], $content);

        $this->assertArraySubset([
            Entity::STATUS    => Status::PAUSED,
         ], $response);

        $patch = $this ->fixtures->getDbLastPatch();

        $this->assertEquals($patch[PatchEntity::MANDATE_ID], $lastMandate[Entity::ID]);
    }

    public function testInitiateUnpause()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate()->toArray();

        $request = $helper->initiateAuthorize($lastMandate[Entity::ID], []);

        $content = $this->handleSdkRequest($request);

        $helper->authorizeMandate($request['callback'], $content);

        $lastMandate = $this->fixtures->getDbLastMandate()->toArray();

        $lastMandate[Entity::PAUSE_START] = Carbon::now()->getTimestamp();
        $lastMandate[Entity::PAUSE_END]   = Carbon::now()->getTimestamp();

        $request = $helper->initiatePause($lastMandate[Entity::ID], $lastMandate);

        $this->assertStringContainsString($lastMandate[Entity::ID], $request['callback']);

        $content = $this->handleSdkRequest($request);

        $response = $helper->pauseMandate($request['callback'], $content);

        $this->assertArraySubset([
                 Entity::STATUS    => Status::PAUSED,
                ], $response);

        $request = $helper->initiateUnPause($lastMandate[Entity::ID], $lastMandate);

        $this->assertStringContainsString($lastMandate[Entity::ID], $request['callback']);
    }

    public function testUnpause()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate()->toArray();

        $request = $helper->initiateAuthorize($lastMandate[Entity::ID], []);

        $content = $this->handleSdkRequest($request);

        $helper->authorizeMandate($request['callback'], $content);

        $lastMandate = $this->fixtures->getDbLastMandate()->toArray();

        $lastMandate[Entity::PAUSE_START] = Carbon::now()->getTimestamp();
        $lastMandate[Entity::PAUSE_END]   = Carbon::now()->getTimestamp();

        $request = $helper->initiatePause($lastMandate[Entity::ID], $lastMandate);

        $this->assertStringContainsString($lastMandate[Entity::ID], $request['callback']);

        $content = $this->handleSdkRequest($request);

        $response = $helper->pauseMandate($request['callback'], $content);

        $this->assertArraySubset([Entity::STATUS  => Status::PAUSED], $response);

        $request = $helper->initiateUnPause($lastMandate[Entity::ID], $lastMandate);

        $response = $helper->unpauseMandate($request['callback'], $content);

        $this->assertArraySubset([
                Entity::STATUS   => Status::APPROVED,
         ], $response);

        $patch = $this ->fixtures->getDbLastPatch()->toArray();

        $this->assertEquals($patch[PatchEntity::MANDATE_ID], $lastMandate[Entity::ID]);
    }

    public function testInitiateRevoke()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate()->toArray();

        $request = $helper->initiateAuthorize($lastMandate[Entity::ID], []);

        $content = $this->handleSdkRequest($request);

        $helper->authorizeMandate($request['callback'], $content);

        $request = $helper->initiateRevoke($lastMandate[Entity::ID], []);

        $this->assertStringContainsString($lastMandate[Entity::ID], $request['callback']);
    }

    public function testRevokeMandate()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate()->toArray();

        $request = $helper->initiateAuthorize($lastMandate[Entity::ID], []);

        $content = $this->handleSdkRequest($request);

        $helper->authorizeMandate($request['callback'], $content);

        $request = $helper->initiateRevoke($lastMandate[Entity::ID], []);

        $content = $this->handleSdkRequest($request);

        $response = $helper->revokeMandate($request['callback'], $content);

        $this->assertArraySubset([
                 Entity::STATUS   => Status::REVOKED,
             ], $response);

        $patch = $this ->fixtures->getDbLastPatch()->toArray();

        $this->assertEquals($patch[PatchEntity::MANDATE_ID], $lastMandate[Entity::ID]);
    }

    private function createMandateOnMock($helper, $callback)
    {
        $this->mockSdk()->setCallback('CUSTOMER_INCOMING_MANDATE_CREATE_REQUEST_RECEIVED', $callback);

        $request = $this->mockSdk()->callback();

        $response = $helper->callback($this->gateway, $request);

        $this->assertTrue($response['success']);
    }

    public function testFetchAllStatusesWithPendingState()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);


        $collection = $helper->fetchAll([
            'expand'    => ['payer', 'payee'],
            'response'   =>'pending'
        ]);

        $this->assertCollection($collection, 1, [
            [
                'status'    => 'requested',
                'type'      => 'collect',
                'flow'      => 'debit',
            ],
        ]);
    }

    public function testFetchAllStatusesWithActiveState()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate()->toArray();

        $request = $helper->initiateAuthorize($lastMandate[Entity::ID], []);

        $content = $this->handleSdkRequest($request);

        $helper->authorizeMandate($request['callback'], $content);

        $collection = $helper->fetchAll([
                        'expand'    => ['payer', 'payee'],
                        'response'   =>'active'
                      ]);

        $this->assertCollection($collection, 1, [
            [
                'status'    => 'approved',
            ],
        ]);
    }


    public function testFetchAllStatusesWithHistoryState()
    {
        $helper = $this->getMandateHelper();

        $request = $helper->getCreateMandatePayload($this->gateway);

        $this->createMandateOnMock($helper, $request);

        $lastMandate = $this->fixtures->getDbLastMandate();

        $request = $helper->initiateAuthorize($lastMandate->getId(), []);

        $content = $this->handleSdkRequest($request);

        $helper->authorizeMandate($request['callback'], $content);

        $request = $helper->initiateRevoke($lastMandate->getId(), []);

        $content = $this->handleSdkRequest($request);

        $response = $helper->revokeMandate($request['callback'], $content);

        $collection = $helper->fetchAll([
                            'expand'    => ['payer', 'payee'],
                            'response'   =>'history'
                      ]);

        $this->assertCollection($collection, 1, [
            [
                'status'    => 'revoked',
            ],
        ]);
    }
}
