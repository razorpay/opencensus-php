<?php

namespace RZP\Tests\Functional\Batch;

use Mail;
use Mockery;
use Illuminate\Support\Facades\Queue;

use RZP\Models\Batch\Header;
use RZP\Models\Batch\Entity;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Batch\AuthLink as BatchAuthFileMail;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class NachMigrationTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NachMigrationTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    /*
     * Test cases for Batch File and UI input Validations
     */

    public function testCreateBatchOfNachMigrations()
    {
        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();
    }

    public function testBatchFileValidation()
    {
        $this->setUpMerchant();

        $entries = $this->getDefaultFileEntries();
        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

    }

    public function testValidateBatchWithInvalidHeaders()
    {
        $this->setUpMerchant();

        $entries = $this->getWrongHeaderEntries();
        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testInvlidFeature()
    {
        $merchant = $this->setUpMerchant(True, True, False);

        $entries = $this->getDefaultFileEntries();
        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();
    }

    public function testInvlidTerminal()
    {
        $merchant = $this->setUpMerchant(False, True, True);

        $entries = $this->getDefaultFileEntries();
        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();
    }

    public function testInvlidPricing()
    {
        $merchant = $this->setUpMerchant(True, False, True);

        $entries = $this->getDefaultFileEntries();
        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                Header::NACH_MIGRATION_START_DATE                   => '10/02/2018',
                Header::NACH_MIGRATION_END_DATE                     => '10/02/2021',
                Header::NACH_MIGRATION_BANK                         => 'HDFC',
                Header::NACH_MIGRATION_ACCOUNT_NUMBER               => 'HDFC00000000001',
                Header::NACH_MIGRATION_ACCOUNT_HOLDER_NAME          => "NameFirst",
                Header::NACH_MIGRATION_ACCOUNT_TYPE                 => 'savings',
                Header::NACH_MIGRATION_IFSC                         => 'HDFC0000007',
                Header::NACH_MIGRATION_MAX_AMOUNT                   => "99999",
                Header::NACH_MIGRATION_UMRN                         => 'HDFC00038433903433',
                Header::NACH_MIGRATION_DEBIT_TYPE                   => 'max_amount',
                Header::NACH_MIGRATION_FREQ                         => "adhoc",
                Header::NACH_MIGRATION_METHOD                       => "emandate",
                Header::NACH_MIGRATION_CUSTOMER_EMAIL               => "abc@gmail.com",
                Header::NACH_MIGRATION_CUSTOMER_PHONE               => "9123456780",
                'notes[custom 1]'                                   => 'Some Random Comments',
                'notes[custom 3]'                                   => 'Some More Comments',
            ],

            //will fail due to start date
            [
                Header::NACH_MIGRATION_START_DATE                   => '10/02/2018',
                Header::NACH_MIGRATION_END_DATE                     => '10/02/2019',
                Header::NACH_MIGRATION_BANK                         => 'HDFC',
                Header::NACH_MIGRATION_ACCOUNT_NUMBER               => 'HDFC00000000001',
                Header::NACH_MIGRATION_ACCOUNT_HOLDER_NAME          => "NameSecond",
                Header::NACH_MIGRATION_ACCOUNT_TYPE                 => 'savings',
                Header::NACH_MIGRATION_IFSC                         => 'HDFC0000007',
                Header::NACH_MIGRATION_MAX_AMOUNT                   => "99999",
                Header::NACH_MIGRATION_UMRN                         => 'HDFC00038433903433',
                Header::NACH_MIGRATION_DEBIT_TYPE                   => 'max_amount',
                Header::NACH_MIGRATION_FREQ                         => "adhoc",
                Header::NACH_MIGRATION_METHOD                       => "emandate",
                Header::NACH_MIGRATION_CUSTOMER_EMAIL               => "xyz@gmail.com",
                Header::NACH_MIGRATION_CUSTOMER_PHONE               => "9123433333",
                'notes[custom 1]'                                   => 'Some Random Comments',
                'notes[custom 3]'                                   => 'Some More Comments',
            ],
            [
                Header::NACH_MIGRATION_START_DATE                   => '10/02/2018',
                Header::NACH_MIGRATION_END_DATE                     => 'Until Cancelled',
                Header::NACH_MIGRATION_BANK                         => 'HDFC',
                Header::NACH_MIGRATION_ACCOUNT_NUMBER               => 'HDFC00000000001',
                Header::NACH_MIGRATION_ACCOUNT_HOLDER_NAME          => "NameThird",
                Header::NACH_MIGRATION_ACCOUNT_TYPE                 => 'savings',
                Header::NACH_MIGRATION_IFSC                         => 'HDFC0000007',
                Header::NACH_MIGRATION_MAX_AMOUNT                   => "41000",
                Header::NACH_MIGRATION_UMRN                         => 'HDFC00038433903433',
                Header::NACH_MIGRATION_DEBIT_TYPE                   => 'max_amount',
                Header::NACH_MIGRATION_FREQ                         => "adhoc",
                Header::NACH_MIGRATION_METHOD                       => "nach",
                Header::NACH_MIGRATION_CUSTOMER_EMAIL               => "abc@gmail.com",
                Header::NACH_MIGRATION_CUSTOMER_PHONE               => "9123456780",
                'notes[custom 1]'                                   => 'Some Random Comments',
                'notes[custom 3]'                                   => 'Some More Comments',
            ],

            //will fail due to invalid email
            [
                Header::NACH_MIGRATION_START_DATE                   => '10/02/2018',
                Header::NACH_MIGRATION_END_DATE                     => '10/02/2021',
                Header::NACH_MIGRATION_BANK                         => 'HDFC',
                Header::NACH_MIGRATION_ACCOUNT_NUMBER               => 'HDFC00000000001',
                Header::NACH_MIGRATION_ACCOUNT_HOLDER_NAME          => "NameFourth",
                Header::NACH_MIGRATION_ACCOUNT_TYPE                 => 'savings',
                Header::NACH_MIGRATION_IFSC                         => 'HDFC0000007',
                Header::NACH_MIGRATION_MAX_AMOUNT                   => "99999",
                Header::NACH_MIGRATION_UMRN                         => 'HDFC00038433903433',
                Header::NACH_MIGRATION_DEBIT_TYPE                   => 'max_amount',
                Header::NACH_MIGRATION_FREQ                         => "adhoc",
                Header::NACH_MIGRATION_METHOD                       => "emandate",
                Header::NACH_MIGRATION_CUSTOMER_EMAIL               => "",
                Header::NACH_MIGRATION_CUSTOMER_PHONE               => "9199999999",
                'notes[custom 1]'                                   => 'Some Random Comments',
                'notes[custom 3]'                                   => 'Some More Comments',
            ],
        ];
    }



    protected function getWrongHeaderEntries()
    {
        return [
            [
                Header::NACH_MIGRATION_START_DATE                   => '10/02/2018',
                Header::NACH_MIGRATION_END_DATE                     => '10/02/2021',
                Header::NACH_MIGRATION_BANK                         => 'HDFC',
                Header::NACH_MIGRATION_ACCOUNT_NUMBER               => 'HDFC00000000001',
                Header::NACH_MIGRATION_ACCOUNT_HOLDER_NAME          => "NameFourth",
                Header::NACH_MIGRATION_ACCOUNT_TYPE                 => 'savings',
                Header::NACH_MIGRATION_IFSC                         => 'HDFC0000007',
                Header::NACH_MIGRATION_MAX_AMOUNT                   => "99999",
                Header::NACH_MIGRATION_DEBIT_TYPE                   => 'max_amount',
                Header::HDFC_EM_DEBIT_AMOUNT                        => "adhoc",
                Header::NACH_MIGRATION_METHOD                       => "emandate",
                Header::NACH_MIGRATION_CUSTOMER_EMAIL               => "",
                Header::NACH_MIGRATION_CUSTOMER_PHONE               => "9199999999",
                'noted[custom 1]'                                   => 'Some Random Comments',
                'noted[custom 3]'                                   => 'Some More Comments',

            ],
        ];
    }

    /*
     * utility method
     */
    private function setUpMerchant($addTerminal = True, $addPricing = True, $addFeature = True) {

        $terminalId = 'testsomerandom'; // this will not be found in the test data
        if ($addTerminal == True) {
            $terminalId = 'testhdfcrandom';
        }

        $merchant = $this->fixtures
            ->create('merchant_fluid', ['id' => '1cXSLlUU8X8sXl',

                'pricing_plan_id' => '1hDYlICobzOCYt',
            ])
            ->addTerminal('hdfc', ['id' => $terminalId, 'emandate' => 1])
            ->get();

        if ($addFeature == True) {
            $this->fixtures->merchant->addFeatures('charge_at_will', $merchant->getId());
        }

        if ($addPricing == True) {
            $attributes = array(
                'merchant_id' => $merchant->getId(),
                'disabled_banks' => [],
                'banks' => '[]',
            );

            $this->fixtures->create('methods', $attributes);
            $this->fixtures->merchant->enableEmandate($merchant->getId());
            $this->fixtures->merchant->enableNach($merchant->getId());
        }

        return $merchant;
    }



    /*
     * Test cases for the nach migration API
     */

    public function testSuccessNachMigration()
    {
        $this->setUpMerchant();

        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testFailedNachMigrationInvalidUMRN()
    {
        $this->setUpMerchant();

        $this->ba->batchAppAuth();

        $this->startTest();
    }

}
