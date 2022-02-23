<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

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

    public function setUp(): void
    {
        parent::setUp();

        $this->pspxMandate = $this->app['pspx_mandate'];
    }

    public function getPspxLastMandate()
    {
        $mandates = $this->pspxMandate->fetchAll();

        return $mandates[array_key_last($mandates)];

    }
}
