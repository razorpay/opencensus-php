<?php

namespace Unit\Services;

use Mockery;
use Request;
use RZP\Services\UfhService;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;



class ReconServiceTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->adminProxyAuth();
    }

    public function testUploadFile()
    {
        $reconService = $this->getReconServiceWithSendRequestMock();

        $file = new UploadedFile(__DIR__.'/ReconServiceTestFile.csv','ReconServiceTestFile.csv', 'csv', null, true);

        $upload_file_payload = ['merchant_id' => '1000000Razorpay', 'workspace_id' => 'random', 'file_type_id' => 'random', 'file' => $file];

        $reconService->shouldAllowMockingMethod('uploadFileToUfh')
            ->shouldReceive('uploadFileToUfh')->withAnyArgs()->andReturn('random');

        $reconService->uploadFile($upload_file_payload);

        $reconService->shouldHaveReceived('sendRequest');
    }

    protected function getReconServiceWithSendRequestMock()
    {
        $reconService = Mockery::mock('RZP\Services\ReconService', [$this->app])->makePartial();

        $reconService->shouldAllowMockingProtectedMethods();

        $reconService->shouldReceive('sendRequest');

        return $reconService;
    }
}
