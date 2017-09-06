<?php

namespace RZP\Tests\Functional\Merchant;

use Illuminate\Http\UploadedFile;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;


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

    public function testpostResponsesWithFiles()
    {
        $this->ba->proxyAuth();

        $url = "storage/files/onboarding/signed_agreement_with_third_party.pdf";

        $uploadedFile = $this->createUploadedFile($url);

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        $request['content']['onboarding']['marketplace']['signed_agreement_with_third_party'] = $uploadedFile;

        $expectedResponse = $testData['response']['content'];

        $actualResponse = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals($expectedResponse, $actualResponse);

        $this->assertArrayHasKey( 'signed_agreement_with_third_party', $actualResponse['onboarding']['marketplace']);
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
