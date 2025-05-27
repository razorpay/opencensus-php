<?php

namespace Tests\Unit\Models\Payout;

use Mockery;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\FundAccount;
use RZP\Models\Merchant\Balance;
use RZP\Tests\Functional\TestCase;
use RZP\Trace\TraceCode;

class CoreTest extends TestCase
{
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
            'id' => '1000000fundac',
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
            'id' => '1000000fundac',
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
            'id' => '1000000fundac',
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 