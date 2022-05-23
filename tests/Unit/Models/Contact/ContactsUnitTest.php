<?php

namespace RZP\Tests\Functional\Contacts;

use RZP\Constants\Mode;
use RZP\Models\Contact\Repository;
use RZP\Models\Contact\Core;
use RZP\Models\Contact\Service;
use RZP\Models\Contact\Type;
use RZP\Models\Settings;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Exception\BadRequestValidationFailureException;

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

    public function testCreateForCompositeRequestWithContactTypeDefault()
    {
        $merchant = $this->fixtures->create('merchant');

        $core = new Core();

        $result = $core->createForCompositeRequest(['name' => 'Test Contact', 'type' => 'customer'], $merchant, [],true);

        $this->assertEquals('Test Contact', $result['name'],'');
        $this->assertEquals('customer', $result['type'],'');
    }

    public function testCreateForCompositeRequestWithContactTypeNonDefault()
    {
        $merchant = $this->fixtures->on('live')->create('merchant');

        $accessor = Settings\Accessor::for($merchant, Settings\Module::CONTACT_TYPE, Mode::LIVE);

        $accessor->upsert(['custom123' => ''])->save();

        $core = new Core();

        $result = $core->createForCompositeRequest(['name' => 'Test Contact', 'type' => 'custom123'], $merchant, [],true);

        $this->assertEquals('Test Contact', $result['name'],'');
        $this->assertEquals('custom123', $result['type'],'');
    }

    public function testCreateForCompositeRequestWithInvalidInputMissingNameField()
    {
        $merchant = $this->fixtures->on('live')->create('merchant');

        $core = new Core();

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The name field is required.');

        $core->createForCompositeRequest(['name' => '', 'type' => 'employee'], $merchant, [],true);
    }

    public function testCreateForCompositeRequestWithContactTypeHavingSpaces()
    {
        $merchant = $this->fixtures->on('live')->create('merchant');

        $core = new Core();

        $result = $core->createForCompositeRequest(['name' => 'Test Contact', 'type' => ' employee  '], $merchant, [],true);

        $this->assertEquals('Test Contact', $result['name'],'');
        $this->assertEquals('employee', $result['type'],'');
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

        $contact2 = $core->createForCompositeRequest(['name'=>'test1 test1'], $merchant, []);

        $contacts = $this->getDbEntities('contact');

        $this->assertEquals(2, sizeof($contacts),'');
        $this->assertArrayHasKey('name', $contacts[0],'');
        $this->assertEquals('test test', $contacts[0]['name'],'');
        $this->assertArrayHasKey('name', $contacts[1],'');
        $this->assertEquals('test1 test1', $contacts[1]['name'],'');
    }

    public function testFetchContactsHavingSpaceInTypeNoTypes()
    {
        $merchant = $this->fixtures->create('merchant');
        $creationTime = $merchant['created_at'];

        $repo = new Repository();
        $result = $repo->fetchContactsHavingSpaceInType([$merchant['id']], $creationTime, $creationTime);

        $this->assertEquals(0,sizeof($result),'');
    }

    public function testTrimType(){
        $typeObj = new Type();
        $merchant = $this->fixtures->create('merchant');
        $typeObj->trimType(' test ', $merchant);

        $allTypes = $typeObj->getAll($merchant)['items'];

        $hasType = 0;
        for($i=0;$i<sizeof($allTypes);$i++){
            if($allTypes[$i]['type'] == 'test'){
                $hasType = 1;
            }
        }

        $this->assertEquals(1,$hasType,'');
    }
}
