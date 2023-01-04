<?php

namespace RZP\Models\Merchant\XChannelDefinition;

class Channels
{

    // Channels
    const NIT                = 'NIT';                       // Not implemented yet
    const SALES_INITIATIVE   = 'Sales_Initiative';          // Not implemented yet
    const MOBILE_APP_SIGNUPS = 'Mobile_App_Signups';
    const OTHER_PRODUCT_BETS = 'Other Product Bets';        // Not implemented yet
    const PG                 = 'PG';
    const DIRECTX            = 'DirectX';
    const DIRECTX_CA         = 'DirectX_CA';
    const DIRECTX_APPS       = 'DirectX_Apps';
    const DIRECTX_CAPITAL    = 'DirectX_Capital';
    const DIRECT_SIGNUPS     = 'Direct Signups';
    const OTHERS             = 'Others';
    const UNMAPPED           = 'Unmapped';

    // Sub-channels

    // NIT sub-channels
    const NIT_ENTERPRISE_MID_MARKET = 'Enterprise / Mid-Market';
    const NIT_PARTNERSHIPS          = 'Partnerships';

    // Sales_Initiative sub-channels
    const SALES_INITIATIVE_PAYROLL_REFERRALS = 'Payroll_Referrals';
    const SALES_INITIATIVE_REFERRALS         = 'Referrals';

    // Mobile_App_Signups sub-channels
    const MOBILE_APP_SIGNUPS_DIRECT = 'Direct';

    // Other Product Bets sub-channels
    const OTHER_PRODUCT_BETS_RBL_COCREATED = 'RBL_CoCreated';   // This flow is shelved, remove it later

    // PG sub-channels
    const PG_NITRO                     = 'Nitro';
    const PG_BANKING_WIDGET            = 'Banking_Widget';
    const PG_ACCOUNT_LINKING           = 'Account_Linking';
    const PG_DIRECT_RAZORPAY           = 'Direct_Razorpay';
    const PG_APP_SWITCHER              = 'App_Switcher';
    const PG_PG_DASHBOARD_ANNOUNCEMENT = 'PG_Dashboard_Announcement';

    // DirectX, DirectX_CA, DirectX_Apps, DirectX_Capital sub-channels
    const SUB_CHANNEL_BANNER           = 'Banner';
    const SUB_CHANNEL_BLOG             = 'Blog';
    const SUB_CHANNEL_DIRECT           = 'Direct';
    const SUB_CHANNEL_EMAIL            = 'Email';
    const SUB_CHANNEL_ORGANIC          = 'Organic';
    const SUB_CHANNEL_OTHERS_MARKETING = 'Others_Marketing';
    const SUB_CHANNEL_PERFORMANCE      = 'Performance';
    const SUB_CHANNEL_REFERRAL         = 'Referral';
    const SUB_CHANNEL_SOCIAL           = 'Social';

    public static $channelPriorities = [
        self::NIT                => 1,
        self::SALES_INITIATIVE   => 2,
        self::MOBILE_APP_SIGNUPS => 3,
        self::OTHER_PRODUCT_BETS => 4,
        self::PG                 => 5,
        self::DIRECTX            => 6,
        self::DIRECTX_CA         => 7,
        self::DIRECTX_APPS       => 8,
        self::DIRECTX_CAPITAL    => 9,
        self::DIRECT_SIGNUPS     => 10,
        self::OTHERS             => 11,
    ];

