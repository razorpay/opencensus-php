<?php

namespace RZP\Tests\Functional\Contacts;

use RZP\Models\Contact\Repository;
use RZP\Models\Contact\Core;
use RZP\Models\Contact\Service;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class ContactsUnitTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->privateAuth();
    }
    public function testCreateForCompositeRequest()
    {
        $merchant = $this->fixtures->create('merchant');

        $core = new Core();
        $result = $core->createForCompositeRequest(['name'=>'Test Contact'], $merchant,[]);

        $this->assertEquals('Test Contact', $result['name'],'');
    }

    public function testCreateForCompositeRequestDuplicateContact()
    {
        $merchant = $this->fixtures->create('merchant');

        $core = new Core();
        $core->createForCompositeRequest(['name'=>'Test Contact'], $merchant,[]);
        $result = $core->createForCompositeRequest(['name'=>'Test Contact'], $merchant,[]);

        $this->assertEquals('Test Contact', $result['name'],'');
    }

    public function testCreateForCompositeRequestCompositePayoutSaveOrFailFalse()
    {
        $merchant = $this->fixtures->create('merchant');

        $core = new Core();
        $result = $core->createForCompositeRequest(['name'=>'Test Contact'], $merchant,[], false);

        $this->assertEquals('Test Contact', $result['name'],'');
    }

    public function testCreateForCompositeRequestWithMetadataID()
    {
        $merchant = $this->fixtures->create('merchant');

        $core = new Core();
        $result = $core->createForCompositeRequest(['name'=>'Test Contact'], $merchant,[],true, ['id'=>'1000000contact']);
        $this->assertEquals('1000000contact', $result['id'],'');
    }

    public function testCreateForCompositeRequestWithMetadataCreatedAt()
    {
        $merchant = $this->fixtures->create('merchant');
        $currentTimeMs = round(microtime(true));
        $core = new Core();
        $result = $core->createForCompositeRequest(['name'=>'Test Contact'], $merchant,[],true, ['created_at'=>$currentTimeMs]);
        $this->assertEquals($currentTimeMs, $result['created_at'],'');
    }

    public function testCreateForCompositePayout()
    {
        $merchant = $this->fixtures->create('merchant');

        $service = new Service();
        $result = $service->createForCompositePayout(['name'=>'Test Contact'], [], $merchant,true,[]);

        $this->assertEquals('Test Contact', $result['name'],'');
    }

    public function testFetchContactsHavingSpaceInNameNoContacts()
    {
        $merchant = $this->fixtures->create('merchant');
        $creationTime = $merchant['created_at'];

        $repo = new Repository();
        $result = $repo->fetchContactsHavingSpaceInName([$merchant['id']], $creationTime, $creationTime);

        $this->assertEquals(0, sizeof($result),'');
    }

    public function testFetchContactsHavingSpaceInNameWithContacts()
    {
        $merchant = $this->fixtures->create('merchant');

        $core = new Core();
        $contact1 = $core->createForCompositeRequest(['name'=>' test test '], $merchant, []);
        $startTime = $contact1['created_at'];

        $contact2 = $core->createForCompositeRequest(['name'=>'test1 test1'], $merchant, []);
        $endTime = $contact2['created_at'];

        $repo = new Repository();
        $result = $repo->fetchContactsHavingSpaceInName([$merchant['id']], $startTime, $endTime);

        $this->assertEquals(1,sizeof($result),'');
        $this->assertArrayHasKey('name', $result[0],'');
        $this->assertEquals(' test test ', $result[0]['name'],'');
    }

    public function testFetchContactsHavingSpaceInTypeNoTypes()
    {
        $merchant = $this->fixtures->create('merchant');
        $creationTime = $merchant['created_at'];

        $repo = new Repository();
        $result = $repo->fetchContactsHavingSpaceInType([$merchant['id']], $creationTime, $creationTime);

        $this->assertEquals(0,sizeof($result),'');
    }
}
