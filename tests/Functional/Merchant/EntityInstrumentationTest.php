<?php

namespace RZP\Tests\Functional\Merchant;

use Illuminate\Support\Facades\Event;

use RZP\Constants\Entity as E;
use RZP\Constants\Metric;
use RZP\Models\Merchant\Repository as MerchantRepo;
use RZP\Models\Merchant\Detail\Repository as MerchantDetailRepo;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;
use RZP\Events\EntityInstrumentationEvent;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;

class EntityInstrumentationTest extends TestCase
{
    use DbEntityFetchTrait;

    public function testMerchantInstrumentation()
    {
        Event::Fake([EntityInstrumentationEvent::class]);

        /** @var MerchantEntity $merchant */
        $merchant = $this->fixtures->merchant->create();
        Event::assertDispatched(EntityInstrumentationEvent::class,
            function(EntityInstrumentationEvent $e) {
                return (Metric::ENTITY_CREATED === $e->eventName)
                    and (E::MERCHANT == $e->dimensions[Metric::LABEL_ENTITY_NAME]);
            }
        );

        $repo = (new MerchantRepo);
        $merchant = $repo->find($merchant->id);
        Event::assertDispatched(EntityInstrumentationEvent::class,
            function(EntityInstrumentationEvent $e) {
                return (Metric::ENTITY_RETRIEVED === $e->eventName)
                    and (E::MERCHANT == $e->dimensions[Metric::LABEL_ENTITY_NAME]);
            }
        );

        $merchant->name = 'Updated Test Merchant Name';
        $repo->saveOrFail($merchant);
        Event::assertDispatched(EntityInstrumentationEvent::class,
            function(EntityInstrumentationEvent $e) {
                return (Metric::ENTITY_UPDATED === $e->eventName)
                    and (E::MERCHANT == $e->dimensions[Metric::LABEL_ENTITY_NAME]);
            }
        );
    }

    public function testMerchantDetailsInstrumentation()
    {
        Event::Fake([EntityInstrumentationEvent::class]);

        /** @var MerchantDetailEntity $merchantDetail */
        $merchantDetail = $this->fixtures->merchantDetail->create();
        Event::assertDispatched(EntityInstrumentationEvent::class,
            function(EntityInstrumentationEvent $e) {
                return (Metric::ENTITY_CREATED === $e->eventName)
                    and (E::MERCHANT_DETAIL == $e->dimensions[Metric::LABEL_ENTITY_NAME]);
            }
        );

        $merchantDetail = $this->getDbEntity(E::MERCHANT_DETAIL, [
            MerchantDetailEntity::MERCHANT_ID => $merchantDetail->merchant_id
        ]);
        Event::assertDispatched(EntityInstrumentationEvent::class,
            function(EntityInstrumentationEvent $e) {
                return (Metric::ENTITY_RETRIEVED === $e->eventName)
                    and (E::MERCHANT_DETAIL == $e->dimensions[Metric::LABEL_ENTITY_NAME]);
            }
        );

        $repo = (new MerchantDetailRepo);
        $merchantDetail->business_name = 'Updated Test Merchant Name';
        $repo->saveOrFail($merchantDetail);
        Event::assertDispatched(EntityInstrumentationEvent::class,
            function(EntityInstrumentationEvent $e) {
                return (Metric::ENTITY_UPDATED === $e->eventName)
                    and (E::MERCHANT_DETAIL == $e->dimensions[Metric::LABEL_ENTITY_NAME]);
            }
        );
    }

    public function testInstrumentationWithQueryBuilder()
    {
        $merchant = $this->fixtures->merchant->create();

        Event::Fake([EntityInstrumentationEvent::class]);

        $repo = (new MerchantRepo);
        $repo->getMerchantOrg($merchant->id);
        Event::assertDispatched(EntityInstrumentationEvent::class,
            function(EntityInstrumentationEvent $e) {
                return (Metric::ENTITY_RETRIEVED === $e->eventName)
                    and (E::MERCHANT == $e->dimensions[Metric::LABEL_ENTITY_NAME]);
            }
        );
    }
}
