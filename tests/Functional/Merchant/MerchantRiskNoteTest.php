<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Constants;
use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\RiskNotes;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantRiskNoteTest extends TestCase
{
    use RequestResponseFlowTrait;

    const DEFAULT_NOTE = 'This is a test note';
    const DEFAULT_MERCHANT_ID = '10000000000001';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantRiskNoteTestData.php';

        parent::setUp();

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $getNotesPerm = $this->fixtures->create(Constants\Entity::PERMISSION, [Permission\Entity::NAME => Permission\Name::GET_MERCHANT_RISK_NOTES]);

        $role->permissions()->attach($getNotesPerm->getId());
    }

    private function addPermission($permissionName)
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $createNotesPerm = $this->fixtures->create(Constants\Entity::PERMISSION, [Permission\Entity::NAME => $permissionName]);

        $role->permissions()->attach($createNotesPerm->getId());

        return $admin;
    }

    private function createDefaultRiskNote($merchant)
    {
        $admin = $this->ba->getAdmin();

        $input = [
            'note'                       => 'This is a test note',
        ];

        $riskNote = (new RiskNotes\Entity)->build($input);

        $riskNote->merchant()->associate($merchant);

        $riskNote->admin()->associate($admin);

        (new RiskNotes\Repository())->saveOrFail($riskNote);

        return $riskNote;
    }

    private function deleteRiskNote($createdNote)
    {
        $createdNote->setDeletedAt();

        $createdNote->deletedByAdmin()->associate('RzrpySprAdmnId');

        (new RiskNotes\Repository())->saveOrFail($createdNote);
    }

    public function testCreateRiskNoteWithoutPermission()
    {
        $this->startTest();
    }

    public function testCreateRiskNoteWithPermission()
    {
        $admin = $this->addPermission(Permission\Name::CREATE_MERCHANT_RISK_NOTES);

        $this->fixtures->create('merchant',
            [
                'id'    => '10000000000001',
            ]
        );

        $response = $this->startTest();

        $this->assertNotNull($response['id']);

        $this->assertNotNull($response['created_at']);

        $this->assertNotNull($response['note']);

        $this->assertEquals($response['admin_id'], $admin->getId());

        $this->assertEquals($response['admin']['name'], $admin->getName());
    }

    public function testDeleteRiskNoteWithInvalidRiskId()
    {
        $this->addPermission(Permission\Name::DELETE_MERCHANT_RISK_NOTES);

        $merchant = $this->fixtures->create('merchant',
            [
                'id'    => '10000000000001',
            ]
        );

        $this->createDefaultRiskNote($merchant);

        $this->startTest();
    }

    public function testDeleteRiskNoteWithInvalidMerchant()
    {
        $this->addPermission(Permission\Name::DELETE_MERCHANT_RISK_NOTES);

        $merchant = $this->fixtures->create('merchant',
            [
                'id'    => '10000000000001',
            ]
        );

        $createdNoted = $this->createDefaultRiskNote($merchant);

        $riskNoteId = ($createdNoted->toArrayPublic())['id'];

        $this->testData[__FUNCTION__]['request']['url']
            = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $riskNoteId,]);

        $this->startTest();
    }

    public function testDeleteRiskNoteWithoutPermission()
    {
        $this->ba->getAdmin();

        $merchantId = $this->fixtures->create('merchant',
            [
                'id'    => '10000000000001',
            ]
        );

        $createdNoted = $this->createDefaultRiskNote($merchantId);

        $riskNoteId = ($createdNoted->toArrayPublic())['id'];

        $this->testData[__FUNCTION__]['request']['url']
            = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $riskNoteId,]);

        $this->startTest();
    }

    public function testDeleteRiskNoteWithValidMerchantRiskIdAndPermission()
    {
        $this->addPermission(Permission\Name::DELETE_MERCHANT_RISK_NOTES);

        $merchant = $this->fixtures->create('merchant',
            [
                'id'    => '10000000000001',
            ]
        );

        $createdNotes = $this->createDefaultRiskNote($merchant);

        $riskNoteId = ($createdNotes->toArrayPublic())['id'];

        $this->testData[__FUNCTION__]['request']['url']
            = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $riskNoteId,]);

        $this->startTest();
    }

    public function testGetAllRiskNotesWithNoDeletes()
    {
        $merchant = $this->fixtures->create('merchant', [
           'id'    => '10000000000001',
        ]);
        $this->createDefaultRiskNote($merchant);
        $this->createDefaultRiskNote($merchant);

        $response = $this->startTest();

        $items = $response['items'];

        $admin = $this->ba->getAdmin();

        $this->assertSame(sizeof($items), 2);

        $this->assertNotNull($items[0]['id']);
        $this->assertNotNull($items[1]['id']);

        $this->assertNotNull($items[0]['note']);
        $this->assertNotNull($items[1]['note']);

        $this->assertEquals($items[0]['admin']['name'], $admin->getName());
        $this->assertEquals($items[0]['merchant_id'], $merchant->getId());

        $this->assertNull($items[0]['deleted_at']);
        $this->assertNull($items[1]['deleted_at']);

        $this->assertNull($items[0]['deleted_by']);
        $this->assertNull($items[1]['deleted_by']);

        $this->assertNull($items[0]['deleted_by_admin']);
        $this->assertNull($items[1]['deleted_by_admin']);
    }

    public function testGetAllRiskNotesShowSoftDeletes()
    {
        $merchant = $this->fixtures->create('merchant', [
            'id'    => '10000000000001',
        ]);
        $this->createDefaultRiskNote($merchant);
        $createdNote = $this->createDefaultRiskNote($merchant);

        $this->deleteRiskNote($createdNote);

        $response = $this->startTest();

        $items = $response['items'];

        $admin = $this->ba->getAdmin();

        $this->assertSame(sizeof($items), 2);

        $this->assertNotNull($items[0]['id']);
        $this->assertNotNull($items[1]['id']);

        $this->assertNotNull($items[0]['note']);
        $this->assertNotNull($items[1]['note']);

        $this->assertEquals($items[0]['admin']['name'], $admin->getName());

        $this->assertNotNull($items[0]['deleted_at']);
        $this->assertNull($items[1]['deleted_at']);

        $this->assertNotNull($items[0]['deleted_by']);
        $this->assertNull($items[1]['deleted_by']);

        $this->assertEquals($items[0]['deleted_by'],$admin->getId());

        $this->assertNotNull($items[0]['deleted_by_admin']);
        $this->assertNull($items[1]['deleted_by_admin']);

        $this->assertEquals($items[0]['deleted_by_admin']['name'],$admin->getName());

    }

    public function testGetAllRiskNotesHideSoftDeletes()
    {
        $merchant = $this->fixtures->create('merchant', [
            'id'    => '10000000000001',
        ]);
        $this->createDefaultRiskNote($merchant);
        $createdNote = $this->createDefaultRiskNote($merchant);

        $this->deleteRiskNote($createdNote);

        $response = $this->startTest();

        $items = $response['items'];

        $this->assertSame(sizeof($items), 1);

        $this->assertNotNull($items[0]['id']);

        $this->assertNotNull($items[0]['note']);

        $this->assertNull($items[0]['deleted_at']);

        $this->assertNull($items[0]['deleted_by']);
    }
}
