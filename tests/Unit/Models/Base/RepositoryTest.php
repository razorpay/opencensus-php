<?php

namespace RZP\Tests\Unit\Models\Base;

use Database\Connection;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant;
use RZP\Models\User;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Exception;

class RepositoryTest extends TestCase
{
    use RepositoryUpdateTestAndLive;
    public function testVerifyIdGenerationWhenTestAndLiveEntityAreInSync()
    {
        $repo = (new Merchant\Repository);

        $entity = (new Merchant\Entity);

        $this->expectException(
            'RZP\Exception\LogicException',
            'Unique id not generated for the entity');

        $repo->saveOrFail($entity);
    }

    /**
     * @throws \Throwable
     */
    public function testVerifyTestAndLiveTxnMigrationToTestLiveAndAsv(){

        // entity should get created in live , test and asv db if entity is being supported in asv
        $repo = new Merchant\BusinessDetail\Repository();
        $businessDetailEntity = (new Merchant\BusinessDetail\Entity());
        $id = $businessDetailEntity->generateUniqueId();
        $businessDetailEntity->setId($id);
        $businessDetailEntity->fill([
            Merchant\BusinessDetail\Entity::MERCHANT_ID => $id,
        ]);
        $repo->repo->transactionOnLiveAndTest(function() use ($businessDetailEntity, $repo){
            $repo->saveOrFail($businessDetailEntity);
        });
        $asvWriterEntity = $repo->findOrFail($businessDetailEntity->getId(), '*',Connection::ASV_WRITER);
        $liveEntity = $repo->findOrFail($businessDetailEntity->getId(),'*',  Connection::LIVE);
        $testEntity = $repo->findOrFail($businessDetailEntity->getId(), '*', Connection::TEST);
        $this->assertNotNull($asvWriterEntity);
        $this->assertNotNull($liveEntity);
        $this->assertNotNull($testEntity);


        // entity should get updated in live , test and asv db if entity is being supported in asv
        $repo = new Merchant\BusinessDetail\Repository();
        $businessDetailEntity = (new Merchant\BusinessDetail\Entity());
        $id = $businessDetailEntity->generateUniqueId();
        $businessDetailEntity->setId($id);
        $businessDetailEntity->fill([
            Merchant\BusinessDetail\Entity::MERCHANT_ID => $id,
        ]);
        $repo->saveOrFail($businessDetailEntity);
        $repo->repo->transactionOnLiveAndTest(function() use ($businessDetailEntity, $repo){
            $businessDetailEntity->setPgUseCase("true");
            $repo->saveOrFail($businessDetailEntity);
        });
        $asvWriterEntity = $repo->findOrFail($businessDetailEntity->getId(), '*',Connection::ASV_WRITER);
        $liveEntity = $repo->findOrFail($businessDetailEntity->getId(),'*',  Connection::LIVE);
        $testEntity = $repo->findOrFail($businessDetailEntity->getId(), '*', Connection::TEST);
        $this->assertEquals($asvWriterEntity->getPgUseCase(), 'true');
        $this->assertEquals($liveEntity->getPgUseCase(), 'true');
        $this->assertEquals($testEntity->getPgUseCase(), 'true');

        // entry should get created in live , test and if entity is being not supported in asv
        $repo = new Merchant\BvsValidation\Repository;
        $bvsValidationEntity = (new Merchant\BvsValidation\Entity());
        $id = $bvsValidationEntity::generateUniqueId();
        $bvsValidationEntity->fill([
            Merchant\BvsValidation\Entity::VALIDATION_ID => $id,
            Merchant\BvsValidation\Entity::ARTEFACT_TYPE => "pan",
            Merchant\BvsValidation\Entity::OWNER_ID => $id,
            Merchant\BvsValidation\Entity::OWNER_TYPE => "merchant"
        ]);
        $repo->repo->transactionOnLiveAndTest(function () use ($bvsValidationEntity, $repo){
            $repo->saveOrFail($bvsValidationEntity);
        });
        $liveEntity = $repo->findOrFail($bvsValidationEntity->getValidationId(), '*',Connection::LIVE);
        $testEntity = $repo->findOrFail($bvsValidationEntity->getValidationId(), '*',Connection::TEST);
        $this->assertNotNull($liveEntity);
        $this->assertNotNull($testEntity);

        // entry should get updated in live , test and if entity is being not supported in asv
        $repo = new Merchant\BvsValidation\Repository;
        $bvsValidationEntity = (new Merchant\BvsValidation\Entity());
        $id = $bvsValidationEntity::generateUniqueId();
        $bvsValidationEntity->fill([
            Merchant\BvsValidation\Entity::VALIDATION_ID => $id,
            Merchant\BvsValidation\Entity::ARTEFACT_TYPE => "pan",
            Merchant\BvsValidation\Entity::OWNER_ID => $id,
            Merchant\BvsValidation\Entity::OWNER_TYPE => "merchant"
        ]);
        $repo->saveOrFail($bvsValidationEntity);
        $repo->repo->transactionOnLiveAndTest(function() use ($bvsValidationEntity, $repo){
            $bvsValidationEntity->setArtefactType("test_artefact");
            $repo->saveOrFail($bvsValidationEntity);
        });
        $liveEntity = $repo->findOrFail($bvsValidationEntity->getValidationId(), '*',Connection::LIVE);
        $testEntity = $repo->findOrFail($bvsValidationEntity->getValidationId(), '*',Connection::TEST);
        $this->assertEquals($liveEntity->getArtefactType(), "test_artefact");
        $this->assertEquals($testEntity->getArtefactType(), "test_artefact");
    }

