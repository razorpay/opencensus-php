<?php


namespace Functional\Merchant;

use Illuminate\Support\Facades\DB;
use RZP\Models;
use Illuminate\Http\UploadedFile;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Org\Repository as OrgRepository;
use RZP\Models\Base\EsDao;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Merchant\Fraud\BulkNotification\File;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;


class PaymentLimitTest extends TestCase
{
    use RequestResponseFlowTrait;

    use WorkflowTrait;

    protected $esDao;

    protected $config;

    protected $esClient;

    public function setUp(): void
    {
        ConfigKey::resetFetchedKeys();

        $this->testDataFilePath = __DIR__ . '/helpers/PaymentLimitTestData.php';

        parent::setUp();
    }

    protected function validateContent($actualContent, $expectedContent): bool
    {
        foreach ($expectedContent as $key => $value) {
            if (isset($actualContent[$key]) === false) {
                return false;
            }

            if ($expectedContent[$key] !== $actualContent[$key]) {
                return false;
            }
        }

        return true;
    }

    private function addAdminPermission()
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => PermissionName::MERCHANT_MAX_PAYMENT_LIMIT_UPDATE]);

        $role->permissions()->attach($perm->getId());
    }

    private function getMaxPaymentLimitUploadedXLSXFileFromFileData($fileData): UploadedFile
    {
        $inputExcelFile = (new File())->createExcelFile(
            $fileData,
            'bulk_max_payment_limit',
            'files/max_payment_limit/test'
        );

        return $this->createUploadedFile($inputExcelFile, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function createUploadedFile(string $filePath, string $mimeType = null, int $fileSize = -1): UploadedFile
    {
        $this->assertFileExists($filePath);

        $mimeType = $mimeType ?: 'image/png';

        return new UploadedFile($filePath, $filePath, $mimeType, null, true);
    }

    public function testUploadMaxLimitViaFile()
    {
        $this->setupMaxPaymentLimitWorkflow("merchant_max_payment_limit_workflow", PermissionName::EXECUTE_MERCHANT_MAX_PAYMENT_LIMIT_WORKFLOW);

        $fileData = $this->testData['max_payment_limit_data'];

        $testData = $this->testData['testUploadMaxLimitViaFile'];

        $testData['request']['files']['file'] = $this->getMaxPaymentLimitUploadedXLSXFileFromFileData($fileData);

        $this->ba->adminAuth();

        $this->addAdminPermission();

        $response = $this->startTest($testData);

        $entityId = $response['entity_id'];

        $workflowActionId = empty($response['data']) ? null : $response['data']['id'];

        $fileStoreEntities = (new Models\FileStore\Repository())->fetch([
            'type' => 'payment_limit',
            'entity_id' => $entityId
        ]);

        $this->assertCount(2, $fileStoreEntities);

        $expectedOutputFileRows = [
            ["merchant_id", "max_payment_amount", "max_international_payment_amount"],
            ['38RR00000197367', 100000000000, 50000000],
            ['H9sTmdNiFOOFCC', 100000000000, 50000000],
            ['10000000000000', 60000000000, 50000000],
            ['38RR00000197367', 4200000000, 50000000],
            ['38RR00000197367', 2200000000, 50000000],
            ['38RR00000197367', 7000000000, 50000000],
            ['38RR00000197367', 3000000000, 50000000],
        ];

        /** @var Models\FileStore\Entity $outputFile */
        $outputFile = $fileStoreEntities->firstWhere('name', '=', 'bulk_max_payment_limit.xlsx');

        $uploadFile = new UploadedFile($outputFile->getFullFilePath(), $outputFile->getName() . '.' . $outputFile->getExtension());

        $fileData = (new File())->getFileData($uploadFile);

        $this->assertArraySelectiveEquals($expectedOutputFileRows, $fileData);

        $this->esDao = new EsDao();

        $this->esClient = $this->esDao->getEsClient()->getClient();

        if (empty($workflowActionId) === false) {
            $responsePostWA = $this->performWorkflowAction($workflowActionId, true);

            $this->assertNotEmpty($responsePostWA);
        }

    }


    protected function setupMaxPaymentLimitWorkflow(string $workflowName, string $permissionName): void
    {
        $org = (new OrgRepository)->getRazorpayOrg();

        $this->fixtures->on('live')->create('org:workflow_users', ['org' => $org]);

        $this->createWorkflow([
            'org_id' => '100000razorpay',
            'name' => $workflowName,
            'permissions' => [$permissionName],
            'levels' => [
                [
                    'level' => 1,
                    'op_type' => 'or',
                    'steps' => [
                        [
                            'reviewer_count' => 1,
                            'role_id' => Org::ADMIN_ROLE,
                        ],
                    ],
                ],
            ],
        ]);
    }

}
