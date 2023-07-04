<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use RZP\Models\Admin;
use RZP\Models\Feature;
use RZP\Constants\Entity as E;
use RZP\Tests\Functional\TestCase;
use RZP\Models\PaymentLink;
use RZP\Models\PaymentLink\ViewSerializer;
use RZP\Tests\Traits\PaymentLinkTestTrait;

class ViewSerializerTest extends TestCase
{
    use PaymentLinkTestTrait;

    const TEST_PL_ID    = '100000000000pl';
    const SUPPORT_EMAIL = 'abc@gmail.com';
    const SUPPORT_PHONE = '9732097320';


    /**
     * @group nocode_view_serializer
     *
     * @dataProvider supportDetailsDataProvider
     */
    public function testGetMerchantSupportDetails($supportEmail, $supportPhone)
    {
        if (isset($supportEmail) && isset($supportPhone))
        {
            // create support email
            $this->fixtures->create('merchant_email', [
                'type'   => 'support',
                'email'  => $supportEmail,
                'phone'  => $supportPhone,
                'policy' => 'tech',
                'url'    => 'https://razorpay.com'
            ]);
        }

        $attributes = [ PaymentLink\Entity::VIEW_TYPE => PaymentLink\ViewType::PAGE ];

        $pl = $this->createPaymentLink(self::TEST_PL_ID, $attributes);
        $mockViewSerializer = \Mockery::mock(ViewSerializer::class, [$pl])->makePartial();
        $mockViewSerializer->shouldAllowMockingProtectedMethods();

        // mock addFormattedEpochAttributesForPaymentLink method call
        $mockViewSerializer->shouldReceive('addFormattedEpochAttributesForPaymentLink')->andReturn([]);

        $serialized = $mockViewSerializer->serializeForHosted();

        $this->assertTrue(array_get($serialized, E::MERCHANT . ".support_email") === $supportEmail);
        $this->assertTrue(array_get($serialized, E::MERCHANT . ".support_mobile") === $supportPhone);
    }

    public function supportDetailsDataProvider(): array
    {
        return [
            [self::SUPPORT_EMAIL, self::SUPPORT_PHONE],
            ["", ""]
        ];
    }

    /**
     * @group nocode_view_serializer
     *
     * @param $showRzpLogo
     * @param $assetLogoUrl
     * @param $mockAxis
     * @dataProvider serializeOrgPropertiesForHostedDataProvider
     */
    public function testSerializeOrgPropertiesForHosted($showRzpLogo, $assetLogoUrl, $mockAxis)
    {
        $this->fixtures->merchant->activate('10000000000000');
        $this->fixtures->merchant->addFeatures(Feature\Constants::ORG_CUSTOM_BRANDING);
        if ($mockAxis === true)
        {
            $this->fixtures->org->createAxisOrg(
                [
                    'org'   => [
                        'custom_code' => 'axis',
                    ]
                ]
            );
            $this->fixtures->merchant->edit('10000000000000',
                [
                    'org_id' => Admin\Org\Entity::AXIS_ORG_ID
                ]
            );
        }

        $attributes = [ PaymentLink\Entity::VIEW_TYPE => PaymentLink\ViewType::PAGE ];

        $pl = $this->createPaymentLink(self::TEST_PL_ID, $attributes);
        $mockViewSerializer = \Mockery::mock(ViewSerializer::class, [$pl])->makePartial();
        $mockViewSerializer->shouldAllowMockingProtectedMethods();

        // mock addFormattedEpochAttributesForPaymentLink method call
        $mockViewSerializer->shouldReceive('addFormattedEpochAttributesForPaymentLink')->andReturn([]);

        $serialized = $mockViewSerializer->serializeForHosted();

        $this->assertTrue(array_get($serialized, "org.branding.show_rzp_logo") === $showRzpLogo);
        $this->assertTrue(array_get($serialized, "org.branding.branding_logo") === $assetLogoUrl);
    }

    public function serializeOrgPropertiesForHostedDataProvider(): array
    {
        return [
            [false, ViewSerializer::AXIS_BRANDING_LOGO, true],
            [true, '', false],
        ];
    }

    /**
     * @group nocode_view_serializer
     *
     * @param $isCurlec
     * @param $showRzpLogo
     * @param $brandingLogoUrl
     * @param $securityBrandingLogoUrl
     * @param $merchantCountryCode
     * @dataProvider serializePropertiesForHostedMalaysiaDataProvider
     */
    public function testSerializePropertiesForHostedMalaysia($isCurlec, $showRzpLogo, $brandingLogoUrl, $securityBrandingLogoUrl, $merchantCountryCode)
    {
        if($isCurlec === true)
        {
            $org = $this->fixtures->create('org:curlec_org');
            $this->fixtures->merchant->edit('10000000000000', ['country_code' => 'MY','org_id'    => $org->getId()]);
            $this->fixtures->merchant->addFeatures(Feature\Constants::ORG_CUSTOM_BRANDING);
        }

        $this->fixtures->merchant->activate('10000000000000');

        $attributes = [ PaymentLink\Entity::VIEW_TYPE => PaymentLink\ViewType::PAGE ];
        $pl = $this->createPaymentLink(self::TEST_PL_ID, $attributes);
        $pl['currency'] = 'MYR';

        $mockViewSerializer = \Mockery::mock(ViewSerializer::class, [$pl])->makePartial();
        $mockViewSerializer->shouldAllowMockingProtectedMethods();
        $mockViewSerializer->shouldReceive('addFormattedEpochAttributesForPaymentLink')->andReturn([]);

        $serialized = $mockViewSerializer->serializeForHosted();

        $this->assertTrue(array_get($serialized, "org.branding.show_rzp_logo") === $showRzpLogo);
        $this->assertEquals($brandingLogoUrl, array_get($serialized, "org.branding.branding_logo"));
        $this->assertEquals($securityBrandingLogoUrl, array_get($serialized, "org.branding.security_branding_logo"));
        $this->assertEquals($merchantCountryCode, array_get($serialized, "merchant.merchant_country_code"));
    }

    public function serializePropertiesForHostedMalaysiaDataProvider() : array
    {
        return [
            [true, true, 'https://rzp-1415-prod-dashboard-activation.s3.ap-south-1.amazonaws.com/org_KjWRtYXwpK6VfK/payment_apps_logo/phplelIPA', 'https://cdn.razorpay.com/static/assets/i18n/malaysia/security-branding.png', 'MY'],
            [false, true, '', '', 'IN'],
        ];
    }

    public function testPPBatchUpload()
    {
        $attributes = [ PaymentLink\Entity::VIEW_TYPE => PaymentLink\ViewType::FILE_UPLOAD_PAGE ];

        $pl = $this->createPaymentLink(self::TEST_PL_ID, $attributes);
        $mockViewSerializer = \Mockery::mock(ViewSerializer::class, [$pl])->makePartial();
        $mockViewSerializer->shouldAllowMockingProtectedMethods();

        // mock addSettingsOfPaymentLink method call
        $mockViewSerializer->shouldReceive('addSettingsOfPaymentLink')->andReturn([]);
        $serialized = $mockViewSerializer->serializeForHosted();

        $serialized['payment_link']['settings']['udf_schema']= "[{\"name\":\"pri__ref__id\",\"title\":\"Primary Reference ID\",\"required\":true,\"type\":\"string\",\"pattern\":\"alphanumeric\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":2}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":3}},{\"name\":\"blood_group\",\"title\":\"blood group\",\"required\":false,\"type\":\"string\",\"settings\":{\"position\":4}}]";

        $this->assertTrue($mockViewSerializer->isPPBatchUpload($serialized));
    }
}
