<?php

namespace RZP\Tests\Functional\Merchant;

use Mockery;
use RZP\Models\Merchant\Core;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;
use RZP\Models\Merchant\BusinessDetail\Constants as BusinessDetailConstants;
use RZP\Models\Merchant\InternationalEnablement\Service as InternationalEnablementService;
use RZP\Models\Merchant\InternationalEnablement\Detail\Entity as IEDetailEntity;
use RZP\Models\Merchant\InternationalEnablement\Document\Core as DocumentCore;
use RZP\Models\Merchant\InternationalEnablement\Document\Entity as DocumentEntity;
use RZP\Models\Merchant\InternationalEnablement\Constants as IEConstants;
use RZP\Models\Partner\Entity as PartnerEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\MocksRedisTrait;
use RZP\Models\Base\PublicCollection;

class MerchantCrossBorderRiskWorkflowTest extends TestCase
{
    use MocksRedisTrait;

    protected $merchantCore;
    protected $repoMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock cache to avoid Redis connection issues
        $cacheMock = Mockery::mock('Illuminate\Contracts\Cache\Repository');
        $cacheMock->shouldReceive('get')->andReturn(null);
        $cacheMock->shouldReceive('forever')->andReturn(true);
        $this->app->instance('cache', $cacheMock);

        $this->merchantCore = new Core();
        
        // Mock the repository structure properly
        $this->repoMock = Mockery::mock('stdClass');
        $merchantAccessMapRepoMock = Mockery::mock('stdClass');
        $merchantAccessMapRepoMock->shouldReceive('fetchAffiliatedPartnersForSubmerchant')->andReturn(new PublicCollection());
        $this->repoMock->merchant_access_map = $merchantAccessMapRepoMock;

