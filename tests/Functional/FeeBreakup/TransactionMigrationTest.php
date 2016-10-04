<?php

namespace RZP\Tests\Functional\FeeBreakup;

use DB;
use Mockery;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Batch\Status;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;
use Illuminate\Http\UploadedFile;


class TransactionMigrationTest extends TestCase
{
    use PaymentTrait;

    protected $payment = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/TransactionMigrationTestData.php';

        parent::setUp();

        $this->payment = $this->fixtures->create('payment:captured');

        $this->ba->privateAuth();
    }

    public function testMigration()
    {
        $this->ba->appAuth();
        $this->startTest();
    }

}
