<?php

namespace Unit\Models\Merchant\AccessMap;

use Config;
use RZP\Tests\Functional;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\AccessMap\Repository;
use Unit\Models\Merchant\TestingHelper\RepositoryTestHelper;

class RepositoryTest extends RepositoryTestHelper
{
    use Functional\AsvFindOrFailAndFindOrFailPublicTrait;

    public function testFetchAffiliatedPartnersForSubmerchantEmptyEntityOwner()
    {
        Config::set('applications.asv_v2.splitz_send_filter_to_asv', PublicEntity::generateUniqueId());

        $id1 = PublicEntity::generateUniqueId();
        $this->fixtures->create('merchant', ['id' => $id1]);
        $accessMap = [
            'id'              => 'CMe2wjY0hiWBrL',
            'entity_type'     => 'application',
            'entity_id'       => PublicEntity::generateUniqueId(),
            'merchant_id'     => $id1,
            'entity_owner_id' => null,
        ];
        $repository = new Repository();
        $this->fixtures->create('merchant_access_map', $accessMap);
        $repository->repo->transactionOnLiveAndTestAndAsv(function () use ($id1, $repository) {
            $accessMaps = $repository->fetchAffiliatedPartnersForSubmerchant($id1);
            $this->assertEquals(0, sizeof($accessMaps));
        });
    }

    public function testFetchAffiliatedPartnersForSubmerchantValidEntityOwner()
    {
        Config::set('applications.asv_v2.splitz_send_filter_to_asv', PublicEntity::generateUniqueId());

        $id1 = PublicEntity::generateUniqueId();
        $id2 = PublicEntity::generateUniqueId();
        $this->fixtures->create('merchant', ['id' => $id1]);
        $this->fixtures->create('merchant', ['id' => $id2]);
        $accessMap = [
            'id'              => 'CMe2wjY0hiWBrL',
            'entity_type'     => 'application',
            'entity_id'       => PublicEntity::generateUniqueId(),
            'merchant_id'     => $id1,
            'entity_owner_id' => $id2,
        ];
        $repository = new Repository();
        $this->fixtures->create('merchant_access_map', $accessMap);
        $repository->repo->transactionOnLiveAndTestAndAsv(function () use ($id2, $id1, $repository) {
            $accessMaps = $repository->fetchAffiliatedPartnersForSubmerchant($id1);
            $this->assertEquals(1, sizeof($accessMaps));
            $this->assertEquals($id2, $accessMaps[0]['entity_owner_id']);
            $this->assertNotNull($accessMaps[0]->entityOwner);
        });
    }
}