        // Use reflection to set the protected repo property
        $reflection = new \ReflectionClass(Core::class);
        $repoProperty = $reflection->getProperty('repo');
        $repoProperty->setAccessible(true);
        $repoProperty->setValue($this->merchantCore, $this->repoMock);
    }

    public function testValidateWebsiteCheckForInternationalActivationWithBusinessWebsite()
    {
        $merchantMock = Mockery::mock(Entity::class);
        $merchantMock->shouldReceive('getWebsite')->andReturn('https://example.com');

        $merchantDetailMock = Mockery::mock(DetailEntity::class);
        $merchantDetailMock->shouldReceive('getWebsite')->andReturn(null);
        // Mock the businessDetail property access
        $merchantDetailMock->shouldReceive('getAttribute')->with('businessDetail')->andReturn(null);
        $merchantDetailMock->shouldReceive('__get')->with('businessDetail')->andReturn(null);

        $result = $this->merchantCore->validateWebsiteCheckForInternationalActivation($merchantMock, $merchantDetailMock);

        $this->assertTrue($result);
    }

  

    public function testValidateWebsiteCheckForInternationalActivationWithFreelancerUrl()
    {
        $merchantMock = Mockery::mock(Entity::class);
        $merchantMock->shouldReceive('getWebsite')->andReturn(null); // Business website is null

        $businessDetailMock = Mockery::mock(BusinessDetailEntity::class);
        $businessDetailMock->shouldReceive('getWebsiteDetails')->andReturn([
            'cross_border_freelancer_url' => 'https://www.upwork.com/freelancers/example'
        ]);

        $merchantDetailMock = Mockery::mock(DetailEntity::class);
        $merchantDetailMock->shouldReceive('getWebsite')->andReturn(null);
        // Mock the businessDetail property access to return our mock
        $merchantDetailMock->shouldReceive('getAttribute')->with('businessDetail')->andReturn($businessDetailMock);
        $merchantDetailMock->shouldReceive('__get')->with('businessDetail')->andReturn($businessDetailMock);

        $result = $this->merchantCore->validateWebsiteCheckForInternationalActivation($merchantMock, $merchantDetailMock);

        $this->assertTrue($result);
    }





    /**
     * Test addFreelancerUrlToWorkflowData method with valid freelancer URL
     */
    public function testAddFreelancerUrlToWorkflowDataWithValidUrl()
    {
        $merchantMock = Mockery::mock(Entity::class);
        $merchantMock->shouldReceive('getId')->andReturn('test_merchant_id');

        $businessDetailMock = Mockery::mock(BusinessDetailEntity::class);
        $businessDetailMock->shouldReceive('getWebsiteDetails')->andReturn([
            BusinessDetailConstants::CROSS_BORDER_FREELANCER_URL => 'https://www.upwork.com/freelancers/example'
        ]);

        $merchantDetailMock = Mockery::mock(DetailEntity::class);
        $merchantDetailMock->shouldReceive('getAttribute')->with('businessDetail')->andReturn($businessDetailMock);
        $merchantDetailMock->shouldReceive('__get')->with('businessDetail')->andReturn($businessDetailMock);
        $merchantDetailMock->shouldReceive('setAttribute')->andReturn(null);
        $merchantDetailMock->shouldReceive('__set')->andReturn(null);
        $merchantDetailMock->businessDetail = $businessDetailMock;

        // Mock Merchant\Detail\Core
        $merchantDetailCoreMock = Mockery::mock('overload:RZP\Models\Merchant\Detail\Core');
        $merchantDetailCoreMock->shouldReceive('getMerchantDetails')->with($merchantMock)->andReturn($merchantDetailMock);

        $ieDetailMock = Mockery::mock(IEDetailEntity::class);
        $ieDetailMock->shouldReceive('getMerchantId')->andReturn('test_merchant_id');
        $ieDetailMock->shouldReceive('getId')->andReturn('ie_detail_id');
        $ieDetailMock->shouldReceive('getAttribute')->with('documents')->andReturn(new PublicCollection());
        $ieDetailMock->shouldReceive('__get')->with('documents')->andReturn(new PublicCollection());
        $ieDetailMock->shouldReceive('setAttribute')->andReturn(null);
        $ieDetailMock->shouldReceive('__set')->andReturn(null);
        $ieDetailMock->documents = new PublicCollection();

        $service = new InternationalEnablementService();
        
        // Use reflection to set properties
        $reflection = new \ReflectionClass(InternationalEnablementService::class);
        
        $merchantProperty = $reflection->getProperty('merchant');
        $merchantProperty->setAccessible(true);
        $merchantProperty->setValue($service, $merchantMock);

        // Create a proper app mock that implements ArrayAccess
        $appMock = Mockery::mock('ArrayAccess');
        $appMock->shouldReceive('offsetExists')->with('rzp.mode')->andReturn(true);
        $appMock->shouldReceive('offsetGet')->with('rzp.mode')->andReturn('test');
        $appProperty = $reflection->getProperty('app');
        $appProperty->setAccessible(true);
        $appProperty->setValue($service, $appMock);

        $traceMock = Mockery::mock('stdClass');
        $traceMock->shouldReceive('info')->andReturn(null);
        $traceProperty = $reflection->getProperty('trace');
        $traceProperty->setAccessible(true);
        $traceProperty->setValue($service, $traceMock);

        // Mock Document\Core for convertDocObjectsToExternalFormat method
        $documentCoreMock = Mockery::mock('overload:RZP\Models\Merchant\InternationalEnablement\Document\Core');
        $documentCoreMock->shouldReceive('convertDocObjectsToExternalFormat')->andReturn([]);

        // Variable to capture workflow data
        $capturedWorkflowData = null;

        // Mock Typeform\Core for processInHouseQuestionnaire method and capture workflow data
        $typeformCoreMock = Mockery::mock('overload:RZP\Models\Typeform\Core');
        $typeformCoreMock->shouldReceive('processInHouseQuestionnaire')
            ->with($merchantMock, Mockery::on(function($workflowData) use (&$capturedWorkflowData) {
                $capturedWorkflowData = $workflowData;
                return true;
            }), Mockery::any(), Mockery::any())
            ->andReturn(null);

        $input = ['test' => 'data'];
        
        // Call createWorkflowsIfApplicable which will internally call addFreelancerUrlToWorkflowData
        $service->createWorkflowsIfApplicable($input, $ieDetailMock);
        
        // Verify that the freelancer URL was successfully added to the workflow data
        s($capturedWorkflowData);
        $this->assertNotNull($capturedWorkflowData, 'Workflow data should be captured');
        $this->assertEquals('https://www.upwork.com/freelancers/example', $capturedWorkflowData['freelancer_url']);
        
        // Also verify other expected workflow data fields are present
        $this->assertArrayHasKey('detail_url', $capturedWorkflowData, 'Detail URL should be present in workflow data');
    }

  

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 