    // Mapping of channels and URLs regex. If a channel uses the same set of URLs for all sub-channels, they can be
    // evaluated just once on the channel level.
    public static $channelRefWebsiteMapping = [
        self::NIT                => [],
        self::SALES_INITIATIVE   => [],
        self::MOBILE_APP_SIGNUPS => [],
        self::OTHER_PRODUCT_BETS => [],
        self::PG                 => [],
        self::DIRECTX            => [
            '/razorpay.com\/x\/$/',
            '/razorpay.com\/x\/\?/',
            '/razorpay.com\/x\/#/',
        ],
        self::DIRECTX_CA         => [
            '/razorpay.com\/x\/current-accounts\//',
            '/xproduct.razorpay.com\/current-account-for-startups/',
        ],
        self::DIRECTX_APPS       => [
            '/razorpay.com\/x\/payouts\//',
            '/razorpay.com\/x\/payout-links\//',
            '/razorpay.com\/x\/vendor-payments\//',
            '/razorpay.com\/x\/tax-payments\//',
            '/razorpay.com\/x\/accounting-payouts\//',
            '/razorpay.com\/x\/tds-online-payment\//',
            '/razorpay.com\/x\/accounting-payouts\//',
            '/razorpay.com\/x\/accounting-payouts\/quickbooks\//',
            '/razorpay.com\/x\/accounting-payouts\/tally-payouts\//',
        ],
        self::DIRECTX_CAPITAL    => [
            '/razorpay.com\/x\/corporate-cards\//',
            '/razorpay.com\/capital\//',
            '/razorpay.com\/capital\/instant-settlements\//',
            '/razorpay.com\/capital\/working-capital-loans\//',
            '/razorpay.com\/x\/instant-settlements-for-marketplace\//',
            '/razorpay.com\/capital\/cash-advance\//',
        ],
        self::DIRECT_SIGNUPS     => [
            '/x.razorpay.com\/auth\/signup/'
        ],
        self::OTHERS             => [
            '/razorpay.com\/x\/mobile-app\//',
            '/xproduct.razorpay.com\/watch-banking/',
            '/razorpay.com\/x\/neobank-report-for-smes-in-india\//',
            '/x.razorpay.com\/demo/',
            '/razorpay.com\/learn\//',
            '/https:\/\/razorpay.com\/docs\/#home-payments/',
            '/razorpay.com\/support\//',
            '/razorpay.com\/payroll\//',
            '/razorpay.com\/partners\//',
            '/razorpay.com\/terms\//',
            '/razorpay.com\/pricing\//',
            '/razorpay.com\/docs\//',
            '/razorpay.com\/privacy\//',
            '/razorpay.com\/app-store\/slack\//',
            '/razorpay.com\/agreement\//',
            '/razorpay.com\/x\/payouts-to-amazonpay\//',
            '/razorpay.com\/international-demo\//',
            '/razorpay.com\/dispute-guide\//',
            '/razorpay.com\/x\/referral-benefits\//',
            '/razorpay.com\/jobs\//',
            '/razorpay.com\/links\/covid19/',
            '/razorpay.com\/mor_terms\//',
            '/razorpay.com\/about\//',
        ],
        self::UNMAPPED           => [],
    ];

    // defining this separately since PG doesn't have same websites for all sub-channels
    // matching has to be strict on start & end of str.
    public static $refWebsitesForPG = [
        '/^razorpay.com\/x\/current-accounts\/$/',
        '/^razorpay.com\/x\/$/',
        '/^razorpay.com\/x\/payout-links\/$/',
        '/^razorpay.com\/x\/tax-payments\/$/',
        '/^razorpay.com\/x\/mobile-app\/$/',
        '/^razorpay.com\/x\/accounting-payouts\/$/',
        '/^razorpay.com\/x\/accounting-payouts\/quickbooks\/$/',
        '/^razorpay.com\/x\/accounting-payouts\/tally-payouts\/$/',
        '/^razorpay.com\/docs\/x\/$/',
        '/^razorpay.com\/blog\/category\/business-banking\/$/',
        '/^razorpay.com\/x\/tds-online-payment\/$/',
        '/^razorpay.com\/watch-banking\/$/',
        '/^razorpay.com\/current-account-for-startups\/$/',
        '/^razorpay.com\/x\/neobank-report-for-smes-in-india\/$/',
        '/^razorpay.com\/x\/for-yc-startups\/$/',
        '/^razorpay.com\/demo\/$/',
    ];

