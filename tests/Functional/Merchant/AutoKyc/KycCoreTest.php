<?php

namespace RZP\Tests\Functional\Merchant\AutoKyc;

use Config;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\AutoKyc\Core as kycCore;

class KycCoreTest extends TestCase
{
    public function testWhenDocumentIsPresentInKycService()
    {
        $input = ['kyc_id' => '1234'];

        Config::set('applications.kyc.mock', true);
        Config::set('applications.kyc.kyc_detail_document', ['personal_pan', 'aadhaar']);

        $this->assertTrue((new kycCore)->isDocumentAlreadyPresentInKycService($input, 'personal_pan'));
        $this->assertTrue((new kycCore)->isDocumentAlreadyPresentInKycService($input, 'aadhaar'));
    }

    public function testWhenDocumentIsNotPresentInKycService()
    {
        $input = ['kyc_id' => '1234'];

        Config::set('applications.kyc.mock', true);
        Config::set('applications.kyc.kyc_detail_document', ['personal_pan', 'aadhaar']);

        $this->assertFalse((new kycCore)->isDocumentAlreadyPresentInKycService($input, 'drivers_license'));
    }

}
