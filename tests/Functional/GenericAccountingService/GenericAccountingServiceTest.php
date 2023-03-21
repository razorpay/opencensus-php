<?php

namespace Functional\GenericAccountingService;

use RZP\Models\Payout\SourceUpdater\GenericAccountingUpdater;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksSplitz;


class GenericAccountingServiceTest extends TestCase
{
    use MocksSplitz;


    public function testExperimentEnabled()
    {
        $resp = GenericAccountingUpdater::isGAIExperimentEnabled("test_merchant_id");

        $this->assertFalse($resp);

        $this->app['config']->set('app.generic_ai_enabled_experiment_result_mock', true);

        $resp = GenericAccountingUpdater::isGAIExperimentEnabled("test_merchant_id");

        $this->assertTrue($resp);
    }
}
