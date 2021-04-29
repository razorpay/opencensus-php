<?php

namespace Tests\Functional\Merchant;

use RZP\Constants\Entity as E;
use RZP\Models\Base\EntityInstrumentationObserver;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;
use RZP\Events\EntityInstrumentationEvent;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use Illuminate\Support\Facades\Event;
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
                return (EntityInstrumentationObserver::CREATED === $e->eventName)
                    and (E::MERCHANT == $e->entityName);
            }
        );

        $merchant = $this->getDbEntityById(E::MERCHANT, $merchant->id);
        Event::assertDispatched(EntityInstrumentationEvent::class,
            function(EntityInstrumentationEvent $e) {
                return (EntityInstrumentationObserver::RETRIEVED === $e->eventName)
                    and (E::MERCHANT == $e->entityName);
            }
        );

        $merchant->name = 'Updated Test Merchant Name';
        $merchant->saveOrFail();
        Event::assertDispatched(EntityInstrumentationEvent::class,
            function(EntityInstrumentationEvent $e) {
                return (EntityInstrumentationObserver::UPDATED === $e->eventName)
                    and (E::MERCHANT == $e->entityName);
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
                return (EntityInstrumentationObserver::CREATED === $e->eventName)
                    and (E::MERCHANT_DETAIL == $e->entityName);
            }
        );

        $merchantDetail = $this->getDbEntity(E::MERCHANT_DETAIL, [
            MerchantDetailEntity::MERCHANT_ID => $merchantDetail->merchant_id
        ]);
        Event::assertDispatched(EntityInstrumentationEvent::class,
            function(EntityInstrumentationEvent $e) {
                return (EntityInstrumentationObserver::RETRIEVED === $e->eventName)
                    and (E::MERCHANT_DETAIL == $e->entityName);
            }
        );

        $merchantDetail->business_name = 'Updated Test Merchant Name';
        $merchantDetail->saveOrFail();
        Event::assertDispatched(EntityInstrumentationEvent::class,
            function(EntityInstrumentationEvent $e) {
                return (EntityInstrumentationObserver::UPDATED === $e->eventName)
                    and (E::MERCHANT_DETAIL == $e->entityName);
            }
        );
    }
}