    public function testValidateEntitiesMatchForUser()
    {
        $liveUserEntity = new User\Entity();
        $testUserEntity = new User\Entity();

        $id = $liveUserEntity->generateUniqueId();
        $liveUserEntity->setId($id);
        $testUserEntity->setId($id);

        $liveUserEntity->fill([
           User\Entity::WRONG_2FA_ATTEMPTS => 2,
           User\Entity::SECOND_FACTOR_AUTH => 1
        ]);

        $testUserEntity->fill([
            User\Entity::WRONG_2FA_ATTEMPTS => 0,
            User\Entity::SECOND_FACTOR_AUTH => 0
        ]);

        $this->validateEntitiesMatch($testUserEntity, $liveUserEntity);

    }

    public function testValidateEntitiesThrowsException()
    {
        $liveMerchantDetailEntity  = new Merchant\Detail\Entity();
        $testMerchantDetailEntity  = new Merchant\Detail\Entity();

        $id = $liveMerchantDetailEntity->generateUniqueId();

        $promoterPan = 'AAAPA1234J';

        $liveMerchantDetailEntity->fill([
            Merchant\Detail\Entity::MERCHANT_ID               => $id,
            Merchant\Detail\Entity::BUSINESS_TYPE             => 5,
            Merchant\Detail\Entity::BUSINESS_CATEGORY         => Merchant\Detail\BusinessCategoriesV2\BusinessCategory::IT_AND_SOFTWARE,
            Merchant\Detail\Entity::BUSINESS_SUBCATEGORY      => Merchant\Detail\BusinessCategoriesV2\BusinessCategory::CONSULTING_AND_OUTSOURCING,
            Merchant\Detail\Entity::ACTIVATION_FLOW           => Merchant\Detail\ActivationFlow::WHITELIST,
            Merchant\Detail\Entity::PROMOTER_PAN              => $promoterPan,
            Merchant\Detail\Entity::ACTIVATION_FORM_MILESTONE => Merchant\Detail\Constants::L2_SUBMISSION,
            Merchant\Detail\Entity::POI_VERIFICATION_STATUS   => Merchant\Detail\Constants::VERIFIED,
            Merchant\Detail\Entity::ACTIVATION_STATUS         => Merchant\Constants::ACTIVATED,
            Merchant\Detail\Entity::SUBMITTED                 => true
        ]);


        $testMerchantDetailEntity->fill([
            Merchant\Detail\Entity::MERCHANT_ID               => $id,
            Merchant\Detail\Entity::BUSINESS_TYPE             => 4,
            Merchant\Detail\Entity::BUSINESS_CATEGORY         => Merchant\Detail\BusinessCategoriesV2\BusinessCategory::IT_AND_SOFTWARE,
            Merchant\Detail\Entity::BUSINESS_SUBCATEGORY      => Merchant\Detail\BusinessCategoriesV2\BusinessCategory::CONSULTING_AND_OUTSOURCING,
            Merchant\Detail\Entity::ACTIVATION_FLOW           => Merchant\Detail\ActivationFlow::BLACKLIST,
            Merchant\Detail\Entity::PROMOTER_PAN              => $promoterPan,
            Merchant\Detail\Entity::ACTIVATION_FORM_MILESTONE => Merchant\Detail\Constants::L1_SUBMISSION,
            Merchant\Detail\Entity::POI_VERIFICATION_STATUS   => Merchant\Detail\Constants::VERIFIED,
            Merchant\Detail\Entity::ACTIVATION_STATUS         => Merchant\Detail\Constants::UNDER_REVIEW,
            Merchant\Detail\Entity::SUBMITTED                 => false
        ]);

        $this->expectException(Exception\LogicException::class);

        $this->validateEntitiesMatch($testMerchantDetailEntity, $liveMerchantDetailEntity);

    }

}
