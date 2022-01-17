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
}
