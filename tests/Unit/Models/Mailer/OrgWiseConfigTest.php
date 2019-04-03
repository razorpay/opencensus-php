<?php

namespace RZP\Tests\Unit\Models\Mailer;

use RZP\Models\Merchant;
use RZP\Mail\Base\OrgWiseConfig;
use RZP\Tests\Functional\TestCase;

class OrgWiseConfigTest extends TestCase
{
    const HDFC_ORG_CODE = 'hdfc';

    const TEST_ORG_CODE = 'dummy';

    /**
     * Org with code self::TEST_ORG_CODE has config present and has
     * payment captured mail set to true, authorized to false and
     * does not have entry for refunded mail.
     * Org with code self::TEST_ORG_CODE_2 does not have an entry
     * in the config array at all.
     */
    public function testEmailConfigDummyOrg()
    {
        $merchant = Merchant\Entity::find('10000000000000');

        $this->fixtures->merchant->addFeatures('payment_mails_disabled');

        $sendPaymentCaptureMail = OrgWiseConfig::getEmailEnabledForOrg(
            self::HDFC_ORG_CODE,
            \RZP\Mail\Payment\Captured::class,
            $merchant);

        $sendPaymentAuthorizeMail = OrgWiseConfig::getEmailEnabledForOrg(
            self::HDFC_ORG_CODE,
            \RZP\Mail\Payment\Authorized::class,
            $merchant);

        $sendPaymentRefundMail = OrgWiseConfig::getEmailEnabledForOrg(
            self::HDFC_ORG_CODE,
            \RZP\Mail\Payment\Authorized::class,
            $merchant);

        $sendPaymentRefundMailNoConfigOrg = OrgWiseConfig::getEmailEnabledForOrg(
            self::TEST_ORG_CODE,
            \RZP\Mail\Payment\Authorized::class,
            $merchant);

        $this->assertFalse($sendPaymentCaptureMail);

        $this->assertFalse($sendPaymentAuthorizeMail);

        $this->assertFalse($sendPaymentRefundMail);

        $this->assertTrue($sendPaymentRefundMailNoConfigOrg);
    }
}
