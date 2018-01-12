<?php

namespace RZP\Tests\Functional\Settlement\Reconciliaton\Icici;

use App;
use Mail;
use Config;
use Carbon\Carbon;

use RZP\Tests\Functional\Gateway\Kotak\ReconciliationTrait;

use RZP\Constants\Timezone;
use RZP\Mail\Settlement\Reconciliation as ReconciliationMail;
use RZP\Mail\Merchant\SettlementFailure as SettlementFailureMail;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Account;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Settlement;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class IciciReconciliationTest extends TestCase
{
    use RequestResponseFlowTrait;
    use SettlementTrait;
    use ReconciliationTrait;
    use FileHandlerTrait;
    use HeimdallTrait;

    protected $channel;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/IciciTestData.php';

        parent::setUp();

        $this->channel = Settlement\Channel::ICICI;

        $this->ba->adminAuth();

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $this->channel]);
}

    public function testReconFileProcess()
    {
        // Create payments and refunds with timestamps two days back
        $this->createPaymentAndRefundEntities(2);

        $this->ba->appAuth();

        $this->deleteSetlFiles();

        // Generate settlements for above transactions
        $setlFile = $this->initiateSettlementsAndAssertSuccess($this->channel);

        // Generate settlement reconciliation file
        $setlReconciliationFile = $this->generateSetlReconciliationFile($setlFile, $this->channel);

        // Reconcile settlements
        $data = $this->reconcileSettlements($setlReconciliationFile, $this->channel);

        // Match data returned by reconciliation
        $this->assertTestResponse($data, 'matchSummaryForReconFile');

        // Validate settlement attempt entity
        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertTestResponse($settlementAttempt, 'matchSettlementAttemptForReconSuccess');
        $this->assertNotNull($settlementAttempt['utr']);
    }
}