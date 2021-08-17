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
    }

    private function createDefaultRiskNote()
    {
        $admin = $this->ba->getAdmin();

        $input = [
            'note'                       => 'This is a test note',
            'admin_id'                   => $admin['id'],
            'merchant_id'                => '10000000000001',
        ];

        $riskNote = (new RiskNotes\Entity)->build($input);

        (new RiskNotes\Repository())->saveOrFail($riskNote);

        return $riskNote;
    }

    private function deleteRiskNote($createdNote)
    {
        $createdNote->setDeletedAt();

        $createdNote->setDeletedBy('RzrpySprAdmnId');

        (new RiskNotes\Repository())->saveOrFail($createdNote);
    }

    public function testCreateRiskNoteWithoutPermission()
    {
        $this->startTest();
    }

    public function testCreateRiskNoteWithPermission()
    {
        $this->addPermission(Permission\Name::CREATE_MERCHANT_RISK_NOTES);

        $this->fixtures->create('merchant',
            [
                'id'    => '10000000000001',
            ]
        );

        $response = $this->startTest();

        $this->assertNotNull($response['id']);

        $this->assertNotNull($response['created_at']);
    }

    public function testDeleteRiskNoteWithInvalidRiskId()
    {
        $this->addPermission(Permission\Name::DELETE_MERCHANT_RISK_NOTES);

        $this->fixtures->create('merchant',
            [
                'id'    => '10000000000001',
            ]
        );

        $this->createDefaultRiskNote();

        s($this->startTest());
    }

    public function testDeleteRiskNoteWithInvalidMerchant()
    {
        $this->addPermission(Permission\Name::DELETE_MERCHANT_RISK_NOTES);

        $this->fixtures->create('merchant',
            [
                'id'    => '10000000000001',
            ]
        );

        $createdNoted = $this->createDefaultRiskNote();;

        $riskNoteId = ($createdNoted->toArrayPublic())['id'];

        $this->testData[__FUNCTION__]['request']['url']
            = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $riskNoteId,]);

        $this->startTest();
    }

    public function testDeleteRiskNoteWithoutPermission()
    {
        $this->ba->getAdmin();

        $this->fixtures->create('merchant',
            [
                'id'    => '10000000000001',
            ]
        );

        $createdNoted = $this->createDefaultRiskNote();

        $riskNoteId = ($createdNoted->toArrayPublic())['id'];

        $this->testData[__FUNCTION__]['request']['url']
            = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $riskNoteId,]);

        $this->startTest();
    }

    public function testDeleteRiskNoteWithValidMerchantRiskIdAndPermission()
    {
        $this->addPermission(Permission\Name::DELETE_MERCHANT_RISK_NOTES);

        $this->fixtures->create('merchant',
            [
                'id'    => '10000000000001',
            ]
        );

        $createdNotes = $this->createDefaultRiskNote();

        $riskNoteId = ($createdNotes->toArrayPublic())['id'];

        $this->testData[__FUNCTION__]['request']['url']
            = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $riskNoteId,]);

        $this->startTest();
    }

    public function testGetAllRiskNotesWithNoDeletes()
    {
        $this->fixtures->create('merchant', [
           'id'    => '10000000000001',
        ]);
        $this->createDefaultRiskNote();
        $this->createDefaultRiskNote();

        $response = $this->startTest();

        $this->assertSame(sizeof($response), 2);

        $this->assertNotNull($response[0]['id']);
        $this->assertNotNull($response[1]['id']);

        $this->assertNull($response[0]['deleted_at']);
        $this->assertNull($response[1]['deleted_at']);

        $this->assertNull($response[0]['deleted_by']);
        $this->assertNull($response[1]['deleted_by']);
    }

    public function testGetAllRiskNotesShowSoftDeletes()
    {
        $this->fixtures->create('merchant', [
            'id'    => '10000000000001',
        ]);
        $this->createDefaultRiskNote();
        $createdNote = $this->createDefaultRiskNote();

        $this->deleteRiskNote($createdNote);

        $response = $this->startTest();

        $this->assertSame(sizeof($response), 2);

        $this->assertNotNull($response[0]['id']);
        $this->assertNotNull($response[1]['id']);

        $this->assertNotNull($response[0]['deleted_at']);
        $this->assertNull($response[1]['deleted_at']);

        $this->assertNotNull($response[0]['deleted_by']);
        $this->assertNull($response[1]['deleted_by']);
    }

    public function testGetAllRiskNotesHideSoftDeletes()
    {
        $this->fixtures->create('merchant', [
            'id'    => '10000000000001',
        ]);
        $this->createDefaultRiskNote();
        $createdNote = $this->createDefaultRiskNote();

        $this->deleteRiskNote($createdNote);

        $response = $this->startTest();

        $this->assertSame(sizeof($response), 1);

        $this->assertNotNull($response[0]['id']);

        $this->assertNull($response[0]['deleted_at']);

        $this->assertNull($response[0]['deleted_by']);
    }
}
