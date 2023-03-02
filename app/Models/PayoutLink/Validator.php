<?php

namespace RZP\Models\PayoutLink;

use RZP\Http\Request\Requests;
use App;
use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Payout\Mode;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\Constants as PayoutConstants;
use RZP\Models\PayoutLink\Constants as PayoutLinkConstants;
use RZP\Models\Workflow\Action\Checker\Entity as ActionChecker;


class Validator extends Base\Validator
{
    const CONTACT_ID                       = 'contact.id';

    // This rule is used for the payout-link create api, along with rules for contact details,
    // as this api supports contact creation too along with payout-link creation
    const COMPOSITE_CREATE_RULE            = 'composite_create';
    const VERIFY_OTP                       = 'verify_otp';
    const GET_FUND_ACCOUNT_BY_CONTACT_RULE = 'get_fund_account_by_contact';
    const GENERATE_OTP                     = 'generate_otp';
    const RESEND_NOTIFICATION_RULE         = 'resend_notification';
    const ADD_FUND_ACCOUNT_RULE            = 'add_fund_account';
    const SETTINGS_RULE                    = 'settings';
    const SEND_OTP_EMAIL_INTERNAL_RULE     = 'send_otp_email_internal';
    const SEND_LINK_EMAIL_INTERNAL_RULE    = 'send_link_email_internal';
    const SEND_DEMO_OTP_EMAIL_INTERNAL_RULE   = 'send_demo_otp_email_internal';
    const SEND_DEMO_LINK_EMAIL_INTERNAL_RULE  = 'send_demo_link_email_internal';
    const SEND_SUCCESS_EMAIL_INTERNAL_RULE    = 'send_success_email_internal';
    const SEND_FAILURE_EMAIL_INTERNAL_RULE    = 'send_failure_email_internal';
    const SEND_REMINDER_EMAIL_INTERNAL_RULE   = 'send_reminder_email_internal';
    const SEND_PROCESSING_EXPIRED_EMAIL_INTERNAL_RULE   = 'send_processing_expired_email_internal';
    const SEND_APPROVE_OTP_EMAIL_INTERNAL_RULE   = 'send_approve_otp_email_internal';
    const OWNER_BULK_REJECT_PAYOUT_LINKS   = 'owner_bulk_reject_payout_links';
    const SEND_BULK_APPROVE_OTP_EMAIL_INTERNAL_RULE   = 'send_bulk_approve_otp_email_internal';
    const FETCH_PENDING_PAYOUT_LINKS       = 'fetch_pending_payout_links';
    const MAX_IMPS_AMOUNT                  = 50000000;
    const MAX_UPI_AMOUNT                   = 10000000;
    const MAX_AMAZON_PAY_AMOUNT            = 1000000;
    const RESEND_NOTIFICATION_PARAMS       = 'resend_notification_params';
    const NOTIFICATION_SETTINGS            = 'notification_settings';
    const BATCH_CREATE                     = 'batch_create';
    const CREATE_DEMO_PAYOUT_LINK            = 'create_demo_payout_link';
    const GOOGLE_CAPTCHA_VERIFICATION_ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

    protected static $batchCreateRules = [
        'type'      => 'required|string',
    ];

    protected static $resendNotificationRules = [
        Entity::SEND_EMAIL           => 'sometimes|boolean',
        Entity::SEND_SMS             => 'sometimes|boolean',
        Entity::CONTACT_EMAIL        => 'sometimes|nullable|email',
        Entity::CONTACT_PHONE_NUMBER => 'sometimes|nullable|contact_syntax',
    ];

    protected static $sendOtpEmailInternalRules = [
        Entity::MERCHANT_ID => 'required|string',
        Entity::PURPOSE     => 'required|filled|string|max:30|alpha_dash_space',
        Entity::OTP         => 'required|string|min:4|max:6',
        Entity::TO_EMAIL    => 'required|email',
    ];

    protected static $sendDemoOtpEmailInternalRules = [
        'merchantinfo'      => 'required|array',
        Entity::PURPOSE     => 'required|filled|string|max:30|alpha_dash_space',
        Entity::OTP         => 'required|string|min:4|max:6',
        Entity::TO_EMAIL    => 'required|email',
    ];

    protected static $sendLinkEmailInternalRules = [
        Entity::MERCHANT_ID => 'required|string',
        Entity::TO_EMAIL    => 'required|email',
        'payoutlinkresponse'=> 'required|array',
    ];

    protected static $sendDemoLinkEmailInternalRules = [
        Entity::TO_EMAIL    => 'required|email',
        'payoutlinkresponse'=> 'required|array',
        'merchantinfo'      => 'required|array',
    ];

    protected static $sendSuccessEmailInternalRules = [
        Entity::MERCHANT_ID => 'required|string',
        Entity::TO_EMAIL    => 'required|email',
        'payoutlinkresponse'=> 'required|array',
        'settings'          => 'required|array',
    ];


    protected static $sendFailureEmailInternalRules = [
        Entity::MERCHANT_ID => 'required|string',
        Entity::TO_EMAIL    => 'required|email',
        'payoutlinkresponse'=> 'required|array',
        'settings'          => 'required|array',
    ];

