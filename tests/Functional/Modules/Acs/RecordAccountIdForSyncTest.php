<?php

namespace RZP\Tests\Functional\Modules\Acs;

use Illuminate\Support\Facades\Event;

use RZP\Models\Merchant\Repository as MerchantRepo;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Repository as MerchantDetailRepo;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;
use RZP\Models\Merchant\Stakeholder\Repository as StakeholderRepo;
use RZP\Models\Merchant\Stakeholder\Entity as StakeholderEntity;
use RZP\Models\Merchant\Document\Repository as DocumentRepo;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;
use RZP\Models\Merchant\Email\Repository as EmailRepo;
use RZP\Models\Merchant\Email\Entity as EmailEntity;
use RZP\Modules\Acs\RecordSyncEvent;
use RZP\Tests\Functional\TestCase;

class RecordAccountIdForSyncTest extends TestCase
{

    public function testMerchantCreate()
    {
        Event::Fake([RecordSyncEvent::class]);

        /** @var MerchantEntity $merchant */
        $merchant = $this->fixtures->merchant->create();
        Event::assertDispatched(RecordSyncEvent::class,
            function(RecordSyncEvent $e) use ($merchant) {
                return $merchant->getMerchantId() === $e->entity->getMerchantId();
            }
        );
    }

    public function testMerchantUpdate()
    {
        /** @var MerchantEntity $merchant */
        $merchant = $this->fixtures->merchant->create();

        Event::Fake([RecordSyncEvent::class]);

        $repo = (new MerchantRepo());
        $merchant->name = 'Updated Test Merchant Name';
        $repo->saveOrFail($merchant);

        Event::assertDispatched(RecordSyncEvent::class,
            function(RecordSyncEvent $e) use ($merchant) {
                return $merchant->getMerchantId() === $e->entity->getMerchantId();
            }
        );
    }

    public function testMerchantDetailCreate()
    {
        Event::Fake([RecordSyncEvent::class]);

        /** @var MerchantDetailEntity $merchantDetail */
        $merchantDetail = $this->fixtures->merchantDetail->create();
        Event::assertDispatched(RecordSyncEvent::class,
            function(RecordSyncEvent $e) use ($merchantDetail) {
                return $merchantDetail->getMerchantId() === $e->entity->getMerchantId();
            }
        );
    }

    public function testMerchantDetailUpdate()
    {
        /** @var MerchantDetailEntity $merchant */
        $merchantDetail = $this->fixtures->merchantDetail->create();

        Event::Fake([RecordSyncEvent::class]);

        $repo = (new MerchantDetailRepo());
        $merchantDetail->business_name = 'Updated Test Merchant Name';
        $repo->saveOrFail($merchantDetail);

        Event::assertDispatched(RecordSyncEvent::class,
            function(RecordSyncEvent $e) use ($merchantDetail) {
                return $merchantDetail->getMerchantId() === $e->entity->getMerchantId();
            }
        );
    }

    public function testMerchantDocumentCreate()
    {
        /** @var MerchantEntity $merchantDetail */
        $merchant = $this->fixtures->merchant->create();

        Event::Fake([RecordSyncEvent::class]);

        /** @var DocumentEntity $document */
        $attributes = [DocumentEntity::MERCHANT_ID => $merchant->getMerchantId()];
        $document = $this->fixtures->merchantDocument->create($attributes);
        Event::assertDispatched(RecordSyncEvent::class,
            function(RecordSyncEvent $e) use ($document) {
                return $document->getMerchantId() === $e->entity->getMerchantId();
            }
        );
    }

    public function testMerchantDocumentUpdate()
    {
        /** @var MerchantEntity $merchantDetail */
        $merchant = $this->fixtures->merchant->create();

        /** @var DocumentEntity $document */
        $attributes = [DocumentEntity::MERCHANT_ID => $merchant->getMerchantId()];
        $document = $this->fixtures->merchantDocument->create($attributes);

        Event::Fake([RecordSyncEvent::class]);

        $repo = (new DocumentRepo());
        $document->validation_id = uniqid();
        $repo->saveOrFail($document);

        Event::assertDispatched(RecordSyncEvent::class,
            function(RecordSyncEvent $e) use ($document) {
                return $document->getMerchantId() === $e->entity->getMerchantId();
            }
        );
    }

    public function testMerchantEmailCreate()
    {
        /** @var EmailEntity $merchantDetail */
        $merchant = $this->fixtures->merchant->create();

        Event::Fake([RecordSyncEvent::class]);

        /** @var EmailEntity $email */
        $attributes = [EmailEntity::MERCHANT_ID => $merchant->getMerchantId()];
        $email = $this->fixtures->merchantEmail->create($attributes);
        Event::assertDispatched(RecordSyncEvent::class,
            function(RecordSyncEvent $e) use ($email) {
                return $email->getMerchantId() === $e->entity->getMerchantId();
            }
        );
    }

    public function testMerchantEmailUpdate()
    {
        /** @var MerchantEntity $merchantDetail */
        $merchant = $this->fixtures->merchant->create();

        /** @var EmailEntity $email */
        $attributes = [EmailEntity::MERCHANT_ID => $merchant->getMerchantId()];
        $email = $this->fixtures->merchantEmail->create($attributes);

        Event::Fake([RecordSyncEvent::class]);

        $repo = (new EmailRepo());
        $email->type = 'dummy_type';
        $repo->saveOrFail($email);

        Event::assertDispatched(RecordSyncEvent::class,
            function(RecordSyncEvent $e) use ($email) {
                return $email->getMerchantId() === $e->entity->getMerchantId();
            }
        );
    }

    public function testStakeholderCreate()
    {
        /** @var EmailEntity $merchantDetail */
        $merchant = $this->fixtures->merchant->create();

        Event::Fake([RecordSyncEvent::class]);

        /** @var StakeholderEntity $stakeholder */
        $attributes = [StakeholderEntity::MERCHANT_ID => $merchant->getMerchantId()];
        $stakeholder = $this->fixtures->stakeholder->create($attributes);
        Event::assertDispatched(RecordSyncEvent::class,
            function(RecordSyncEvent $e) use ($stakeholder) {
                return $stakeholder->getMerchantId() === $e->entity->getMerchantId();
            }
        );
    }

    public function testStakeholderUpdate()
    {
        /** @var MerchantEntity $merchantDetail */
        $merchant = $this->fixtures->merchant->create();

        /** @var StakeholderEntity $stakeholder */
        $attributes = [StakeholderEntity::MERCHANT_ID => $merchant->getMerchantId()];
        $stakeholder = $this->fixtures->stakeholder->create($attributes);

        Event::Fake([RecordSyncEvent::class]);

        $repo = (new StakeholderRepo());
        $stakeholder->name = 'Updated Stakeholder Name';
        $repo->saveOrFail($stakeholder);

        Event::assertDispatched(RecordSyncEvent::class,
            function(RecordSyncEvent $e) use ($stakeholder) {
                return $stakeholder->getMerchantId() === $e->entity->getMerchantId();
            }
        );
    }
}
