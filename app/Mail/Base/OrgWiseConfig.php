<?php

namespace RZP\Mail\Base;

use App;
use RZP\Models\Admin\Org;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Feature\Constants as Features;

class OrgWiseConfig
{
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
     * If a mailer does not have entry here the it will be sent.
     * If there is an entry then it will be sent if the merchant
     * does not have the blocking feature for that mailer if the
     * restriction applies to his org.
     */
    const captureOrgList = [
        self::HDFC,
        self::AXIS_EASY_PAY,
        self::AXIS_BANK_BQR_TEAM,
        self::AXIS_BANK_LTD,
        self::HDFC_COLLECT_NOW,
        self::HDFC_BANK_GIG,
        self::ICICI_BANK,
        self::SOUTH_INDIAN_BANK,
        self::HSBC,
        self::KOTAK,
        self::YES_BANK,
        self::JK_BANK_IPG,
        self::BANK_OF_BARODA,
        self::TJSB_BANK
    ];

    const authorisedOrgList = [
        self::HDFC,
        self::AXIS_EASY_PAY,
        self::AXIS_BANK_BQR_TEAM,
        self::AXIS_BANK_LTD,
        self::HDFC_COLLECT_NOW,
        self::HDFC_BANK_GIG,
        self::ICICI_BANK,
        self::SOUTH_INDIAN_BANK,
        self::HSBC,
        self::KOTAK,
        self::YES_BANK,
        self::JK_BANK_IPG,
        self::BANK_OF_BARODA,
        self::TJSB_BANK];

    const failedOrgList = [
        self::HDFC,
        self::AXIS_EASY_PAY,
        self::AXIS_BANK_BQR_TEAM,
        self::AXIS_BANK_LTD,
        self::HDFC_COLLECT_NOW,
        self::HDFC_BANK_GIG,
        self::ICICI_BANK,
        self::SOUTH_INDIAN_BANK,
        self::HSBC,
        self::KOTAK,
        self::YES_BANK,
        self::JK_BANK_IPG,
        self::BANK_OF_BARODA,
        self::TJSB_BANK
    ];

    const failedToAuthorisedOrgList = [
        self::HDFC,
        self::AXIS_EASY_PAY,
        self::AXIS_BANK_BQR_TEAM,
        self::AXIS_BANK_LTD,
        self::HDFC_COLLECT_NOW,
        self::HDFC_BANK_GIG,
        self::ICICI_BANK,
        self::SOUTH_INDIAN_BANK,
        self::HSBC,
        self::KOTAK,
        self::YES_BANK,
        self::JK_BANK_IPG,
        self::BANK_OF_BARODA,
        self::TJSB_BANK
    ];

    const refundOrgList = [
        self::HDFC,
        self::AXIS_EASY_PAY,
        self::AXIS_BANK_BQR_TEAM,
        self::AXIS_BANK_LTD,
        self::HDFC_COLLECT_NOW,
        self::HDFC_BANK_GIG,
        self::ICICI_BANK,
        self::SOUTH_INDIAN_BANK,
        self::HSBC,
        self::KOTAK,
        self::YES_BANK,
        self::JK_BANK_IPG,
        self::BANK_OF_BARODA,
        self::TJSB_BANK
    ];

    const authorisedReminderOrgList = [
        self::HDFC,
        self::AXIS_EASY_PAY,
        self::AXIS_BANK_BQR_TEAM,
        self::AXIS_BANK_LTD,
        self::HDFC_COLLECT_NOW,
        self::HDFC_BANK_GIG,
        self::ICICI_BANK,
        self::SOUTH_INDIAN_BANK,
        self::HSBC,
        self::KOTAK,
        self::YES_BANK,
        self::JK_BANK_IPG,
        self::BANK_OF_BARODA,
        self::TJSB_BANK
    ];

    const dailyReportOrgList = [
        self::HDFC,
        self::AXIS_EASY_PAY,
        self::AXIS_BANK_BQR_TEAM,
        self::AXIS_BANK_LTD,
        self::HDFC_COLLECT_NOW,
        self::HDFC_BANK_GIG,
        self::ICICI_BANK,
        self::SOUTH_INDIAN_BANK,
        self::HSBC,
        self::KOTAK,
        self::YES_BANK,
        self::JK_BANK_IPG,
        self::BANK_OF_BARODA,
        self::TJSB_BANK
    ];

    const customerFailedOrgList = [
        self::HDFC,
        self::AXIS_EASY_PAY,
        self::AXIS_BANK_BQR_TEAM,
        self::AXIS_BANK_LTD,
        self::HDFC_COLLECT_NOW,
        self::HDFC_BANK_GIG,
        self::ICICI_BANK,
        self::SOUTH_INDIAN_BANK,
        self::HSBC,
        self::KOTAK,
        self::YES_BANK,
        self::JK_BANK_IPG,
        self::BANK_OF_BARODA,
        self::TJSB_BANK
    ];


