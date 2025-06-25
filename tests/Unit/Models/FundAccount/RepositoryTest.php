<?php

namespace Tests\Unit\Models\FundAccount;

use Tests\TestCase;
use RZP\Models\Contact;
use RZP\Models\FundAccount;
use RZP\Constants\Entity as E;
use RZP\Tests\Functional\TestCase as FunctionalTestCase;

class RepositoryTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->ba->privateAuth();
    }

    /**
     * Test the new fetchFundAccountWithContactForStatementEnrichment method with contact
     */
    public function testFetchFundAccountWithContactForStatementEnrichmentWithContact()
    {
        // Create a contact
        $contact = $this->fixtures->create('contact', [
            'id' => '1000000contact',
            'name' => 'Test Contact',
            'email' => 'test@example.com',
            'contact' => '9876543210',
            'merchant_id' => '10000000000000'
        ]);

        // Create a fund account linked to contact - using the new fund account ID from StatementController
        $fundAccount = $this->fixtures->create('fund_account', [
            'id' => 'DC66xJ6xbcOLqU',
            'merchant_id' => '10000000000000',
            'source_type' => 'contact',
            'source_id' => $contact->getId(),
            'account_type' => 'bank_account',
        ]);

        // Test the new repository method
        $repo = new FundAccount\Repository();
        $result = $repo->fetchFundAccountWithContactForStatementEnrichment($fundAccount->getId());

        // Verify the method returns expected data structure
        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertEquals($fundAccount->getId(), $result['fund_account_id']);
        $this->assertEquals($contact->getId(), $result['contact_id']);
        $this->assertEquals('Test Contact', $result['name']);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('9876543210', $result['contact']);
    }

    /**
     * Test the new fetchFundAccountWithContactForStatementEnrichment method without contact
     */
    public function testFetchFundAccountWithContactForStatementEnrichmentWithoutContact()
    {
        // Create a fund account not linked to contact (merchant fund account) - using the new fund account ID
        $fundAccount = $this->fixtures->create('fund_account', [
            'id' => 'DC66xJ6xbcOLqU',
            'merchant_id' => '10000000000000',
            'source_type' => 'merchant',
            'source_id' => '10000000000000',
            'account_type' => 'bank_account',
        ]);

        // Test the new repository method
        $repo = new FundAccount\Repository();
        $result = $repo->fetchFundAccountWithContactForStatementEnrichment($fundAccount->getId());

        // Verify the method returns fund account ID but null contact data
        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertEquals($fundAccount->getId(), $result['fund_account_id']);
        $this->assertNull($result['contact_id']);
        $this->assertNull($result['name']);
        $this->assertNull($result['email']);
        $this->assertNull($result['contact']);
    }

    /**
     * Test the new fetchFundAccountWithContactForStatementEnrichment method with non-existent fund account
     */
    public function testFetchFundAccountWithContactForStatementEnrichmentNotFound()
    {
        // Test with non-existent fund account ID
        $repo = new FundAccount\Repository();
        $result = $repo->fetchFundAccountWithContactForStatementEnrichment('nonexistent123');

        // Verify the method returns null for non-existent fund account
        $this->assertNull($result);
    }

    /**
     * Test the new fetchFundAccountWithContactForStatementEnrichment method with the exact fund account ID from StatementController
     */
    public function testFetchFundAccountWithContactForStatementEnrichmentWithStatementControllerFundAccount()
    {
        // Create a contact
        $contact = $this->fixtures->create('contact', [
            'id' => '1000000contact',
            'name' => 'Statement Test Contact',
            'email' => 'statement@example.com',
            'contact' => '9876543210',
            'merchant_id' => '10000000000000'
        ]);

        // Create a fund account with the exact ID used in StatementController
        $fundAccount = $this->fixtures->create('fund_account', [
            'id' => 'DC66xJ6xbcOLqU', // This matches the StatementController hardcoded value
            'merchant_id' => '10000000000000',
            'source_type' => 'contact',
            'source_id' => $contact->getId(),
            'account_type' => 'bank_account',
        ]);

        // Test the new repository method with the StatementController fund account ID
        $repo = new FundAccount\Repository();
        $result = $repo->fetchFundAccountWithContactForStatementEnrichment('DC66xJ6xbcOLqU');

        // Verify the method returns expected data structure
        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertEquals('DC66xJ6xbcOLqU', $result['fund_account_id']);
        $this->assertEquals($contact->getId(), $result['contact_id']);
        $this->assertEquals('Statement Test Contact', $result['name']);
        $this->assertEquals('statement@example.com', $result['email']);
        $this->assertEquals('9876543210', $result['contact']);
    }

    /**
     * Test that the repository method works correctly when the Core method strips "fa_" prefix
     * This test demonstrates the integration between Core and Repository for prefix handling
     */
    public function testFetchFundAccountWithContactForStatementEnrichmentIntegrationWithPrefixHandling()
    {
        // Create a contact
        $contact = $this->fixtures->create('contact', [
            'id' => '1000000contact',
            'name' => 'Prefix Test Contact',
            'email' => 'prefix@example.com',
            'contact' => '9876543210',
            'merchant_id' => '10000000000000'
        ]);

        // Create a fund account
        $fundAccount = $this->fixtures->create('fund_account', [
            'id' => 'DC66xJ6xbcOLqU',
            'merchant_id' => '10000000000000',
            'source_type' => 'contact',
            'source_id' => $contact->getId(),
            'account_type' => 'bank_account',
        ]);

        $repo = new FundAccount\Repository();
        
        // Test 1: Repository method called with ID without "fa_" prefix (as it would be after Core strips it)
        $result1 = $repo->fetchFundAccountWithContactForStatementEnrichment('DC66xJ6xbcOLqU');
        $this->assertNotNull($result1);
        $this->assertEquals('DC66xJ6xbcOLqU', $result1['fund_account_id']);
        $this->assertEquals('Prefix Test Contact', $result1['name']);
        
        // Test 2: Verify that if somehow a "fa_" prefixed ID reaches the repository, it won't find the record
        // (This demonstrates why the Core method needs to strip the prefix)
        $result2 = $repo->fetchFundAccountWithContactForStatementEnrichment('fa_DC66xJ6xbcOLqU');
        $this->assertNull($result2); // Should be null because fund account ID in DB doesn't have "fa_" prefix
    }
} 