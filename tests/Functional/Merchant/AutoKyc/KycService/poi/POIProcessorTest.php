<?php

namespace RZP\Tests\Functional\Merchant\AutoKyc\KycService\poi;

use Config;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\AutoKyc\KycService\poi\POIProcessor as POIProcessor;

class POIProcessorTest extends TestCase
{
    public function testRequestDetailsWhenDocumentIsPresentInKycService()
    {
        $input = ['kyc_id' => '1234'];

        Config::set('applications.kyc.mock', true);
        Config::set('applications.kyc.kyc_detail_document', ['personal_pan', 'aadhaar']);

        $this->assertEquals((new POIProcessor($input))->getApplicableRequestDetails('personal_pan')['method'], 'PUT');
        $this->assertEquals((new POIProcessor($input))->getApplicableRequestDetails('aadhaar')['method'], 'PUT');
    }

    public function testRequestDetailsWhenDocumentIsNotPresentInKycService()
    {
        $input = ['kyc_id' => '1234'];

        Config::set('applications.kyc.mock', true);
        Config::set('applications.kyc.kyc_detail_document', ['personal_pan', 'aadhaar']);

        $this->assertEquals((new POIProcessor($input))->getApplicableRequestDetails('drivers_license')['method'], 'POST');
    }
}