    const CONFIG = [
        \Rzp\Mail\User\AccountVerification::class            => [
            Features::SELF_KYC_DISABLED => [self::HDFC]
        ],
        \Rzp\Mail\User\PasswordReset::class                  => [
            Features::SELF_KYC_DISABLED => [self::HDFC]
        ],
        \RZP\Mail\Merchant\NotifyActivationSubmission::class => [
            Features::SELF_KYC_DISABLED => [self::HDFC]
        ],
        \RZP\Mail\Merchant\Activation::class                 => [
            Features::SELF_KYC_DISABLED => [self::HDFC]
        ],
        \RZP\Mail\Merchant\RequestNeedsClarification::class  => [
            Features::SELF_KYC_DISABLED => [self::HDFC]
        ],
        \RZP\Mail\Merchant\RequestRejection::class           => [
            Features::SELF_KYC_DISABLED => [self::HDFC]
        ],
        \RZP\Mail\Merchant\InstantActivation::class          => [
            Features::SELF_KYC_DISABLED => [self::HDFC]
        ],
        \RZP\Mail\Payment\Captured::class                    => [
            Features::PAYMENT_MAILS_DISABLED => self::captureOrgList
        ],
        \RZP\Mail\Payment\Authorized::class                  => [
            Features::PAYMENT_MAILS_DISABLED => self::authorisedOrgList
        ],
        \RZP\Mail\Payment\Failed::class                      => [
            Features::PAYMENT_MAILS_DISABLED => self::failedOrgList
        ],
        \RZP\Mail\Payment\FailedToAuthorized::class          => [
            Features::PAYMENT_MAILS_DISABLED => self::failedToAuthorisedOrgList
        ],
        \RZP\Mail\Payment\Refunded::class                    => [
            Features::PAYMENT_MAILS_DISABLED => self::refundOrgList
        ],
        \RZP\Mail\Merchant\AuthorizedPaymentsReminder::class => [
            Features::PAYMENT_MAILS_DISABLED => self::authorisedReminderOrgList
        ],
        \RZP\Mail\Merchant\DailyReport::class                => [
            Features::PAYMENT_MAILS_DISABLED => self::dailyReportOrgList
        ],
        \RZP\Mail\Payment\CustomerFailed::class              => [
            Features::PAYMENT_MAILS_DISABLED => self::customerFailedOrgList
        ]
    ];

    public static function getEmailEnabledForOrg(string $customCode, string $mailerClass, Merchant $merchant): bool
    {
        if (array_key_exists($mailerClass, self::CONFIG) === false)
        {
            // Send mail if mailer has no entry in org config
            return true;
        }

        $blockingFeature = array_keys(self::CONFIG[$mailerClass])[0]; //[$mailerClass];

        $orgRestricted = in_array($customCode, self::CONFIG[$mailerClass][$blockingFeature]);

        $hasBlockingFeature = $merchant->isFeatureEnabled($blockingFeature);

        // If merchant has the blocking feature return false, otherwise return true and let the mail be sent
        return (($orgRestricted && $hasBlockingFeature) === false);
    }

    public static function getOrgDataForEmail($merchant = null) : array
    {
        $app = App::getFacadeRoot();

        $repo = $app['repo'];

        $data = [];

        $customBranding = isset($merchant) ?
                            (new MerchantCore())->isOrgCustomBranding($merchant):
                            false;

        if ($customBranding === true)
        {
            $orgId = $merchant->getOrgId();

            $orgDetails = $repo->org->find($orgId);

            $data['email_logo'] = $orgDetails->getEmailLogo();

            $data['org_name'] = $orgDetails->getDisplayName();

            $data['checkout_logo'] = $orgDetails->getCheckoutLogo();

            $data['hostname'] = $orgDetails->getPrimaryHostName();

            $data['custom_code'] = $orgDetails->getCustomCode();

        }
        else
        {
            $razorpayOrg = $repo->org->find(Org\Entity::RAZORPAY_ORG_ID);

            $data['email_logo'] = $razorpayOrg->getMainLogo();

            $data['org_name'] = $razorpayOrg->getDisplayName();

            $data['checkout_logo'] = $razorpayOrg->getCheckoutLogo();

            $data['hostname'] = $razorpayOrg->getPrimaryHostName();

            $data['custom_code'] = $razorpayOrg->getCustomCode();
        }

        $data['custom_branding'] = $customBranding;

        return $data;
    }
}
