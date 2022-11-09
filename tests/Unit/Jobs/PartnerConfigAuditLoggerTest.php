<?php

namespace Unit\Jobs;

use Mockery;
use RZP\Tests\TestCase;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Jobs\PartnerConfigAuditLogger;
use RZP\Models\Partner\Config\Entity;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Services\Partnerships\PartnershipsService;

class PartnerConfigAuditLoggerTest extends TestCase
{
    use MocksSplitz;

    protected $auditLogParams;

    protected $job;

    const ADMIN_ID = "100adminid001";
    const ADMIN_EMAIL = "100adminid001@razorpay.com";
    const ENTITY_NAME = "partner_config";
    const APPLICATION = "application";
    const MERCHANT = "merchant";

    protected function setup() : void {
        parent::setUp();

        $this->auditLogParams = $this->buildAuditLogParams(self::APPLICATION);
        $this->job = $this->mockPartnerConfigAuditLog("live", $this->auditLogParams);
    }

    public function testHandleWhenEntityTypeIsApplication()
    {
        $partnershipsServiceMock = Mockery::mock(PartnershipsService::class)->makePartial();
        $this->app->instance('partnerships', $partnershipsServiceMock);

        $partnershipsServiceMock->shouldReceive('createAuditLog')
                                ->once();

        $this->job->handle();

    }

    public function testHandleWhenEntityTypeIsMerchant()
    {

        $this->auditLogParams = $this->buildAuditLogParams(self::MERCHANT);
        $this->job = $this->mockPartnerConfigAuditLog("live", $this->auditLogParams);

        $partnershipsServiceMock = Mockery::mock(PartnershipsService::class)->makePartial();
        $this->app->instance('partnerships', $partnershipsServiceMock);

        $partnershipsServiceMock->shouldReceive('createAuditLog')
                                ->once();

        $this->job->handle();

    }

    private function mockPartnerConfigAuditLog($mode, $params)
    {
        return Mockery::mock(PartnerConfigAuditLogger::class, [$params, $mode])->makePartial();
    }

    private function buildAuditLogParams($entityType)
    {
        $partner_config = new Entity();
        $partner_config->setId(Constants::DEFAULT_PARTNER_CONFIGS_ID);
        $partner_config->setUpdatedAt(12345678);
        $partner_config->setEntityType($entityType);
        if($entityType === self::APPLICATION) {
            $partner_config->setEntityId(Constants::DEFAULT_PLATFORM_APP_ID);
        } else {
            $partner_config->setEntityId(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID);
            $partner_config->setOriginId(Constants::DEFAULT_PLATFORM_APP_ID);
        }

        return [
            "entity" => $partner_config->toArray(),
            "entity_name" => self::ENTITY_NAME,
            "actor_id" => self::ADMIN_ID,
            "actor_email" => self::ADMIN_EMAIL
        ];
    }
}
