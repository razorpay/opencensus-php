<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

/**
 * @property Fixtures $fixtures
 *
 * Trait TransactionTrait
 * @package RZP\Tests\P2p\Service\Base\Traits
 */
trait MandateTrait
{
    protected $pspxMandate;

    protected $context;

    public function setUp(): void
    {
        parent::setUp();

        $this->pspxMandate = $this->app['pspx_mandate'];
    }

    public function getPspxLastMandate(string $device)
    {
        $this->setContext($device);
        $mandates = $this->pspxMandate->fetchAll($this->context);

        return $mandates[array_key_last($mandates)];

    }

    /**
     * Set Sharp gateway context to $context property
     *
     * @throws \RZP\Exception\P2p\BadRequestException
     */
    protected function setContext(string $device)
    {
        $context = new Context();

        $context->setHandle($this->fixtures->handle($device));

        $context->setMerchant($this->fixtures->merchant($device));

        $context->setDevice($this->fixtures->device($device));

        $context->setDeviceToken($this->fixtures->deviceToken($device));

        $context->registerServices();

        $this->context = $context;
    }
}
