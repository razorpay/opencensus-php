<?php

namespace RZP\Models\PaymentLink\CustomDomain;

use Illuminate\Support\Facades\Config;
use RZP\Models\PaymentLink\CustomDomain\WebhookProcessor\IWebhookHandler;

class Factory
{
    const CDS_MOCK_KEY = "services.custom_domain_service.mock";

    /**
     * @return \RZP\Models\PaymentLink\CustomDomain\IDomainClient
     */
    public static function getDomainClient(): IDomainClient
    {
        if (self::isCDSMocked() === true)
        {
            return new Mock\DomainClient();
        }

        return new DomainClient();
    }

    /**
     * @return \RZP\Models\PaymentLink\CustomDomain\IPropagationClient
     */
    public static function getPropagationClient(): IPropagationClient
    {
        if (self::isCDSMocked() === true)
        {
            return new Mock\PropagationClient();
        }

        return new PropagationClient();
    }

    /**
     * @return \RZP\Models\PaymentLink\CustomDomain\IAppClient
     */
    public static function getAppClient(): IAppClient
    {
        if (self::isCDSMocked() === true)
        {
            return new Mock\AppClient();
        }

        return new AppClient();
    }

    /**
     * @return \RZP\Models\PaymentLink\CustomDomain\WebhookProcessor\IWebhookHandler
     */
    public static function getWebHookHandler(): IWebhookHandler
    {
        if (self::isCDSMocked() === true)
        {
            return new Mock\Processor();
        }

        return new WebhookProcessor\Processor();
    }

    /**
     * @return bool
     */
    private static function isCDSMocked(): bool
    {
        return Config::get(self::CDS_MOCK_KEY);
    }
}
