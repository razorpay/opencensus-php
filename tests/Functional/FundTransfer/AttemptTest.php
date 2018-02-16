<?php

namespace RZP\Tests\Functional\FundTransfer;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;

class AttemptTest extends TestCase
{
    use AttemptTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/AttemptTestData.php';

        parent::setUp();
    }

    public function testSettlementFileCreationIcici()
    {
        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::ICICI, 1, Attempt\Type::SETTLEMENT);

        $this->verifySettlementFileGeneration();
    }

    public function verifySettlementFileGeneration()
    {
        $batch = $this->getLastEntity('batch_fund_transfer', true);

        $request = [
            'url'       => '/settlements/file/generate',
            'method'    => 'POST',
            'content'   => [
                'h2h'                    => 1,
                'batch_fund_transfer_id' => $batch['id'],
            ],
        ];

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($content['local_file_path']);
    }

    public function testSettlementFileCreationKotak()
    {
        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::KOTAK, 1, Attempt\Type::SETTLEMENT);
    }

    public function testSettlementFileCreationAxis()
    {
        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::AXIS, 1, Attempt\Type::SETTLEMENT);

        $this->verifySettlementFileGeneration();
    }

    public function testPayoutFileCreationAxis()
    {
        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::AXIS, 1, Attempt\Type::PAYOUT);
    }

    public function testPayoutFileCreationIcici()
    {
        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::ICICI, 1, Attempt\Type::PAYOUT);
    }
}