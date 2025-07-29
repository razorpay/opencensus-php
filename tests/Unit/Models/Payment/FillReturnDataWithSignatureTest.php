<?php

namespace RZP\Tests\Unit\Models\Payment;

use Mockery;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Payment\Processor\Processor;
use RZP\Tests\Functional\TestCase;

class FillReturnDataWithSignatureTest extends TestCase
{
    protected $processor;
    protected $basicAuth;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->basicAuth = Mockery::mock(BasicAuth::class);
        $this->processor = Mockery::mock(Processor::class)->makePartial()->shouldAllowMockingProtectedMethods();
        
        // Set the basic auth mock to the processor
        $this->processor->shouldReceive('getAttribute')->with('ba')->andReturn($this->basicAuth);
        $this->processor->ba = $this->basicAuth;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test fillReturnDataWithSignatureIfApplicable when getKeyEntity returns null
     * and all conditions are met to skip signature calculation
     */
    public function testFillReturnDataWithSignatureSkippedWhenKeyEntityIsNull()
    {
        $data = ['razorpay_payment_id' => 'pay_test123'];

        // Mock the conditions for skipping signature calculation
        $this->basicAuth->shouldReceive('isPublicAuth')->andReturn(true);
        $this->basicAuth->shouldReceive('isDirectAuth')->andReturn(false);
        $this->basicAuth->shouldReceive('getKeyEntity')->andReturn(null); // Key entity is null
        $this->basicAuth->shouldReceive('getOAuthClientId')->andReturn(null);
        $this->basicAuth->shouldReceive('isPartnerAuth')->andReturn(false);

        // getSignature should NOT be called since we're skipping
        $this->processor->shouldNotReceive('getSignature');

        // Call the method
        $this->processor->fillReturnDataWithSignatureIfApplicable($data);

        // Assert that no signature was added
        $this->assertArrayNotHasKey('razorpay_signature', $data);
        $this->assertEquals(['razorpay_payment_id' => 'pay_test123'], $data);
    }

    /**
     * Test fillReturnDataWithSignatureIfApplicable when getKeyEntity returns empty array
     * and all conditions are met to skip signature calculation
     */
    public function testFillReturnDataWithSignatureSkippedWhenKeyEntityIsEmpty()
    {
        $data = ['razorpay_payment_id' => 'pay_test123'];

        // Mock the conditions for skipping signature calculation
        $this->basicAuth->shouldReceive('isPublicAuth')->andReturn(false);
        $this->basicAuth->shouldReceive('isDirectAuth')->andReturn(true);
        $this->basicAuth->shouldReceive('getKeyEntity')->andReturn([]); // Key entity is empty array
        $this->basicAuth->shouldReceive('getOAuthClientId')->andReturn(null);
        $this->basicAuth->shouldReceive('isPartnerAuth')->andReturn(false);

        // getSignature should NOT be called since we're skipping
        $this->processor->shouldNotReceive('getSignature');

        // Call the method
        $this->processor->fillReturnDataWithSignatureIfApplicable($data);

        // Assert that no signature was added
        $this->assertArrayNotHasKey('razorpay_signature', $data);
        $this->assertEquals(['razorpay_payment_id' => 'pay_test123'], $data);
    }

    /**
     * Test fillReturnDataWithSignatureIfApplicable when getKeyEntity returns null
     * but conditions are NOT met to skip signature calculation (has OAuth client)
     */
    public function testFillReturnDataWithSignatureNotSkippedWhenKeyEntityNullButHasOAuthClient()
    {
        $data = ['razorpay_payment_id' => 'pay_test123'];

        // Mock the conditions where signature should be calculated
        $this->basicAuth->shouldReceive('isPublicAuth')->andReturn(true);
        $this->basicAuth->shouldReceive('isDirectAuth')->andReturn(false);
        $this->basicAuth->shouldReceive('getKeyEntity')->andReturn(null); // Key entity is null
        $this->basicAuth->shouldReceive('getOAuthClientId')->andReturn('oauth_client_123'); // Has OAuth client
        $this->basicAuth->shouldReceive('isPartnerAuth')->andReturn(false);

        // getSignature SHOULD be called since we're not skipping
        $this->processor->shouldReceive('getSignature')->with($data)->andReturn('test_signature');

        // Call the method
        $this->processor->fillReturnDataWithSignatureIfApplicable($data);

        // Assert that signature was added
        $this->assertArrayHasKey('razorpay_signature', $data);
        $this->assertEquals('test_signature', $data['razorpay_signature']);
    }