    // Mapping of default subchannels and their criteria
    public static $defaultSubChannels = [
        self::SUB_CHANNEL_BANNER           => [
            Constants::LAST_CLICK_SOURCE_CATEGORY => [Constants::LCS_CATEGORY_BANNER],
            Constants::FINAL_UTM_CAMPAIGN         => [],
            Constants::REF_WEBSITE                => [],
            Constants::SUBCHANNEL_PRIORITY        => 1,
        ],
        self::SUB_CHANNEL_BLOG             => [
            Constants::LAST_CLICK_SOURCE_CATEGORY => [Constants::LCS_CATEGORY_BLOG],
            Constants::FINAL_UTM_CAMPAIGN         => [],
            Constants::REF_WEBSITE                => [],
            Constants::SUBCHANNEL_PRIORITY        => 1,
        ],
        self::SUB_CHANNEL_DIRECT           => [
            Constants::LAST_CLICK_SOURCE_CATEGORY => [Constants::LCS_CATEGORY_DIRECT],
            Constants::FINAL_UTM_CAMPAIGN         => [],
            Constants::REF_WEBSITE                => [],
            Constants::SUBCHANNEL_PRIORITY        => 1,
        ],
        self::SUB_CHANNEL_EMAIL            => [
            Constants::LAST_CLICK_SOURCE_CATEGORY => [Constants::LCS_CATEGORY_EMAIL],
            Constants::FINAL_UTM_CAMPAIGN         => [],
            Constants::REF_WEBSITE                => [],
            Constants::SUBCHANNEL_PRIORITY        => 1,
        ],
        self::SUB_CHANNEL_ORGANIC          => [
            Constants::LAST_CLICK_SOURCE_CATEGORY => [Constants::LCS_CATEGORY_ORGANIC],
            Constants::FINAL_UTM_CAMPAIGN         => [],
            Constants::REF_WEBSITE                => [],
            Constants::SUBCHANNEL_PRIORITY        => 1,
        ],
        self::SUB_CHANNEL_OTHERS_MARKETING => [
            Constants::LAST_CLICK_SOURCE_CATEGORY => [Constants::LCS_CATEGORY_OTHERS_MARKETING],
            Constants::FINAL_UTM_CAMPAIGN         => [],
            Constants::REF_WEBSITE                => [],
            Constants::SUBCHANNEL_PRIORITY        => 1,
        ],
        self::SUB_CHANNEL_PERFORMANCE      => [
            Constants::LAST_CLICK_SOURCE_CATEGORY => [Constants::LCS_CATEGORY_PERFORMANCE],
            Constants::FINAL_UTM_CAMPAIGN         => [],
            Constants::REF_WEBSITE                => [],
            Constants::SUBCHANNEL_PRIORITY        => 1,
        ],
        self::SUB_CHANNEL_REFERRAL         => [
            Constants::LAST_CLICK_SOURCE_CATEGORY => [Constants::LCS_CATEGORY_REFERRAL],
            Constants::FINAL_UTM_CAMPAIGN         => [],
            Constants::REF_WEBSITE                => [],
            Constants::SUBCHANNEL_PRIORITY        => 1,
        ],
        self::SUB_CHANNEL_SOCIAL           => [
            Constants::LAST_CLICK_SOURCE_CATEGORY => [Constants::LCS_CATEGORY_SOCIAL],
            Constants::FINAL_UTM_CAMPAIGN         => [],
            Constants::REF_WEBSITE                => [],
            Constants::SUBCHANNEL_PRIORITY        => 1,
        ],
    ];

    // returns defaultSubchannels array after adding $refWebsites to each member
    public static function getDefaultSubChannelsWithRefWebsiteList(array $refWebsites) : array
    {
        $subChannelsWithRefWebsites =  self::$defaultSubChannels;

        foreach ($subChannelsWithRefWebsites as $subChannel => $properties) {
            $subChannelsWithRefWebsites[$subChannel][Constants::REF_WEBSITE] = $refWebsites;
        }

        return $subChannelsWithRefWebsites;
    }

