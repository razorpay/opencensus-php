<?php

namespace RZP\Tests\Unit\Models\Mailer;

use RZP\Models\Merchant;
use RZP\Mail\Base\OrgWiseConfig;
use RZP\Tests\Functional\TestCase;

class OrgWiseConfigTest extends TestCase
{

    const TEST_ORG_CODE = 'dummy';
    const HDFC  = 'hdfc';
    const AXIS_EASY_PAY = 'UTIB';
    const AXIS_BANK_BQR_TEAM = 'axisbank';
    const AXIS_BANK_LTD = 'axis';
    const HDFC_COLLECT_NOW ='HDFC';
    const HDFC_BANK_GIG =  'HDFC GIG';
    const ICICI_BANK = 'icic';
    const SOUTH_INDIAN_BANK = 'SIBL';
    const HSBC = 'HSBC';
    const KOTAK = 'KKBK';
    const YES_BANK = 'YESB';
    const JK_BANK_IPG = 'jkb';
    const BANK_OF_BARODA = 'bob';
    const TJSB_BANK = 'tjsb';


    /**
     * Org with code self::TEST_ORG_CODE has config present and has
     * payment captured mail set to true, authorized to false and
     * does not have entry for refunded mail.
     * Org with code self::TEST_ORG_CODE_2 does not have an entry
     * in the config array at all.
     */
    public function emailEnabledForOrgTest($merchant, $customCode)
    {
        $sendPaymentCaptureMail = OrgWiseConfig::getEmailEnabledForOrg(
            $customCode,
            \RZP\Mail\Payment\Captured::class,
            $merchant);

        $sendPaymentAuthorizeMail = OrgWiseConfig::getEmailEnabledForOrg(
            $customCode,
            \RZP\Mail\Payment\Authorized::class,
            $merchant);

        $sendPaymentRefundMail = OrgWiseConfig::getEmailEnabledForOrg(
            $customCode,
            \RZP\Mail\Payment\Authorized::class,
            $merchant);

        $sendPaymentFailedMail = OrgWiseConfig::getEmailEnabledForOrg(
            $customCode,
            \RZP\Mail\Payment\Failed::class,
            $merchant);

        $sendPaymentFailedToAuthorisedMail = OrgWiseConfig::getEmailEnabledForOrg(
            $customCode,
            \RZP\Mail\Payment\FailedToAuthorized::class,
            $merchant);

        $sendPaymentFailedToAuthorisedPaymentReminder = OrgWiseConfig::getEmailEnabledForOrg(
            $customCode,
            \RZP\Mail\Merchant\AuthorizedPaymentsReminder::class,
            $merchant);

        $sendDailyReport = OrgWiseConfig::getEmailEnabledForOrg(
            $customCode,
            \RZP\Mail\Merchant\DailyReport::class,
            $merchant);

        $sendCustomerFailed = OrgWiseConfig::getEmailEnabledForOrg(
            $customCode,
            \RZP\Mail\Payment\CustomerFailed::class,
            $merchant);


        $sendPaymentRefundMailNoConfigOrg = OrgWiseConfig::getEmailEnabledForOrg(
            self::TEST_ORG_CODE,
            \RZP\Mail\Payment\Authorized::class,
            $merchant);

        $this->assertFalse($sendPaymentCaptureMail);

        $this->assertFalse($sendPaymentAuthorizeMail);

        $this->assertFalse($sendPaymentRefundMail);

        $this->assertFalse($sendPaymentFailedMail);

        $this->assertFalse($sendPaymentFailedToAuthorisedMail);

        $this->assertFalse($sendPaymentFailedToAuthorisedPaymentReminder);

        $this->assertFalse($sendDailyReport);


        $this->assertTrue($sendPaymentRefundMailNoConfigOrg);
    }

    public function testEmailConfigDummyForOrgs()
    {
        $merchant = Merchant\Entity::find('10000000000000');

        $this->fixtures->merchant->addFeatures('payment_mails_disabled');

       $org_custom_code = [self::HDFC, self::AXIS_EASY_PAY, self::AXIS_BANK_BQR_TEAM, self::AXIS_BANK_LTD, self::HDFC_COLLECT_NOW, self::HDFC_BANK_GIG, self::ICICI_BANK, self::SOUTH_INDIAN_BANK, self::HSBC,self::KOTAK, self::YES_BANK, self::JK_BANK_IPG, self::BANK_OF_BARODA, self::TJSB_BANK];

        foreach ($org_custom_code as $value)
        {
            $this->emailEnabledForOrgTest($merchant, $value);
        }
    }
}