    /**
     * Test fillReturnDataWithSignatureIfApplicable when getKeyEntity returns null
     * but conditions are NOT met to skip signature calculation (is partner auth)
     */
    public function testFillReturnDataWithSignatureNotSkippedWhenKeyEntityNullButIsPartnerAuth()
    {
        $data = ['razorpay_payment_id' => 'pay_test123'];

        // Mock the conditions where signature should be calculated
        $this->basicAuth->shouldReceive('isPublicAuth')->andReturn(true);
        $this->basicAuth->shouldReceive('isDirectAuth')->andReturn(false);
        $this->basicAuth->shouldReceive('getKeyEntity')->andReturn(null); // Key entity is null
        $this->basicAuth->shouldReceive('getOAuthClientId')->andReturn(null);
        $this->basicAuth->shouldReceive('isPartnerAuth')->andReturn(true); // Is partner auth

        // getSignature SHOULD be called since we're not skipping
        $this->processor->shouldReceive('getSignature')->with($data)->andReturn('partner_signature');

        // Call the method
        $this->processor->fillReturnDataWithSignatureIfApplicable($data);

        // Assert that signature was added
        $this->assertArrayHasKey('razorpay_signature', $data);
        $this->assertEquals('partner_signature', $data['razorpay_signature']);
    }

    /**
     * Test fillReturnDataWithSignatureIfApplicable when getKeyEntity returns valid key entity
     * signature calculation should proceed normally
     */
    public function testFillReturnDataWithSignatureNotSkippedWhenKeyEntityExists()
    {
        $data = ['razorpay_payment_id' => 'pay_test123'];

        // Mock the conditions where signature should be calculated
        $this->basicAuth->shouldReceive('isPublicAuth')->andReturn(true);
        $this->basicAuth->shouldReceive('isDirectAuth')->andReturn(false);
        $this->basicAuth->shouldReceive('getKeyEntity')->andReturn((object)['id' => 'key_123']); // Key entity exists
        $this->basicAuth->shouldReceive('getOAuthClientId')->andReturn(null);
        $this->basicAuth->shouldReceive('isPartnerAuth')->andReturn(false);

        // getSignature SHOULD be called since key entity exists
        $this->processor->shouldReceive('getSignature')->with($data)->andReturn('valid_signature');

        // Call the method
        $this->processor->fillReturnDataWithSignatureIfApplicable($data);

        // Assert that signature was added
        $this->assertArrayHasKey('razorpay_signature', $data);
        $this->assertEquals('valid_signature', $data['razorpay_signature']);
    }

    /**
     * Test fillReturnDataWithSignatureIfApplicable when neither public auth nor direct auth
     * signature calculation should proceed normally
     */
    public function testFillReturnDataWithSignatureNotSkippedWhenNotPublicOrDirectAuth()
    {
        $data = ['razorpay_payment_id' => 'pay_test123'];

        // Mock the conditions where signature should be calculated (not public or direct auth)
        $this->basicAuth->shouldReceive('isPublicAuth')->andReturn(false);
        $this->basicAuth->shouldReceive('isDirectAuth')->andReturn(false);
        $this->basicAuth->shouldReceive('getKeyEntity')->andReturn(null); // Key entity is null
        $this->basicAuth->shouldReceive('getOAuthClientId')->andReturn(null);
        $this->basicAuth->shouldReceive('isPartnerAuth')->andReturn(false);

        // getSignature SHOULD be called since auth type doesn't match skip conditions
        $this->processor->shouldReceive('getSignature')->with($data)->andReturn('normal_signature');

        // Call the method
        $this->processor->fillReturnDataWithSignatureIfApplicable($data);

        // Assert that signature was added
        $this->assertArrayHasKey('razorpay_signature', $data);
        $this->assertEquals('normal_signature', $data['razorpay_signature']);
    }
} 