    /**
     * Mapping of channels to sub-channels and their criteria. Only certain channels have been added here which are
     * currently implemented. Mobile_App_Signups is not included here as it is updated during pre-signup.
     * Priority is added but isn't used right now because PHP's associative array is ordered.
     *
     * @return array
     */
    public static function getChannelSubchannelMapping(): array
    {
        return [
            self::PG              => [
                self::PG_NITRO                     => [
                    Constants::LAST_CLICK_SOURCE_CATEGORY => [Constants::LCS_CATEGORY_PG_DASHBOARD],
                    Constants::FINAL_UTM_CAMPAIGN         => ['nitro'],
                    Constants::REF_WEBSITE                => [],
                    Constants::SUBCHANNEL_PRIORITY        => 1,
                ],
                self::PG_BANKING_WIDGET            => [
                    Constants::LAST_CLICK_SOURCE_CATEGORY => [Constants::LCS_CATEGORY_PG_DASHBOARD],
                    Constants::FINAL_UTM_CAMPAIGN         => ['banking_widget'],
                    Constants::REF_WEBSITE                => [],
                    Constants::SUBCHANNEL_PRIORITY        => 2,
                ],
                self::PG_ACCOUNT_LINKING           => [
                    Constants::LAST_CLICK_SOURCE_CATEGORY => [Constants::LCS_CATEGORY_PG_DASHBOARD],
                    Constants::FINAL_UTM_CAMPAIGN         => ['account_linking'],
                    Constants::REF_WEBSITE                => [],
                    Constants::SUBCHANNEL_PRIORITY        => 3,
                ],
                self::PG_DIRECT_RAZORPAY           => [
                    Constants::LAST_CLICK_SOURCE_CATEGORY => [],
                    Constants::FINAL_UTM_CAMPAIGN         => [],
                    Constants::REF_WEBSITE                => [
                        '/razorpay.com\/$/',
                        '/razorpay.com\/\?/',
                        '/razorpay.com\/#/',
                        '/razorpay.com\/payment-gateway\//',
                        '/razorpay.com\/payment-pages\//',
                        '/razorpay.com\/payment-links\//',
                        '/razorpay.com\/settlement\//',
                        '/razorpay.com\/e-mandate\//',
                        '/razorpay.com\/accept-international-payments\//',
                        '/razorpay.com\/offers\//',
                        '/razorpay.com\/magic\//',
                        '/razorpay.com\/links\/payment-links-reminders/',
                        '/razorpay.com\/payment-buttons\//',
                        '/razorpay.com\/freelancer-unregistered-business\//',
                        '/razorpay.com\/smart-collect\//',
                        '/razorpay.com\/qr-code\//',
                        '/razorpay.com\/gst-calculator\//',
                        '/razorpay.com\/flashcheckout\/manage\//',
                        '/razorpay.com\/payments-app\//',
                        '/razorpay.com\/upi\//',
                        '/razorpay.com\/payment-link\//',
                    ],
                    Constants::SUBCHANNEL_PRIORITY        => 4,
                ],
                self::PG_APP_SWITCHER              => [
                    Constants::LAST_CLICK_SOURCE_CATEGORY => [Constants::LCS_CATEGORY_PG_DASHBOARD],
                    Constants::FINAL_UTM_CAMPAIGN         => ['app_switcher'],
                    Constants::REF_WEBSITE                => [],
                    Constants::SUBCHANNEL_PRIORITY        => 5,
                ],
                self::PG_PG_DASHBOARD_ANNOUNCEMENT => [
                    Constants::LAST_CLICK_SOURCE_CATEGORY => [Constants::LCS_CATEGORY_PG_DASHBOARD],
                    Constants::FINAL_UTM_CAMPAIGN         => [],
                    Constants::REF_WEBSITE                => [],
                    Constants::SUBCHANNEL_PRIORITY        => 6,
                ],
                ...self::getDefaultSubChannelsWithRefWebsiteList(self::$refWebsitesForPG)
            ],
            self::DIRECTX         => self::$defaultSubChannels,
            self::DIRECTX_CA      => self::$defaultSubChannels,
            self::DIRECTX_APPS    => self::$defaultSubChannels,
            self::DIRECTX_CAPITAL => self::$defaultSubChannels,
            self::DIRECT_SIGNUPS  => self::$defaultSubChannels,
            self::OTHERS          => self::$defaultSubChannels,
            self::UNMAPPED        => self::$defaultSubChannels,
        ];

    }
}
