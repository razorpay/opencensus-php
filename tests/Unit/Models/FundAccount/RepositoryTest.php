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

        // Create a fund account linked to contact
        $fundAccount = $this->fixtures->create('fund_account', [
            'id' => '1000000fundac',
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
        // Create a fund account not linked to contact (merchant fund account)
        $fundAccount = $this->fixtures->create('fund_account', [
            'id' => '1000000fundac',
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
} 