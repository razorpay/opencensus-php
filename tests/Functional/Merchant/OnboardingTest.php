<?php

namespace RZP\Tests\Functional\Feature;

use Illuminate\Http\UploadedFile;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Feature\Onboarding\Constants as OnboardingConstants;
use RZP\Models\Feature\Constants as FeatureConstants;


class OnboardingTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/OnboardingTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testGetQuestions()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPostResponsesWithFiles()
    {
        $this->ba->proxyAuth();

        $url = "storage/files/" . OnboardingConstants::ONBOARDING .  "/" . OnboardingConstants::VENDOR_AGREEMENT . ".pdf";

        $uploadedFile = $this->createUploadedFile($url);

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        $request['content'][FeatureConstants::MARKETPLACE][OnboardingConstants::VENDOR_AGREEMENT] = $uploadedFile;

        $expectedResponse = $testData['response']['content'];

        $actualResponse = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals($expectedResponse, $actualResponse);

        $this->assertArrayHasKey(OnboardingConstants::VENDOR_AGREEMENT, $actualResponse[FeatureConstants::MARKETPLACE]);
    }

    protected function createUploadedFile(string $url): UploadedFile
    {
        $mime = 'application/pdf';

        return new UploadedFile(
            $url,
            'file',
            $mime,
            filesize($url),
            null,
            true);
    }
}
