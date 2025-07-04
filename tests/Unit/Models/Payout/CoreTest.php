<?php

namespace Tests\Unit\Models\Payout;

use Mockery;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\FundAccount;
use RZP\Models\Merchant\Balance;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Trace\TraceCode;

class CoreTest extends TestCase
{
    use MocksSplitz;
    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->privateAuth();
    }

    /**
     * Test that getSourceEventInfoForStatementEnrichment includes enhanced data when available
     */
    public function testGetSourceEventInfoForStatementEnrichmentIncludesEnhancedData()
    {
        // Create test entities
        $contact = $this->fixtures->create('contact', [
            'id' => '1000000contact',
            'name' => 'Test Contact',
            'email' => 'test@example.com',
            'contact' => '9876543210',
            'merchant_id' => '10000000000000'
        ]);

        $fundAccount = $this->fixtures->create('fund_account', [
            'id' => 'DC66xJ6xbcOLqU',
            'merchant_id' => '10000000000000',
            'source_type' => 'contact',
            'source_id' => $contact->getId(),
            'account_type' => 'bank_account',
        ]);


        $balance = $this->fixtures->create('balance', [
            'id' => '1000000balance',
            'merchant_id' => '10000000000000',
            'type' => 'banking',
            'account_type' => 'direct',
        ]);

        $payout = $this->fixtures->create('payout', [
            'id' => '1000000payout',
            'merchant_id' => '10000000000000',
            'fund_account_id' => $fundAccount->getId(),
            'balance_id' => $balance->getId(),
            'amount' => 100000,
            'currency' => 'INR',
            'mode' => 'IMPS',
            'status' => 'processed',
            'utr' => 'TEST123456789',
        ]);

        // Test the enhanced core method
        $core = new Payout\Core();
        $result = $core->getSourceEventInfoForStatementEnrichment($payout);

        // Verify enhanced data is included
        $this->assertEquals($fundAccount->getId(), $result[Payout\Constants::FUND_ACCOUNT_ID]);
        $this->assertEquals($contact->getId(), $result[Payout\Constants::CONTACT_ID]);
        $this->assertEquals('Test Contact', $result[Payout\Constants::NAME]);
        $this->assertEquals('test@example.com', $result[Payout\Constants::EMAIL]);
        $this->assertEquals('9876543210', $result[Payout\Constants::CONTACT]);
    }

    /**
     * Test that getSourceEventInfoForStatementEnrichment handles missing contact data gracefully
     */
    public function testGetSourceEventInfoForStatementEnrichmentHandlesMissingContact()
    {
        // Create fund account without contact (merchant fund account)
        $fundAccount = $this->fixtures->create('fund_account', [
            'id' => 'DC66xJ6xbcOLqU',
            'merchant_id' => '10000000000000',
            'source_type' => 'merchant',
            'source_id' => '10000000000000',
            'account_type' => 'bank_account',
        ]);

        $balance = $this->fixtures->create('balance', [
            'id' => '1000000balance',
            'merchant_id' => '10000000000000',
            'type' => 'banking',
            'account_type' => 'direct',
        ]);

        $payout = $this->fixtures->create('payout', [
            'id' => '1000000payout',
            'merchant_id' => '10000000000000',
            'fund_account_id' => $fundAccount->getId(),
            'balance_id' => $balance->getId(),
            'amount' => 100000,
            'currency' => 'INR',
            'mode' => 'IMPS',
            'status' => 'processed',
            'utr' => 'TEST123456789',
        ]);

        // Test the enhanced core method
        $core = new Payout\Core();
        $result = $core->getSourceEventInfoForStatementEnrichment($payout);

        // Verify enhanced data handles missing contact gracefully
        $this->assertEquals($fundAccount->getId(), $result[Payout\Constants::FUND_ACCOUNT_ID]);
        $this->assertNull($result[Payout\Constants::CONTACT_ID]);
        $this->assertNull($result[Payout\Constants::NAME]);
        $this->assertNull($result[Payout\Constants::EMAIL]);
        $this->assertNull($result[Payout\Constants::CONTACT]);
    }

    /**
     * Test that getSourceEventInfoForStatementEnrichment handles repository errors gracefully
     */
    public function testGetSourceEventInfoForStatementEnrichmentHandlesRepositoryError()
    {
        $fundAccount = $this->fixtures->create('fund_account', [
            'id' => 'DC66xJ6xbcOLqU',
            'merchant_id' => '10000000000000',
            'source_type' => 'contact',
            'source_id' => '1000000contact',
            'account_type' => 'bank_account',
        ]);

        $balance = $this->fixtures->create('balance', [
            'id' => '1000000balance',
            'merchant_id' => '10000000000000',
            'type' => 'banking',
            'account_type' => 'direct',
        ]);

        $payout = $this->fixtures->create('payout', [
            'id' => '1000000payout',
            'merchant_id' => '10000000000000',
            'fund_account_id' => $fundAccount->getId(),
            'balance_id' => $balance->getId(),
            'amount' => 100000,
            'currency' => 'INR',
            'mode' => 'IMPS',
            'status' => 'processed',
            'utr' => 'TEST123456789',
        ]);

        // Mock repository to throw exception
        $mockRepo = Mockery::mock(FundAccount\Repository::class);
        $mockRepo->shouldReceive('fetchFundAccountWithContactForStatementEnrichment')
                 ->andThrow(new \Exception('Database error'));

        $core = new Payout\Core();
        $core->repo = (object)['fund_account' => $mockRepo];

        // Test the enhanced core method
        $result = $core->getSourceEventInfoForStatementEnrichment($payout);

        // Verify method handles error gracefully and enhanced data is null
        $this->assertNull($result[Payout\Constants::FUND_ACCOUNT_ID]);
        $this->assertNull($result[Payout\Constants::CONTACT_ID]);
        $this->assertNull($result[Payout\Constants::NAME]);
        $this->assertNull($result[Payout\Constants::EMAIL]);
        $this->assertNull($result[Payout\Constants::CONTACT]);

        // Base data should still be present
        $this->assertEquals($payout->getId(), $result[Payout\Constants::ENTITY_ID]);
        $this->assertEquals('TEST123456789', $result[Payout\Constants::UTR]);
    }

    /**
     * Test getSourceEventInfoForStatementEnrichment with StatementController payout data structure
     */
    public function testGetSourceEventInfoForStatementEnrichmentWithStatementControllerData()
    {
        // Create test entities that match StatementController implementation
        $contact = $this->fixtures->create('contact', [
            'id' => '1000000contact',
            'name' => 'Statement Controller Contact',
            'email' => 'statement@example.com',
            'contact' => '9876543210',
            'merchant_id' => '10000000000000'
        ]);

        $fundAccount = $this->fixtures->create('fund_account', [
            'id' => 'DC66xJ6xbcOLqU',
            'merchant_id' => '10000000000000',
            'source_type' => 'contact',
            'source_id' => $contact->getId(),
            'account_type' => 'bank_account',
        ]);

        $balance = $this->fixtures->create('balance', [
            'id' => '1000000balance',
            'merchant_id' => '10000000000000',
            'type' => 'banking',
            'account_type' => 'direct',
        ]);

        // Create payout with data structure matching StatementController
        $payout = $this->fixtures->create('payout', [
            'id' => 'QLnondjZwtjPUL',
            'merchant_id' => 'OGJDenfkpc6whP',
            'fund_account_id' => 'DC66xJ6xbcOLqU',
            'balance_id' => 'OGJDgn9X7zdiD8',
            'amount' => 100000,
            'currency' => 'INR',
            'fees' => 590,
            'tax' => 90,
            'mode' => 'NEFT',
            'status' => 'processed',
            'purpose' => 'RANDOM TEST PURPOSE',
            'utr' => 'TEST123456789',
            'narration' => 'CLSBen Fund Transfer',
        ]);

        // Test the enhanced core method
        $core = new Payout\Core();
        $result = $core->getSourceEventInfoForStatementEnrichment($payout);

        // Verify enhanced data is included with StatementController values
        $this->assertEquals('DC66xJ6xbcOLqU', $result[Payout\Constants::FUND_ACCOUNT_ID]);
        $this->assertEquals($contact->getId(), $result[Payout\Constants::CONTACT_ID]);
        $this->assertEquals('Statement Controller Contact', $result[Payout\Constants::NAME]);
        $this->assertEquals('statement@example.com', $result[Payout\Constants::EMAIL]);
        $this->assertEquals('9876543210', $result[Payout\Constants::CONTACT]);

        // Verify base payout data
        $this->assertEquals('QLnondjZwtjPUL', $result[Payout\Constants::ENTITY_ID]);
        $this->assertEquals('TEST123456789', $result[Payout\Constants::UTR]);
        $this->assertEquals('NEFT', $result[Payout\Constants::MODE]);
        $this->assertEquals(100000, $result[Payout\Constants::AMOUNT]);
        $this->assertEquals('OGJDgn9X7zdiD8', $result[Payout\Constants::BALANCE_ID]);
    }

    /**
     * Test getSourceEventInfoForStatementEnrichment handles "fa_" prefix correctly
     */
    public function testGetSourceEventInfoForStatementEnrichmentHandlesFaPrefixCorrectly()
    {
        // Create test entities
        $contact = $this->fixtures->create('contact', [
            'id' => '1000000contact',
            'name' => 'Test Contact',
            'email' => 'test@example.com',
            'contact' => '9876543210',
            'merchant_id' => '10000000000000'
        ]);

        $fundAccount = $this->fixtures->create('fund_account', [
            'id' => 'DC66xJ6xbcOLqU',
            'merchant_id' => '10000000000000',
            'source_type' => 'contact',
            'source_id' => $contact->getId(),
            'account_type' => 'bank_account',
        ]);

        $balance = $this->fixtures->create('balance', [
            'id' => '1000000balance',
            'merchant_id' => '10000000000000',
            'type' => 'banking',
            'account_type' => 'direct',
        ]);

        // Create payout with fund_account_id that has "fa_" prefix
        $payout = $this->fixtures->create('payout', [
            'id' => '1000000payout',
            'merchant_id' => '10000000000000',
            'fund_account_id' => 'fa_DC66xJ6xbcOLqU', // Note: "fa_" prefix added
            'balance_id' => $balance->getId(),
            'amount' => 100000,
            'currency' => 'INR',
            'mode' => 'IMPS',
            'status' => 'processed',
            'utr' => 'TEST123456789',
        ]);

        // Test the enhanced core method
        $core = new Payout\Core();
        $result = $core->getSourceEventInfoForStatementEnrichment($payout);

        // Verify enhanced data is included - the method should strip "fa_" prefix and find the fund account
        $this->assertEquals('DC66xJ6xbcOLqU', $result[Payout\Constants::FUND_ACCOUNT_ID]);
        $this->assertEquals($contact->getId(), $result[Payout\Constants::CONTACT_ID]);
        $this->assertEquals('Test Contact', $result[Payout\Constants::NAME]);
        $this->assertEquals('test@example.com', $result[Payout\Constants::EMAIL]);
        $this->assertEquals('9876543210', $result[Payout\Constants::CONTACT]);
    }

    /**
     * Test getSourceEventInfoForStatementEnrichment handles fund account ID without "fa_" prefix correctly
     */
    public function testGetSourceEventInfoForStatementEnrichmentHandlesNonFaPrefixCorrectly()
    {
        // Create test entities
        $contact = $this->fixtures->create('contact', [
            'id' => '1000000contact',
            'name' => 'Test Contact',
            'email' => 'test@example.com',
            'contact' => '9876543210',
            'merchant_id' => '10000000000000'
        ]);

        $fundAccount = $this->fixtures->create('fund_account', [
            'id' => 'DC66xJ6xbcOLqU',
            'merchant_id' => '10000000000000',
            'source_type' => 'contact',
            'source_id' => $contact->getId(),
            'account_type' => 'bank_account',
        ]);

        $balance = $this->fixtures->create('balance', [
            'id' => '1000000balance',
            'merchant_id' => '10000000000000',
            'type' => 'banking',
            'account_type' => 'direct',
        ]);

        // Create payout with fund_account_id that does NOT have "fa_" prefix
        $payout = $this->fixtures->create('payout', [
            'id' => '1000000payout',
            'merchant_id' => '10000000000000',
            'fund_account_id' => 'DC66xJ6xbcOLqU', // Note: NO "fa_" prefix
            'balance_id' => $balance->getId(),
            'amount' => 100000,
            'currency' => 'INR',
            'mode' => 'IMPS',
            'status' => 'processed',
            'utr' => 'TEST123456789',
        ]);

        // Test the enhanced core method
        $core = new Payout\Core();
        $result = $core->getSourceEventInfoForStatementEnrichment($payout);

        // Verify enhanced data is included - the method should use the ID as-is
        $this->assertEquals('DC66xJ6xbcOLqU', $result[Payout\Constants::FUND_ACCOUNT_ID]);
        $this->assertEquals($contact->getId(), $result[Payout\Constants::CONTACT_ID]);
        $this->assertEquals('Test Contact', $result[Payout\Constants::NAME]);
        $this->assertEquals('test@example.com', $result[Payout\Constants::EMAIL]);
        $this->assertEquals('9876543210', $result[Payout\Constants::CONTACT]);
    }

    /**
     * Test that updateStatusAfterFtaRecon properly merges existing notes with payee information
     * when payout has existing notes stored as JSON and ftaData contains PAYEE_IFSC
     */
    public function testUpdateStatusAfterFtaReconMergesNotesWithPayeeInfo()
    {
        // Mock the rzp.mode service to avoid service resolution errors
        $this->app->instance('rzp.mode', 'test');

        // Create test entities
        $contact = $this->fixtures->create('contact', [
            'id' => '1000000contact',
            'name' => 'Test Contact',
            'email' => 'test@example.com',
            'contact' => '9876543210',
            'merchant_id' => '10000000000000'
        ]);

        // Create a bank account first
        $bankAccount = $this->fixtures->create('bank_account', [
            'beneficiary_name' => 'Test Account',
            'account_number' => '12345678901234',
            'ifsc_code' => 'HDFC0000123'
        ]);

        $fundAccount = $this->fixtures->create('fund_account', [
            'id' => 'DC66xJ6xbcOLqU',
            'merchant_id' => '10000000000000',
            'source_type' => 'contact',
            'source_id' => $contact->getId(),
            'account_type' => 'bank_account',
            'account_id' => $bankAccount->getId(),
        ]);

        $balance = $this->fixtures->create('balance', [
            'id' => '1000000balance',
            'merchant_id' => '10000000000000',
            'type' => 'banking',
            'account_type' => 'direct',
        ]);

        // Create payout with existing notes (this tests the real scenario)
        $existingNotes = [
            'amount' => null,
            'aegonTransactionid' => null,
            'policyno' => 'ALI000000081772',
            'policyStatus' => null,
            'aegonOrderid' => null,
            'paymentMode' => null,
            'dueDate' => null,
            'additionalInfo' => null,
            'payoutId' => '257793',
            'fundAccountId' => null
        ];

        $payout = $this->fixtures->payout->createPayoutWithoutTransaction([
            'merchant_id' => '10000000000000',
            'fund_account_id' => $fundAccount->getId(),
            'balance_id' => $balance->getId(),
            'amount' => 100000,
            'currency' => 'INR',
            'mode' => 'IMPS',
            'status' => 'created',
            'notes' => $existingNotes,
        ]);

        // Test FTA data with PAYEE_IFSC
        $ftaData = [
            Payout\Constants::PAYEE_IFSC => 'SBIN0007105',
            'status' => 'processed',
            'fta_status' => 'processed',
            'utr' => 'TEST123456789',
            'bank_processed_time' => '2024-01-01 12:00:00',
            'remarks' => 'Test payout processed'
        ];

        // Mock Splitz experiment to return true
        $this->mockSplitzTreatment([
            'id' => '10000000000000',
            'experiment_name' => $this->app['config']->get('app.payouts_to_phone_number_splitz_experiment'),
            'request_data' => json_encode(['merchant_id' => '10000000000000'])
        ], [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ]);

        $core = new Payout\Core();

        // Call the actual method - it should handle the notes merging properly
        $core->updateStatusAfterFtaRecon($payout, $ftaData);

        // Refresh payout from database
        $payout->refresh();

        // Verify that notes were merged correctly
        $updatedNotes = $payout->getNotes()->toArray();

        // Check that existing notes are preserved
        $this->assertEquals('ALI000000081772', $updatedNotes['policyno']);
        $this->assertEquals('257793', $updatedNotes['payoutId']);

        // Check that new payee information was added
        $this->assertEquals('SBIN0007105', $updatedNotes[Payout\Constants::PAYEE_IFSC]);
        $this->assertEquals('State Bank of India', $updatedNotes[Payout\Constants::PAYEE_BANK_NAME]);

        // Verify status was updated
        $this->assertEquals('processed', $payout->getStatus());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
