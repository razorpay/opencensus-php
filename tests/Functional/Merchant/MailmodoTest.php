<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Mail;
use Hash;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Cron\Core as CronJobHandler;
use RZP\Models\Merchant\Cron\Constants as CronConstants;
use function GuzzleHttp\json_decode;

class MailmodoTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MailModoTestData.php';
        parent::setUp();
    }


    private function createMerchant($merchantId)
    {

        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant', ['id' => $merchantId]);

        return $merchant;
    }


    protected function enableRazorXTreatmentForRazorX($enabled)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($enabled) {
                                  if ($enabled === true)
                                  {
                                      return 'on';
                                  }
                                  return 'off';
                              }));
    }


}
