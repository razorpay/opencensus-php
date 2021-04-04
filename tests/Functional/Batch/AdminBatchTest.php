<?php

namespace RZP\Tests\Functional\Batch;

use Hash;
use Mail;
use Cache;
use RZP\Models\Admin\Admin;
use RZP\Models\Batch\Header;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class AdminBatchTest extends TestCase
{
    use BatchTestTrait;
    use HeimdallTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/AdminBatchTestData.php';

        parent::setUp();

        $this->org = $this->fixtures->on('test')->create('org', [
            'email'         => 'random@rzp.com',
            'email_domains' => 'rzp.com',
            'auth_type'     => 'password',
        ]);

        $this->orgId = '100000razorpay';

        $this->admin_1 = $this->getAdminBatch('uvw@rzp.com', 0);

        $this->admin_2 = $this->getAdminBatch('abc@rzp.com', 1);

        $this->admin_3 = $this->getAdminBatch('stu@rzp.com', 1);

        $this->admin_4 = $this->getAdminBatch('qrt@rzp.com', 0);

        $this->ba->adminAuth('test', null, $this->orgId);
    }

    public function testCreateAdminBatch()
    {
        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->adminAuth();

        $this->startTest();

        $batch = $this->getLastEntity('batch', true, 'test');

        $this->assertEquals(4, $batch['processed_count']);

        $this->assertEquals(4, $batch['success_count']);

        $this->assertEquals(0, $batch['failure_count']);

        $this->assertEquals('processed', $batch['status']);

        $this->assertEquals(1, $batch['attempts']);

        $admin_1_modified = $this->getEntityById('admin', $this->admin_1->getId(), true);

        $admin_2_modified = $this->getEntityById('admin', $this->admin_2->getId(), true);

        $admin_3_modified = $this->getEntityById('admin', $this->admin_3->getId(), true);

        $admin_4_modified = $this->getEntityById('admin', $this->admin_4->getId(), true);

        $this->assertEquals(true, $admin_1_modified['allow_all_merchants']);

        $this->assertEquals(false, $admin_2_modified['allow_all_merchants']);

        $this->assertEquals(true, $admin_3_modified['allow_all_merchants']);

        $this->assertEquals(false, $admin_4_modified['allow_all_merchants']);

    }

    public function testCreateAdminBatchInvalidFileEntries()
    {
        $entries = $this->getInvalidFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->adminAuth();

        $this->startTest();

        $batch = $this->getLastEntity('batch', true, 'test');

        $this->assertEquals(4, $batch['processed_count']);

        $this->assertEquals(1, $batch['success_count']);

        $this->assertEquals(3, $batch['failure_count']);

        $this->assertEquals('processed', $batch['status']);

        $this->assertEquals(1, $batch['attempts']);

        $admin_1_modified = $this->getEntityById('admin', $this->admin_1->getId(), true);

        $admin_2_modified = $this->getEntityById('admin', $this->admin_2->getId(), true);

        $admin_3_modified = $this->getEntityById('admin', $this->admin_3->getId(), true);

        $admin_4_modified = $this->getEntityById('admin', $this->admin_4->getId(), true);

        $this->assertEquals(true, $admin_1_modified['allow_all_merchants']);

        $this->assertEquals(true, $admin_2_modified['allow_all_merchants']);

        $this->assertEquals(true, $admin_3_modified['allow_all_merchants']);

        $this->assertEquals(false, $admin_4_modified['allow_all_merchants']);
    }

    public function getDefaultFileEntries()
    {
        return [
            [
                Header::ADMIN_ID            => 'admin_'.$this->admin_1->getId(),
                Header::ALLOW_ALL_MERCHANTS => 1,
            ],
            [
                Header::ADMIN_ID            => 'admin_'.$this->admin_2->getId(),
                Header::ALLOW_ALL_MERCHANTS => 0,
            ],
            [
                Header::ADMIN_ID            => 'admin_'.$this->admin_3->getId(),
                Header::ALLOW_ALL_MERCHANTS => 1,
            ],
            [
                Header::ADMIN_ID            => 'admin_'.$this->admin_4->getId(),
                Header::ALLOW_ALL_MERCHANTS => 0,
            ]
        ];
    }

    public function getInvalidFileEntries()
    {
        return [
            [
                Header::ADMIN_ID            => 'admin_'.$this->admin_1->getId(),
                Header::ALLOW_ALL_MERCHANTS => 1,
            ],
            [
                Header::ADMIN_ID            => 'admin_'.$this->admin_2->getId(),
                Header::ALLOW_ALL_MERCHANTS => "zero",
            ],
            [
                Header::ADMIN_ID            => 'admin_'.$this->admin_3->getId(),
                Header::ALLOW_ALL_MERCHANTS => "false",
            ],
            [
                Header::ADMIN_ID            => 'admin_'.$this->admin_4->getId(),
                Header::ALLOW_ALL_MERCHANTS => "o",
            ]
        ];
    }


    protected function getAdminBatch($email, $allowMerchants)
    {
        return $this->fixtures->on('test')->create('admin', [
            Admin\Entity::ORG_ID              => $this->orgId,
            Admin\Entity::EMAIL               => $email,
            Admin\Entity::ALLOW_ALL_MERCHANTS => $allowMerchants
        ]);
    }
}
