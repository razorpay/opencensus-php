<?php

namespace RZP\Tests\Unit\Models\Merchant\Detail;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail\NeedsClarification;

class NeedsClarificationTest extends TestCase
{

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NeedsClarificationTestData.php';

        parent::setUp();
    }

    public function testClarificationReasonTransformation()
    {
        $needClarification = New NeedsClarification\Core();

        $kycClarificationReason = $this->testData['testClarificationReasonTransformationInput'];

        $requirements = $needClarification->getFormattedKycClarificationReasons($kycClarificationReason);

        $expectedOutput = $this->testData['testClarificationReasonTransformationOutput'];

        $this->assertEquals($requirements, $expectedOutput);
    }

    public function testClarificationReasonMerge()
    {
        $needClarification = New NeedsClarification\Core();

        $kycClarificationReason = $this->testData['testClarificationReasonTransformationInput'];

        $kycClarificationSecondaryArray = $this->testData['testClarificationReasonMergeInput'];

        $requirements = $needClarification->mergeKycClarificationReasons(
            $kycClarificationReason,
            $kycClarificationSecondaryArray['clarification_reasons'],
            $kycClarificationSecondaryArray['additional_details']);

        $expectedOutput = $this->testData['testClarificationReasonMergeOutput'];

        $this->assertEquals($requirements, $expectedOutput);
    }
}
