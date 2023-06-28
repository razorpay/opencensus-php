<?php

namespace RZP\Models\Merchant\Website;

use RZP\Models\Merchant\BusinessDetail\Constants as BConstants;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;

class Constants
{

    const PUBLISH_TIME_LIMIT  = 10;
    const DOWNLOAD_TIME_LIMIT = 5;

    //website_details
    const SECTION_STATUS = 'section_status';
    const URL            = 'url';
    const DOCUMENT_ID    = 'document_id';
    const STATUS         = 'status';
    const PUBLISHED_URL  = 'published_url';
    const UPDATED_AT     = 'updated_at';
    const SUBMITTED      = 'submitted';
    const SUBMIT         = 'submit';
    const SECTION_URL    = 'section_url';
    const ACTION         = 'action';
    const PUBLISH        = 'publish';
    const DOWNLOAD       = 'download';
    const UPLOAD         = 'upload';
    const DELETE         = 'delete';

    //section names
    const CONTACT_US   = 'contact_us';
    const TERMS        = 'terms';
    const REFUND       = 'refund';
    const CANCELLATION = 'cancellation';
    const PRIVACY      = 'privacy';
    const SHIPPING     = 'shipping';
    const ABOUT_US     = 'about_us';
    const PRICING      = 'pricing';
    const COMMENTS     = 'comments';

    const CONTACT_US_NAME = 'Contact Us';
    const TERMS_NAME      = 'Terms and Conditions';
    const REFUND_NAME     = 'Cancellation and Refund Policy';
    const PRIVACY_NAME    = 'Privacy Policy';
    const SHIPPING_NAME   = 'Shipping and Delivery Policy';

    //additional_data
    const SUPPORT_EMAIL = 'support_email';
    const SUPPORT_PHONE = 'support_contact_number';

    const SECTION_NAME  = 'section_name';
    const WEBSITE       = 'website';
    const PLAYSTORE_URL = 'playstore_url';
    const APPSTORE_URL  = 'appstore_url';

    const URL_TYPE                    = 'url_type';
    const MERCHANT_CONSENT            = 'merchant_consent';
    const MERCHANT_POLICIES_SUBDOMAIN = 'MERCHANT_POLICIES_SUBDOMAIN';
    const FILE                        = 'file';


    const NEEDS_CLARIFICATION_KEYS = [
        self::PLAYSTORE_URL,
        self::APPSTORE_URL,
        DetailEntity::BUSINESS_WEBSITE
    ];

    const VALID_MERCHANT_ACTION = self::UPLOAD . ',' .
                                  self::DELETE . ',' .
                                  self::DOWNLOAD . ',' .
                                  self::SUBMIT . ',' .
                                  self::PUBLISH;

    const VALID_ADMIN_ACTION = self::UPLOAD . ',' .
                               self::DELETE;


    const SECTION_DISPLAY_NAME_MAPPING = [
        self::CONTACT_US => self::CONTACT_US_NAME,
        self::TERMS      => self::TERMS_NAME,
        self::REFUND     => self::REFUND_NAME,
        self::PRIVACY    => self::PRIVACY_NAME,
        self::SHIPPING   => self::SHIPPING_NAME
    ];

    const VALID_MERCHANT_SECTIONS = self::TERMS . ',' .
                                    self::CONTACT_US . ',' .
                                    self::REFUND . ',' .
                                    self::PRIVACY . ',' .
                                    self::SHIPPING;


    const VALID_ADMIN_FIELDS_LIST = [
        self::URL,
        self::DOCUMENT_ID
    ];

    const VALID_ADMIN_SECTIONS = self::TERMS . ',' .
                                 self::CONTACT_US . ',' .
                                 self::REFUND . ',' .
                                 self::PRIVACY . ',' .
                                 self::SHIPPING . ',' .
                                 self::PRICING . ',' .
                                 self::ABOUT_US . ',' .
                                 self::CANCELLATION . ',' .
                                 self::COMMENTS;

    const MANDATORY_ADMIN_SECTIONS = [self::TERMS,
                                      self::CONTACT_US,
                                      self::REFUND,
                                      self::PRIVACY,
                                      self::SHIPPING,
                                      self::PRICING,
                                      self::CANCELLATION] ;


    // 1- I have live page with required details
    // 2- I have live page but some details are missing
    // 3- I don't have this page and need help in creating it
    const VALID_SECTION_STATUS = '1,2,3';

    const VALID_STATUS = self::SUBMITTED;

    const VALID_URL_TYPE = self::WEBSITE . ',' .
                           self::PLAYSTORE_URL . ',' .
                           self::APPSTORE_URL;

    const VALID_DELIVERABLE_TYPE = 'goods,services';

    const SHIPPING_PERIOD = 'shipping_period';

    const QUESTION_ID = 'question_id';

    const ANSWER = 'answer';

    const COMMON_QUESTIONS_IN_WEBSITE_POLICY_AND_BMC = [
        self::SHIPPING_PERIOD => 'question_2',
    ];

    const WEBSITE_POLICY_QUESTION_MAPPING = [
        self::SHIPPING_PERIOD => [
            self::QUESTION_ID           => 'question_2',
            '0-2 days'                  => 'option_2_1',
            '3-5 days'                  => 'option_2_2',
            '6-8 days'                  => 'option_2_3',
            '8+ days'                   => 'option_2_4',
            'Not applicable'            => 'option_2_5',
        ],
    ];

    const FIELD_VALUE_TYPE_MAPPING = [
        self::SHIPPING_PERIOD => 'string',
    ];

}
