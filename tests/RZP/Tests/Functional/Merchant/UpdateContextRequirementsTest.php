<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail\NeedsClarification\UpdateContextRequirements;

class UpdateContextRequirementsTest extends TestCase
{
    public function testCanUpdateContextForProprietorshipSuccessCase()
    {
        $input          = [
            'poi_verification_status'                => 'verified',
            'poa_verification_status'                => 'verified',
            'bank_details_verification_status'       => 'incorrect_details',
            'shop_establishment_verification_status' => 'incorrect_details',
        ];
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $mapping = new UpdateContextRequirements();

        $merchantDetail->setBusinessTypeValue('1');

        $this->assertTrue($mapping->canUpdateMerchantContext($merchantDetail));
    }

    public function testCanUpdateContextForProprietorshipSuccessCaseWithGstin()
    {
        $input          = [
            'poi_verification_status'          => 'verified',
            'poa_verification_status'          => 'verified',
            'bank_details_verification_status' => 'incorrect_details',
            'gstin_verification_status'        => 'incorrect_details',
        ];
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $mapping = new UpdateContextRequirements();

        $merchantDetail->setBusinessTypeValue('1');

        $this->assertTrue($mapping->canUpdateMerchantContext($merchantDetail));

        $requiredFields = [
            'poa_doc',
            'personal_pan_identifier',
            'bank_account_number',
            'gstin_identifier',
            'shop_establishment_identifier',
        ];

        $this->assertEquals($requiredFields, $mapping->getClarificationKeys($merchantDetail));
    }

    public function testCanUpdateContextForProprietorshipFalseCase()
    {
        $input          = [
            'poi_verification_status' => 'verified',
            'poa_verification_status' => 'verified',
        ];
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $mapping = new UpdateContextRequirements();

        $merchantDetail->setBusinessTypeValue('1');

        $this->assertFalse($mapping->canUpdateMerchantContext($merchantDetail));
    }

    public function testCanUpdatePartnerContextForProprietorshipSuccessCase()
    {
        $input          = [
            'poi_verification_status'          => 'verified',
            'bank_details_verification_status' => 'incorrect_details',
            'gstin_verification_status'        => 'incorrect_details',
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $partnerActivationInput = [
            'merchant_id'       => $merchantDetail->getId(),
            'activation_status' => 'under_review',
            'submitted'         => true
        ];

        $partnerActivation = $this->fixtures->create('partner_activation', $partnerActivationInput);

        $mapping = new UpdateContextRequirements();

        $merchantDetail->setBusinessTypeValue('1');

        $this->assertTrue($mapping->canUpdatePartnerContext($partnerActivation));

        $requiredFields = [
            'personal_pan_identifier',
            'bank_account_number',
            'gstin_identifier',
        ];

        $this->assertEquals($requiredFields, $mapping->getClarificationKeys($partnerActivation));
    }

    public function testCanUpdatePartnerContextForProprietorshipFailureCase()
    {
        $input          = [
            'poi_verification_status'          => 'verified',
            'bank_details_verification_status' => 'incorrect_details',
            'gstin_verification_status'        => 'incorrect_details',
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $partnerActivationInput = [
            'merchant_id'       => $merchantDetail->getId(),
            'activation_status' => null,
            'submitted'         => false
        ];

        $partnerActivation = $this->fixtures->create('partner_activation', $partnerActivationInput);

        $mapping = new UpdateContextRequirements();

        $merchantDetail->setBusinessTypeValue('1');

        $this->assertFalse($mapping->canUpdatePartnerContext($partnerActivation));
    }
}
