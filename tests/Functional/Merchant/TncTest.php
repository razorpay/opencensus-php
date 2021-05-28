<?php
namespace RZP\Tests\Functional\Tnc;

use DB;
use Mail;
use Hash;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class TncTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TncTestData.php';

        parent::setUp();
    }
    //tests to be added here.
}
