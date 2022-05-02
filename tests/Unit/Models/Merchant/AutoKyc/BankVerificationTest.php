<?php


namespace Unit\Models\Merchant\AutoKyc;


use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail\Core as DetailCore;

class BankVerificationTest extends TestCase
{
    public function testAutoKycOnBankDetailsVerification()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                         => '11',
            'poi_verification_status'               => 'verified',
            'bank_details_verification_status'      => 'verified',
            'bank_details_doc_verification_status'  => null
        ]);

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'                          => $mid,
            'aadhaar_esign_status'                 => 'verified',
            'aadhaar_verification_with_pan_status' => 'verified'
        ]);

        $isAutoKycDone = (new DetailCore)->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    public function testAutoKycOnBankDetailsDocVerification()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                         => '11',
            'poi_verification_status'               => 'verified',
            'bank_details_verification_status'      => null,
            'bank_details_doc_verification_status'  => 'verified'
        ]);

        $mid = $merchantDetail->getId();
        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'                          => $mid,
            'aadhaar_esign_status'                 => 'verified',
            'aadhaar_verification_with_pan_status' => 'verified'
        ]);

        $isAutoKycDone = (new DetailCore)->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }
}
