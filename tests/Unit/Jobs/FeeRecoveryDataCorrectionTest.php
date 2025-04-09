<?php

namespace Unit\Jobs;

use RZP\Error\ErrorCode;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Models\FeeRecovery\Metric;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\FeeRecoveryDataCorrection;

class FeeRecoveryDataCorrectionTest extends TestCase
{
    use PayoutTrait;
    use MocksSplitz;
    use DbEntityFetchTrait;

    private $merchant;
    private $merchantId;
    private $banking_account;
    private $balance;

    public function setUp(): void{
        parent::setUp();

        $this->fixtures->create('merchant');
        $this->merchant = $this->getDbLastEntity('merchant');
        $this->merchantId = $this->merchant->getId();
        $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => $this->merchantId,
            'channel'               => 'yesbank',
            'status'                => 'activated',
        ]);
        $this->banking_account = $this->getDbLastEntity('banking_account');

        $this->fixtures->create('balance',
            [
                'merchant_id'    => $this->merchantId,
                'type'           => 'banking',
                'balance'        => 100000,
                'account_number' => '2224440041626905',
                'channel'        => 'rbl',
                'account_type'   => 'direct',
            ]);
        $this->balance = $this->getDbLastEntity('balance');
    }

    private function setProtectedProperty($object, $property, $value)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    public function testFeeRecoveryDataCorrectionNotEligible()
    {
        $this->mockSplitzForFeeRecoveryDataCorrection("fee_recovery_data_correction_enabled", $this->balance->getId(), 'disable');
        $job = new FeeRecoveryDataCorrection('mode', $this->balance->getId(), 1609459200, 1609545600);
        $traceMock = $this->createMock(Trace::class);
        $this->setProtectedProperty($job, 'trace', $traceMock);

        $response = $job->handle();
        $this->assertFalse($response);
    }

    public function testFeeRecoveryDataCorrectionSuccess()
    {
        $this->mockSplitzForFeeRecoveryDataCorrection("fee_recovery_data_correction_enabled", $this->balance->getId(), 'enable');
        $job = new FeeRecoveryDataCorrection('mode', $this->balance->getId(), 1609459200, 1609545600);
        $traceMock = $this->createMock(Trace::class);
        $this->setProtectedProperty($job, 'trace', $traceMock);

        $response = $job->handle();
        $this->assertTrue($response);
    }

    public function testFeeRecoveryDataCorrectionFailure()
    {
        $this->mockSplitzForFeeRecoveryDataCorrection("fee_recovery_data_correction_enabled", $this->balance->getId(), 'enable');

        $job = new FeeRecoveryDataCorrection('mode', $this->balance->getId(), 1609459200, 1609459100);
        $traceMock = $this->createMock(Trace::class);
        $this->setProtectedProperty($job, 'trace', $traceMock);

        $this->expectException('\RZP\Exception\BadRequestException');
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_FEE_RECOVERY_INCORRECT_TIMESTAMPS);
        $this->expectExceptionMessage('Start timestamp cannot be greater than end timestamp');
        $job->handle();
    }

    private function mockSplitzForFeeRecoveryDataCorrection(string $experimentId, string $balanceId, string $variant): void
    {
        $input = [
            "experiment_id" => $experimentId,
            "id" => $balanceId,
            'request_data' => json_encode(['id' => $balanceId])
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => $variant,
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);
    }

}
