<?php

namespace RZP\Models\Merchant\XChannelDefinition;

final class Constants
{

    const SUBCHANNEL_PRIORITY = 'subchannel_priority';

    const CHANNEL    = 'channel';
    const SUBCHANNEL = 'subchannel';

    const FINAL_UTM_SOURCE           = 'final_utm_source';
    const FINAL_UTM_MEDIUM           = 'final_utm_medium';
    const FINAL_UTM_CAMPAIGN         = 'final_utm_campaign';
    const REF_WEBSITE                = 'ref_website'; // to be used while storing in merchant attributes
    const WEBSITE                    = 'website'; // to be used to retrieve the value from cookie
    const LAST_CLICK_SOURCE_CATEGORY = 'last_click_source_category';

    const UNKNOWN = 'unknown';

    const X_MOBILE_APP = 'x_mobile_app';

    const SF_CAMPAIGN_ID_BANKING_WIDGET = 'PG_X_Banking_Widget';

    // Last Click Source Categories
    const LCS_CATEGORY_BANNER             = 'Banner';
    const LCS_CATEGORY_BLOG               = 'Blog';
    const LCS_CATEGORY_DIRECT             = 'Direct';
    const LCS_CATEGORY_EMAIL              = 'Email';
    const LCS_CATEGORY_ENTERPRISE         = 'Enterprise';
    const LCS_CATEGORY_INDIVIDUAL         = 'Individual';
    const LCS_CATEGORY_MERCHANT_PARTNER   = 'Merchant_Partner';
    const LCS_CATEGORY_ORGANIC            = 'Organic';
    const LCS_CATEGORY_OTHERS_MARKETING   = 'Others_Marketing';
    const LCS_CATEGORY_PG_DASHBOARD       = 'PG Dashboard';
    const LCS_CATEGORY_PARTNER_INDIVIDUAL = 'Partner_Individual';
    const LCS_CATEGORY_PARTNERSHIP        = 'Partnership';
    const LCS_CATEGORY_PERFORMANCE        = 'Performance';
    const LCS_CATEGORY_PLATFORMS_PLUGINS  = 'Platforms_Plugins';
    const LCS_CATEGORY_REFERRAL           = 'Referral';
    const LCS_CATEGORY_SOCIAL             = 'Social';
    const LCS_CATEGORY_UNKNOWN            = 'Unknown';

    // Mapping of Last Click Source Category to Last Click Source Medium
    const LCS_CATEGORY_TO_SOURCE_MEDIUM_MAPPING = [
        self::LCS_CATEGORY_BLOG               => [
            'blog cta',
        ],
        self::LCS_CATEGORY_DIRECT             => [
            'direct website',
            'homepage banner',
            'productpage organic',
            'xhomepage banner',
            'xwebsite banner',
        ],
        self::LCS_CATEGORY_EMAIL              => [
            'email click',
        ],
        self::LCS_CATEGORY_ENTERPRISE         => [
            'Others_new',
        ],
        self::LCS_CATEGORY_INDIVIDUAL         => [
            'Individuals_new',
        ],
        self::LCS_CATEGORY_MERCHANT_PARTNER   => [
            'Merchant_Partner',
        ],
        self::LCS_CATEGORY_ORGANIC            => [
            'baidu organic',
            'bing organic',
            'bing search',
            'duckduckgo organic',
            'google organic',
            'yahoo organic',
            'yandex organic',
        ],
        self::LCS_CATEGORY_OTHERS_MARKETING   => [
            '/blogpage/',
            '/email/',
            '/exp-cta/',
            '/saasworthy.com cpc/',
            '/test#\/access\/signup/',
            '/uniquevaluehere/',
            '/yourstory techsparks/',
            'EPOS',
            'Razorpay 2.0 Subscriptions Mailer email',
            'customer_mailer email',
            'events startupsclub',
            'hostedpage footer',
            'hostedpage success-page-footer',
            'hs_automation email',
            'hs_automation#/app/activation email',
            'hs_email email',
            'koinex referral',
            'mailchimp email',
            'outbound-mca email',
            'razorpay 2.0 subscriptions mailer email',
            'razorpay subscriptions mailers email2.0',
            'razorpay-page link',
            'referral producthunt',
            'referral sms',
            'shopify referral',
            'signup banner',
            'sms click',
            'sniply sniply',
            'ssl-email cta-click',
            'ssl-email link-click',
            'ssl-landing-page cta-click',
            'test#/access/signup',
            'typeform web',
            'uniquevaluehere',
        ],
        self::LCS_CATEGORY_PG_DASHBOARD       => [
            'pgdashboard annoucement',
            'pg dashboard',
        ],
        self::LCS_CATEGORY_PARTNER_INDIVIDUAL => [
            'Partner_Individual',
        ],
        self::LCS_CATEGORY_PARTNERSHIP        => [
            'Partnership_new',
        ],
        self::LCS_CATEGORY_PERFORMANCE        => [
            '/Bing cpc/',
            '/Google Display/',
            '/Google Paid/',
            '/analyzo/',
            '/bing cpc/',
            '/taboola/',
            'Facebook Conversion',
            'Google CPC',
            'Google PPC',
            'Google Search',
            'adroll programmatic',
            'bing CPC',
            'bing Paid',
            'bing paid',
            'colombia paid',
            'facebook conversion',
            'facebook cpc',
            'facebook paid',
            'google cpc',
            'google discovery',
            'google display',
            'google gsp',
            'google paid',
            'google pmax',
            'google ppc',
            'google search',
            'google video',
            'google youtube',
            'google',
            'googleads cpc',
            'linkedin cpc',
            'mediaant referral',
            'quora paid',
            'reddit paid',
            'referral times-columbia',
            'taboola native',
            'taboola referral',
            'twitter cpc',
            'twitter paid',
        ],
        self::LCS_CATEGORY_PLATFORMS_PLUGINS  => [
            'Platforms_Plugins'
        ],
        self::LCS_CATEGORY_SOCIAL             => [
            '/linkedinpost/',
            '/paidsocial/',
            'Facebook Social',
            'blog facebook',
            'blogpage',
            'facebook organic',
            'facebook paidsocial',
            'facebook post',
            'facebook social',
            'facebook social-paid',
            'linkedin paidsocial',
            'linkedin social',
            'linkedinpost',
            'ph social',
            'producthunt referral',
            'quora social',
            'social facebook',
            'social quora',
            'twitter social',
            'youtube social',
            'youtube video',
        ],
    ];


}
