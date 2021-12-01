<?php

namespace RZP\Tests\Functional\Fixtures\Entity;


class MerchantDetail extends Base
{
    public function create(array $attributes = array())
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $defaultValues =  [
                'merchant_id'   => $merchant['id'],
                'contact_email' => $merchant['email']
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $merchantDetail = $this->createEntityInTestAndLive('merchant_detail', $attributes);

        return $merchantDetail;
    }

    public function createAssociateMerchant(array $attributes = array())
    {
        return $this->createEntityInTestAndLive('merchant_detail', $attributes);
    }

    public function createSane(array $attributes = [])
    {
        return parent::create($attributes);
    }

    public function createValidFields(array $attributes = array())
    {
        $merchantDetailArray = $this->createMerchantDetail($attributes);

        $merchantDetail = $this->create($merchantDetailArray);

        $this->fixtures->create('merchant_document:multiple',
                                [
                                    'document_types' => ['address_proof_url',
                                                         'business_pan_url',
                                                         'business_proof_url',
                                                         'promoter_address_url'],
                                    'attributes'     => ['merchant_id' => $merchantDetail['merchant_id']]
                                ]
        );

        return $merchantDetail;
    }

    public function createFilledEntity(array $attributes = array())
    {
        $merchantDetailArray = $this->createMerchantDetail($attributes);

        $merchantDetail = $this->create($merchantDetailArray);

        return $merchantDetail;
    }

    public function createInvalidFields(array $attributes = array())
    {
        $merchantDetailArray = $this->createMerchantDetail($attributes);

        unset($merchantDetailArray["contact_name"]);

        return $this->create($merchantDetailArray);
    }

    public function createEventAccount()
    {
        $merchantDetailArray = [
            "contact_name"                  => "test",
            "contact_email"                 => "test.test3@razorpay.com",
            "contact_mobile"                => "9123456789",
            "business_type"                 => "1",
            "business_name"                 => "Acme",
            "business_registered_state"     => "state",
            "business_registered_city"      => "city",
            "business_operation_state"      => "state",
            "business_operation_city"       => "city",
            "transaction_volume"            => 3,
        ];

        return $this->create($merchantDetailArray);
    }

    public function createMerchantDetail(array $attributes = [])
    {
        $return = [
            "contact_name"                  => "test",
            "contact_email"                 => "test.test3@razorpay.com",
            "contact_mobile"                => "9123456789",
            "contact_landline"              => "123456789",
            "business_type"                 => "1",
            "business_name"                 => "Acme",
            "business_dba"                  => "Acme",
            "business_website"              => "http://www.example.com/",
            "business_international"        => 0,
            "business_paymentdetails"       => "B2C",
            "business_registered_address"   => "Adress",
            "business_registered_state"     => "state",
            "business_registered_city"      => "city",
            "business_registered_pin"       => "123455",
            "business_operation_address"    => "Adress",
            "business_operation_state"      => "PUNJAB",
            "business_operation_city"       => "city",
            "business_operation_pin"        => "123455",
            "promoter_pan"                  => "ABCPE1234F",
            "promoter_pan_name"             => "testhello",
            "business_doe"                  => "2016-01-01",
            "company_cin"                   => "qwer1234",
            "company_pan"                   => "qwert134",
            "company_pan_name"              => "test",
            "business_model"                => "BModel",
            "transaction_volume"            => 3,
            "transaction_value"             => 1000,
            "website_about"                 => "http://www.website.com/",
            "website_contact"               => "http://www.website.com/",
            "website_privacy"               => "http://www.website.com/",
            "website_terms"                 => "http://www.website.com/",
            "website_refund"                => "http://www.website.com/",
            "website_pricing"               => "http://www.website.com/",
            "website_login"                 => "",
            "steps_finished"                => "[]",
            "locked"                        => false,
            "submitted"                     => 0,
            "submitted_at"                  => null,
            "transaction_report_email"      => "test.test3@razorpay.com",
            "bank_account_number"           => "123456789012345",
            "bank_account_name"             => "test",
            "bank_account_type"             => "saving",
            "bank_branch"                   => "",
            "bank_branch_ifsc"              => "ICIC0000001",
            "bank_beneficiary_address1"     => "addere",
            "bank_beneficiary_address2"     => "add",
            "bank_beneficiary_address3"     => "",
            "bank_beneficiary_city"         => "city",
            "bank_beneficiary_state"        => "KA",
            "bank_beneficiary_pin"          => "123456",
            "address_proof_url"             => "124",
            "business_pan_url"              => "124",
            "business_proof_url"            => "124",
            "promoter_address_url"          => "124",
            "gstin"                         => "07AADCB2230M1ZV",
        ];

        $return = array_replace($return, $attributes);

        return $return;
    }
}
