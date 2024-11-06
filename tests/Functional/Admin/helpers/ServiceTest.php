<?php

namespace Rzp\Tests\Functional\Admin\helpers;

use Config;
use RZP\Models\Admin\Service;
use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\AsvFindOrFailAndFindOrFailPublicTrait;

class ServiceTest extends TestCase
{
    use DbEntityFetchTrait;
    use AsvFindOrFailAndFindOrFailPublicTrait;

    public function testFetchEntityById()
    {
        $id = PublicEntity::generateUniqueId();
        Config::set('applications.asv_v2.splitz_send_filter_to_asv', $id);

        $testData    = [
            [
                "primary_key" => "id",
                "primary_key_value" => PublicEntity::generateUniqueId(),
                "entity" => "merchant"
            ],
            [
                "primary_key" => "merchant_id",
                "primary_key_value" => PublicEntity::generateUniqueId(),
                "entity" => "merchant_detail"
            ],
            [
                "primary_key" => "id",
                "primary_key_value" => PublicEntity::generateUniqueId(),
                "entity" => "stakeholder"
            ],
            [
                "primary_key" => "id",
                "primary_key_value" => PublicEntity::generateUniqueId(),
                "entity" => "merchant_document"
            ],
            [
                "primary_key" => "id",
                "primary_key_value" => PublicEntity::generateUniqueId(),
                "entity" => "merchant_email"

            ],
            [
                "primary_key" => "id",
                "primary_key_value" => PublicEntity::generateUniqueId(),
                "entity" => "merchant_website"
            ],
            [
                "primary_key" => "id",
                "primary_key_value" => PublicEntity::generateUniqueId(),
                "entity" => "merchant_business_detail"
            ],
        ];

        foreach ($testData as $key => $data) {
            if ($data['entity'] == "merchant_detail") {
                $this->fixtures->create("merchant", ["id" => $data['primary_key_value']]);
            }

            $this->fixtures->create($data['entity'], [$data['primary_key'] => $data['primary_key_value']]);

            $this->setSplitzWithOutput("false", 1);

            $entityWithSplitzOff = (new Service())->fetchEntityById($data['entity'], $data['primary_key_value']);

            $this->setSplitzWithOutput("true", 1);

            $entityWithSplitzOn = (new Service())->fetchEntityById($data['entity'], $data['primary_key_value']);

            $this->assertEquals($entityWithSplitzOff, $entityWithSplitzOn, "response does not match for ".$data['entity']);
        }

        // verify that other entities are working fine
        $id = PublicEntity::generateUniqueId();
        $this->fixtures->create("bvs_validation", ["validation_id" => $id]);
        $otherEntity = (new Service())->fetchEntityById("bvs_validation", $id);
        $this->assertEquals($otherEntity["validation_id"], $id);

    }

}
