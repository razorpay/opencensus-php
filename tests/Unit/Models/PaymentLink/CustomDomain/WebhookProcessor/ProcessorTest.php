<?php

namespace Unit\Models\PaymentLink\CustomDomain\WebhookProcessor;

use RZP\Tests\Unit\Models\PaymentLink\BaseTest;
use RZP\Models\PaymentLink\CustomDomain\WebhookProcessor;

class ProcessorTest extends BaseTest
{
    const DOMAIN_NAME                       = "domain.com";
    const MERCHANT_ID                       = "1000000000";
    const DOMAIN_NAME_KEY                   = "domain_name";
    const MERCHANT_ID_KEY                   = "merchant_id";

    const EVENT_DOMAIN_DELETED              = "domain.deleted";
	const EVENT_DOMAIN_CREATED              = "domain.created";
	const EVENT_PROXY_CONFIG_SUCCESS        = "domain.proxy_config_success";
	const EVENT_PROXY_CONFIG_FAILED         = "domain.proxy_config_failed";
	const EVENT_PROXY_CONFIG_DELETED        = "domain.proxy_config_deleted";
	const EVENT_PROXY_CONFIG_DELETE_FAILED  = "domain.proxy_config_delete_failed";

    const CLASS_MAP = [
        self::EVENT_DOMAIN_DELETED              => WebhookProcessor\DomainDeletedProcessor::class,
        self::EVENT_DOMAIN_CREATED              => WebhookProcessor\DomainCreatedProcessor::class,
        self::EVENT_PROXY_CONFIG_SUCCESS        => WebhookProcessor\DomainProxyConfigSuccessProcessor::class,
        self::EVENT_PROXY_CONFIG_FAILED         => WebhookProcessor\DomainProxyConfigFailedProcessor::class,
        self::EVENT_PROXY_CONFIG_DELETED        => WebhookProcessor\DomainProxyConfigDeletedProcessor::class,
        self::EVENT_PROXY_CONFIG_DELETE_FAILED  => WebhookProcessor\DomainProxyConfigDeleteFailedProcessor::class,
    ];

    /**
     * @group nocode_cds
     * @group nocode_cds_processor
     * @return void
     */
    public function testHandle()
    {
        $processor = new WebhookProcessor\Processor;

        foreach (self::CLASS_MAP as $event => $class)
        {
            $data = [
                self::DOMAIN_NAME_KEY                   => self::DOMAIN_NAME,
                self::MERCHANT_ID_KEY                   => self::MERCHANT_ID,
                WebhookProcessor\Processor::EVENTY_KEY  => $event,
            ];

            $processor->process($data);

            $this->assertEquals(new $class($data), $processor->getHandler());
        }
    }
}
