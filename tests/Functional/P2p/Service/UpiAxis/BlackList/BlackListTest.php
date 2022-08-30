<?php

namespace Functional\P2p\Service\UpiAxis\BlackList;

use RZP\Exception\RuntimeException;
use RZP\Models\Vpa\Entity;
use RZP\Models\P2p\BlackList\Entity as BlackListEntity;
use RZP\Models\P2p\BankAccount\Entity as BankAccountEntity;

class BlackListTest extends \RZP\Tests\P2p\Service\UpiAxis\TestCase
{
    public function testCreateVpaBlackList()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $vpas = $helper->fetchAllVpa();

        $vpa = $vpas['items'][0];

        $helper = $this->getBlackListHelper()->setMerchantOnAuth(true);

        $input = array(
            'type'              => 'vpa',
            'username'          => $vpa[Entity::USERNAME],
            'handle'            => $vpa[Entity::HANDLE],
        );

        $response   = $helper->create($input);

        $expectedId = Entity::verifyIdAndSilentlyStripSign($vpa[Entity::ID]);

        $this->assertEquals($expectedId, $response[BlackListEntity::ENTITY_ID]);

        $this->assertEquals($vpa[Entity::ENTITY], $response[BlackListEntity::TYPE]);
    }

    public function testCreateBankAccountBlackList()
    {
        $helper = $this->getBeneficiaryHelper();

        $helper->withSchemaValidated();

        $response = $helper->validateBankAccount([
                        'ifsc' => 'AXIS0000180',
                     ]);

        $this->assertArraySubset([
                 'validated'         => true,
                 'type'              => 'bank_account',
                 'address'           => '987654321000@AXIS0000180.ifsc.npci',
                'beneficiary_name'  => 'Razorpay Customer',
         ], $response);

        $bankAccountId = $response[Entity::ID];

        $input = array(
            'type'              => 'bank_account',
            'account_number'    => '987654321000',
            'ifsc'              => 'AXIS0000180',
            'beneficiary_name'  => 'Razorpay Customer',
        );

        $helper = $this->getBlackListHelper()->setMerchantOnAuth(true);

        $response   = $helper->create($input);

        $expectedId = BankAccountEntity::verifyIdAndSilentlyStripSign($bankAccountId);

        $this->assertEquals($expectedId, $response[BlackListEntity::ENTITY_ID]);

        $this->assertEquals('bank_account', $response[BlackListEntity::TYPE]);
    }

    public function testRemove()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $vpas = $helper->fetchAllVpa();

        $vpa = $vpas['items'][0];

        $this->createBlackList($vpa);

        $helper = $this->getBlackListHelper()->setMerchantOnAuth(true);

        $response = $helper->fetchAll();

        $expectedId = Entity::verifyIdAndSilentlyStripSign($vpa[Entity::ID]);

        $this->assertEquals($expectedId, $response['items'][0][BlackListEntity::ENTITY_ID]);

        $this->assertEquals($vpa[Entity::ENTITY], $response['items'][0][BlackListEntity::TYPE]);

        $expectedId = Entity::verifyIdAndSilentlyStripSign($vpa[Entity::ID]);

        $input = array(
            'type'              => 'vpa',
            'username'          => $vpa[Entity::USERNAME],
            'handle'            => $vpa[Entity::HANDLE],
        );

        $response  = $helper->remove($input);

        $this->assertNotNull($response['deleted_at']);
    }

    public function testRemoveAndReAddingItBack()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $vpas = $helper->fetchAllVpa();

        $vpa = $vpas['items'][0];

        $this->createBlackList($vpa);

        $helper = $this->getBlackListHelper()->setMerchantOnAuth(true);

        $response = $helper->fetchAll();

        $expectedId = Entity::verifyIdAndSilentlyStripSign($vpa[Entity::ID]);

        $this->assertEquals($expectedId, $response['items'][0][BlackListEntity::ENTITY_ID]);

        $this->assertEquals($vpa[Entity::ENTITY], $response['items'][0][BlackListEntity::TYPE]);

        $expectedId = Entity::verifyIdAndSilentlyStripSign($vpa[Entity::ID]);

        $input = array(
            'type'              => 'vpa',
            'username'          => $vpa[Entity::USERNAME],
            'handle'            => $vpa[Entity::HANDLE],
        );

        $response = $helper->remove($input);

        $this->createBlackList($vpa);

        $this->assertNotNull($response['deleted_at']);
    }

    public function testFetchAll()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $vpas = $helper->fetchAllVpa();

        $vpa = $vpas['items'][0];

        $this->createBlackList($vpa);

        $helper = $this->getBlackListHelper()->setMerchantOnAuth(true);

        $response = $helper->fetchAll();

        $expectedId = Entity::verifyIdAndSilentlyStripSign($vpa[Entity::ID]);

        $this->assertEquals($expectedId, $response['items'][0][BlackListEntity::ENTITY_ID]);

        $this->assertEquals($vpa[Entity::ENTITY], $response['items'][0][BlackListEntity::TYPE]);
    }


    protected function createBlackList($vpa)
    {
        $helper = $this->getBlackListHelper()->setMerchantOnAuth(true);

        $input = array(
            'type'              => 'vpa',
            'username'          => $vpa[Entity::USERNAME],
            'handle'            => $vpa[Entity::HANDLE],
        );

        $helper->create($input);
    }

    public function testEntity()
    {
        $entity         = new BlackListEntity();
        $entityClass    = $entity->getEntity();
        $entityName     = $entityClass->getP2pEntityName();
        $this->assertEquals('blacklist', $entityName);
    }
}