    protected static $settingsRules = [
        Mode::UPI               => 'sometimes|boolean|filled',
        Mode::IMPS              => 'sometimes|boolean|filled',
        Entity::SUPPORT_URL     => 'sometimes|string|url',
        Entity::SUPPORT_EMAIL   => 'sometimes|string|email',
        Entity::SUPPORT_CONTACT => 'sometimes|string|contact_syntax',
        Entity::CUSTOM_MESSAGE  => 'sometimes|string|max:255',
        Entity::TICKET_ID       => 'sometimes|string|max:255'
    ];

    protected static $addFundAccountRules = [
        Entity::FUND_ACCOUNT_ID => 'filled|string|public_id',
        Entity::ACCOUNT_TYPE    => 'required_if:fund_account_id,null|string|in:bank_account,vpa',
        Entity::VPA             => 'required_if:type,vpa|array',
        Entity::BANK_ACCOUNT    => 'required_if:type,bank_account|array',
        Entity::TOKEN           => 'required|string'
    ];

    protected static $getFundAccountByContactRules = [
        Entity::TOKEN => 'required|string'
    ];

    protected static $generateOtpRules = [
        Entity::CONTEXT => 'sometimes|string|min:5|max:15'
    ];

    protected static $createRules = [
        Entity::CONTACT_NAME         => 'required|string|max:50',
        Entity::CONTACT_EMAIL        => 'sometimes|nullable|email',
        Entity::CONTACT_PHONE_NUMBER => 'sometimes|nullable|contact_syntax',
        Entity::BALANCE_ID           => 'required|string|size:14',
        Entity::AMOUNT               => 'required|integer|min:100|max:' . Entity::MAX_PAYOUT_LIMIT,
        Entity::CURRENCY             => 'required|size:3|in:INR',
        Entity::NOTES                => 'sometimes|notes',
        Entity::DESCRIPTION          => 'required|string|max:255',
        Entity::PURPOSE              => 'required|filled|string|max:30|alpha_dash_space',
        Entity::RECEIPT              => 'sometimes|string|max:40',
        Entity::SEND_EMAIL           => 'sometimes|boolean',
        Entity::SEND_SMS             => 'sometimes|boolean'
    ];

    protected static $compositeCreateRules = [
        Entity::AMOUNT         => 'required|integer',
        Entity::CURRENCY       => 'required|size:3|in:INR',
        Entity::NOTES          => 'sometimes|notes',
        Entity::ACCOUNT_NUMBER => 'required|alpha_num|between:5,40',
        Entity::DESCRIPTION    => 'required|string|max:255',
        Entity::PURPOSE        => 'required|filled|string|max:30|alpha_dash_space',
        Entity::RECEIPT        => 'sometimes|string|max:40',
        Entity::CONTACT        => 'required|array',
        Entity::SEND_EMAIL     => 'sometimes|boolean',
        Entity::SEND_SMS       => 'sometimes|boolean'
    ];

    protected static $verifyOtpRules = [
        Entity::OTP     => 'required|string|min:4|max:6',
        Entity::CONTEXT => 'sometimes|string|min:5|max:15'
    ];

    protected static $createValidators = [
        Entity::CONTACT,
        self::NOTIFICATION_SETTINGS
    ];

    protected static $resendNotificationValidators = [
        self::RESEND_NOTIFICATION_PARAMS
    ];

    protected static $createDemoPayoutLinkRules = [
        'g-recaptcha-response' => 'required|custom'
    ];

    protected static $sendReminderEmailInternalRules = [
        Entity::MERCHANT_ID   => 'required|string',
        Entity::TO_EMAIL      => 'required|email',
        'payout_link_details' => 'required|array',
    ];

    protected static $sendProcessingExpiredEmailInternalRules = [
        Entity::MERCHANT_ID   => 'required|string',
        Entity::TO_EMAIL      => 'required|email',
        'payout_link_details' => 'required|array',
        'settings'            => 'required|array',
    ];

    protected static $sendApproveOtpEmailInternalRules = [
        'to_email'            => 'required|email',
        'payout_link_details' => 'required|array',
        'otp'                 => 'required|string|min:4|max:6',
        'validity'            => 'required|string',
    ];

    protected static $sendBulkApproveOtpEmailInternalRules = [
        'to_email'            => 'required|email',
        'total_amount'        => 'required|numeric',
        'payout_links_count'  => 'required|integer',
        'otp'                 => 'required|string|min:4|max:6',
        'validity'            => 'required|string',
    ];

    protected static $ownerBulkRejectPayoutLinksRules = [
        PayoutLinkConstants::PAYOUT_LINK_IDS            => 'required|array',
        PayoutLinkConstants::PAYOUT_LINK_IDS . '.*'     => 'required|public_id|size:21',
        PayoutLinkConstants::BULK_REJECT_AS_OWNER       => 'required|boolean',
        ActionChecker::USER_COMMENT                     => 'sometimes|nullable|string|max:255',
    ];

