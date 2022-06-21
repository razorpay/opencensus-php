<?php

namespace RZP\Models\AMPEmail;

use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\BusinessDetail\Constants as BusinessConstant;

class Constants
{
    const MUTEX_LOCK_TIMEOUT     = '60';
    const MUTEX_RETRY_COUNT      = '2';
    const TRACE                  = 'trace';
    const API_MUTEX              = 'api.mutex';
    const EDIT                   = 'edit';
    const URL                    = 'url';
    const APPLICATIONS_MAILMODO  = 'applications.mailmodo';
    const L1_CAMPAIGN_ID         = 'l1_campaign_id';
    const TRIGGER_EMAIL_ENDPOINT = 'trigger_email_endpoint';
    const SUCCESS                = 'success';
    const TIMEOUT                = 'timeout';
    const CONNECT_TIMEOUT        = 'connect_timeout';
    const KEY                    = 'key';
    const AUTH                   = 'auth';
    const SECRET                 = 'secret';
    const PAYLOAD                = 'payload';
    const PATH                   = 'path';
    const RESPONSE               = 'response';
    const BODY                   = 'body';
    const HEADERS                = 'headers';
    const CONTENT_TYPE           = 'Content-Type';
    const APPLICATION_JSON       = 'application/json';
    const ACCEPT                 = 'Accept';
    const success                = 'success';
    const MESSAGE                = 'message';
    const REF                    = 'ref';
    const ERROR                  = 'error';
    const STATUS                 = 'status';
    const INPUT                  = 'input';
    const TOKEN                  = "token";

    //status
    const OPEN      = 'open';
    const CLOSE     = 'close';
    const FAILED    = 'failed';
    const INITIATED = 'initiated';

    //template
    const L1 = 'l1';

    //metadata
    const EMAIL        = 'email';
    const REFERENCE_ID = 'REFERENCE_ID';


    const MERCHANT              = 'merchant';
    const MAILMODO              = 'mailmodo';
    const RAZORPAY_EMAIL        = 'NOREPLY@RAZORPAY.COM';
    const GMAIL_ORIGIN          = 'HTTPS://MAIL.GOOGLE.COM';


    //L1 Form Fields
    const L1_FORM_FIELDS = [
        DetailEntity::COMPANY_PAN,
        DetailEntity::BUSINESS_NAME,
        DetailEntity::PROMOTER_PAN,
        DetailEntity::PROMOTER_PAN_NAME,
        DetailEntity::COMPANY_CIN,
        DetailEntity::BUSINESS_DBA,
        DetailEntity::BUSINESS_REGISTERED_ADDRESS,
        DetailEntity::BUSINESS_REGISTERED_PIN,
        DetailEntity::BUSINESS_REGISTERED_CITY,
        DetailEntity::BUSINESS_REGISTERED_STATE,
        DetailEntity::BUSINESS_OPERATION_ADDRESS,
        DetailEntity::BUSINESS_OPERATION_PIN,
        DetailEntity::BUSINESS_OPERATION_CITY,
        DetailEntity::BUSINESS_OPERATION_STATE,
        DetailEntity::BUSINESS_TYPE,
        DetailEntity::BUSINESS_CATEGORY,
        DetailEntity::BUSINESS_SUBCATEGORY,
        DetailEntity::BUSINESS_MODEL,
        'payments_mode',
        BusinessConstant::WEBSITE_OR_APP,
    ];

}