    protected static $fetchPendingPayoutLinksRules = [
        PayoutConstants::ACCOUNT_NUMBERS          => 'required|array',
    ];

    protected function validateResendNotificationParams(array $input)
    {
        $payoutLink = $this->entity;

        $payoutLinkId = $payoutLink->getPublicId();

        $newEmail = $this->processEmail($input);

        $newPhone = $this->processPhoneNumber($input);

        $sendSms = boolval(array_pull($input, Entity::SEND_SMS, $payoutLink->getSendSms()));

        if (($sendSms === true) and
            (empty($newPhone) === true))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_SMS_NOTIFICATION_WITH_EMPTY_PHONE,
                                          null,
                                          [
                                              'send_sms'       => $sendSms,
                                              'contact_phone'  => $payoutLink->getContactPhoneNumber(),
                                              'payout_link_id' => $payoutLinkId
                                          ]);
        }

        $sendEmail = boolval(array_pull($input, Entity::SEND_EMAIL, $payoutLink->getSendEmail()));

        if (($sendEmail === true) and
            (empty($newEmail) === true))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_EMAIL_NOTIFICATION_WITH_EMPTY_EMAIL,
                                          null,
                                          [
                                              'send_email'     => $sendEmail,
                                              'contact_email'  => $payoutLink->getContactEmail(),
                                              'payout_link_id' => $payoutLinkId
                                          ]);
        }
    }

    protected function processPhoneNumber($input)
    {
        $payoutLink = $this->entity;

        $contactPhoneNumber = array_pull($input, Entity::CONTACT_PHONE_NUMBER);

        $existingContactPhoneNumber = $payoutLink->getContactPhoneNumber();

        if (empty($contactPhoneNumber))
        {
            return $existingContactPhoneNumber;
        }

        if ((empty($contactPhoneNumber) === false) and
            (empty($existingContactPhoneNumber) === false))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_REWRITING_PHONE_NUMBER_NOT_PERMITTED,
                                          null,
                                          [
                                              'payout_link_id' => $payoutLink->getPublicId(),
                                              'new_phone'      => $contactPhoneNumber,
                                              'old_phone'      => $existingContactPhoneNumber
                                          ]);
        }

        return $contactPhoneNumber;
    }

    protected function processEmail(array $input)
    {
        $payoutLink = $this->entity;

        $contactEmail = array_pull($input, Entity::CONTACT_EMAIL);

        $existingContactEmail = $payoutLink->getContactEmail();

        if (empty($contactEmail) === true)
        {
            return $existingContactEmail;
        }

        if ((empty($contactEmail) === false) and
            (empty($existingContactEmail) === false))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_REWRITING_EMAIL_NOT_PERMITTED,
                                          null,
                                          [
                                              'payout_link_id' => $payoutLink->getPublicId(),
                                              'new_email'      => $contactEmail,
                                              'old_email'      => $existingContactEmail
                                          ]);
        }

        return $contactEmail;
    }

    protected function validateNotificationSettings(array $input)
    {
        $sendEmail = boolval(array_pull($input, Entity::SEND_EMAIL, false));

        if (($sendEmail === true) and
            (empty($input[Entity::CONTACT_EMAIL]) === true))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_EMAIL_NOTIFICATION_WITH_EMPTY_EMAIL,
                                          null,
                                          $input
            );
        }

        $sendSms = boolval(array_pull($input, Entity::SEND_SMS, false));

        if (($sendSms === true) and
            (empty($input[Entity::CONTACT_PHONE_NUMBER]) === true))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_SMS_NOTIFICATION_WITH_EMPTY_PHONE,
                                          null,
                                          $input
            );
        }
    }

    /**
     * Check that if contact_id is present and along with it other information is present then fail the api
     * @param array $input
     * @throws BadRequestException
     */
    protected function validateContact(array $input)
    {
        if (empty($input[Entity::CONTACT][Entity::ID]) === true)
        {
            return;
        }

        if ((isset($input[Entity::CONTACT][Entity::EMAIL]) === true) or
            (isset($input[Entity::CONTACT][Entity::PHONE_NUMBER]) === true))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_EITHER_CONTACT_ID_OR_INFORMATION_TO_BE_SENT,
                                          null,
                                          $input);
        }
    }

    protected function validateGRecaptchaResponse($captchaKey, $captchaResponse)
    {
        /**
         * you have to call the g-api and check if this works or not...
         *
         */

        $app = App::getFacadeRoot();

        if ($app->environment('production') === false)
        {
            return;
        }

        $captchaSecret = config('app.signup.nocaptcha_secret');

        $input = [
            'secret'   => $captchaSecret,
            'response' => $captchaResponse,
        ];

        $url = self::GOOGLE_CAPTCHA_VERIFICATION_ENDPOINT;

        $response = Requests::request($url, [], $input, Requests::GET);

        $output = json_decode($response->body);

        if ($output->success !== true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_CAPTCHA_FAILED,
                null,
                [
                    'output_from_google'        => (array)$output
                ]
            );
        }

    }
}
