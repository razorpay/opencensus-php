<?php

namespace RZP\Models\User;

use DB;
use Mail;
use Hash;
use Cache;
use Config;
use RZP\Services\Dcs\Features\Constants as DcsConstants;
use RZP\Services\Dcs\Features\Type;
use Throwable;
use Carbon\Carbon;
use RZP\Exception;
use Lib\PhoneBook;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Models\Payout;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Table;
use RZP\Models\Admin\Org;
use Razorpay\Trace\Logger;
use RZP\Models\AuthzAdmin;
use RZP\Constants\Product;
use RZP\Constants\Timezone;
use RZP\Http\RequestHeader;
use RZP\Constants\Environment;
use RZP\Mail\User as UserMail;
use RZP\Services\TokenService;
use RZP\Models\Admin\ConfigKey;
use RZP\Services\HubspotClient;
use RZP\Models\Admin\AdminLead;
use RZP\Jobs\MailChimpSubscribe;
use RZP\Mail\User\Otp as OtpMail;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Admin\Admin\Token;
use Razorpay\Trace\Logger as Trace;
use RZP\Http\UserRolePermissionsMap;
use Illuminate\Hashing\BcryptHasher;
use RZP\Models\BankingAccountService;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use libphonenumber\NumberParseException;
use RZP\Models\Workflow\Service\Adapter;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Notifications\Onboarding\Events;
use RZP\Mail\User\OtpSignup as OtpSignup;
use RZP\Models\Batch\Entity as BatchEntity;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Models\Feature\Constants as FeatureConstant;
use RZP\Services\Segment\Constants as SegmentConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Escalations as MerchantEscalation;
use RZP\Models\Merchant\Balance\Ledger\Core as LedgerCore;
use RZP\Modules\SecondFactorAuth\Constants as AuthConstants;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\SubVirtualAccount\Constants as SubVaConstants;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;
use RZP\Models\Merchant\Credits\Balance\Entity as CreditEntity;
use RZP\Models\Merchant\Detail\Service as MerchantDetailService;
use RZP\Mail\User\ContactMobileUpdated as ContactMobileUpdatedMail;
use RZP\Models\OAuthApplication\Constants as OAuthApplicationConstants;
use RZP\Models\User\RateLimitLoginSignup\Facade as LoginSignupRateLimit;
use RZP\Mail\User\AccountLockedWrongAttempt as AccountLockedWrongAttemptMail;

class Core extends Base\Core
{
    const VERIFY_SUPPORT_CONTACT = 'verify_support_contact';

    /**
     * For `/user` API, we fetch the merchants user is part of
     * For some users, fetching too many merchants is causing timeout
     * Reducing this to 50 from 1000
     * https://razorpay.slack.com/archives/C7WEGELHJ/p1680530247334459
     */
    const USER_MERCHANTS_FETCH_COUNT = 1000;
    const USER_MERCHANTS_FETCH_COUNT_EXPERIMENT = 50;

    public static $actionToTemplateMapping = [
        'create_payout'                => 'Sms.User.Create_payout.V3',
        'sub_virtual_account_transfer' => 'Sms.User.Sub_virtual_account_transfer.V2',
        'create_payout_batch'          => 'Sms.User.Create_payout_batch.V1',
        'approve_payout'               => 'Sms.User.Approve_payout.V2',
        'approve_payout_bulk'          => 'Sms.User.Approve_payout_bulk.V1',
        // 'bulk_payout_approve'       => 'Sms.User.Bulk_payout_approve.V1',
    ];

    /**
     * @param array $input
     * @return array
     * @throws BadRequestException
     * @throws ServerErrorException
     */
    public function sendSignupOtpViaEmail(array $input): array
    {
        if ($this->checkIfEmailAlreadyExists($input[Entity::EMAIL]) === true) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_EMAIL_ALREADY_EXISTS,
                null,
                [
                    "internal_error_code" => ErrorCode::BAD_REQUEST_EMAIL_ALREADY_EXISTS
                ]
            );
        }

        LoginSignupRateLimit::validateKeyLimitExceeded(
            $input[Entity::EMAIL],
            Constants::SEND_EMAIL_SIGNUP_OTP_RATE_LIMIT_SUFFIX,
            Constants::EMAIL_SIGNUP_OTP_SEND_TTL,
            Constants::EMAIL_SIGNUP_OTP_SEND_THRESHOLD
        );

        $input = array_merge($input, $this->getLoginSignupOtpPayload($input, Constants::SIGNUP_OTP_ACTION));

        $receiver = $input[Entity::EMAIL];

        $otp = $this->generateOtpForLoginSignup($receiver, $input);

        $payload = $this->getEmailPayload($input, $otp);

        $mailable = new OtpSignup($payload, $otp);

        try
        {
            Mail::queue($mailable);
        }
        catch (\Throwable $e)
        {
            $this->trace->count(Metric::USER_EMAIL_OTP_SEND_FAILED);

            $this->trace->traceException(
                $e,
                null,
                TraceCode::USERS_SEND_EMAIL_OTP_FAILED,
                compact('input'));

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_EMAIL_OTP_FAILED,
                null,
                null,
                $e->getMessage()
            );
        }

        $this->traceEmailOtpLoginRoute($input, TraceCode::USER_SEND_EMAIL_OTP_FOR_REGISTER);

        return array_only($otp, 'token');
    }

    public function getOwnerMidsWithEmailOrMobileAndPan($input)
    {
        if (empty($input[DetailConstants::EMAIL]) === true)
        {
            $phoneNumber = $input[DetailConstants::PHONE];

            $phoneNumber = new PhoneBook($phoneNumber);

            $phoneNumber = $phoneNumber->format(PhoneBook::DOMESTIC);

            $user = $this->repo->user->getUserFromMobileOrFail($phoneNumber);
        }
        else
        {
            $user = $this->repo->user->getUserFromEmail($input[DetailConstants::EMAIL]);
        }

        if (empty($user) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_NOT_FOUND);
        }

        $ownerMerchantIds = $user->getIsOwnerMerchantIds();

        foreach ($ownerMerchantIds as $merchantId)
        {
            $details = $this->repo->merchant_detail->getByMerchantId($merchantId);

            if ($input[DetailConstants::PAN] == $details->getPan())
            {
                return $merchantId;
            }
        }

        return null;
    }

    /**
     * @param array $input
     * @return array|null
     * @throws BadRequestException
     * @throws NumberParseException
     */
    public function sendSignupOtpViaSms(array $input): ?array
    {
        if ($this->checkIfMobileAlreadyExists($input[Entity::CONTACT_MOBILE])) {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS,
                null,
                [
                    "internal_error_code" => ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS
                ]
            );
        }

        $input = array_merge($input, $this->getLoginSignupOtpPayload($input, Constants::SIGNUP_OTP_ACTION));

        $receiver = $input[Entity::CONTACT_MOBILE];

        $messageSendViaStork = false;

        if ($this->app['basicauth']->getRequestOriginProduct() === ProductType::PRIMARY and
            $input['action'] = Constants::SIGNUP_OTP_ACTION)
        {
            $messageSendViaStork = true;
            $input['action'] = Constants::SIGNUP_OTP_ACTION_V2; //It should come from frontend once experiment will be removed.
        }

        $otp = $this->generateOtpForLoginSignup($receiver, $input);

        try
        {
            if($messageSendViaStork === true)
            {
                $payload = $this->getStorkLoginSignupPayload($input, $otp);
                $stork = $this->app['stork_service'];

                $stork->sendSms($this->mode,$payload);
            }
            else {
                $payload = $this->getSmsPayload($input, $otp);
                $this->app->raven->sendOtp($payload);
            }
        }
        catch (\Throwable $e)
        {
            if (isset($input['contact_mobile']) === true)
            {
                $input['contact_mobile'] = mask_phone($input['contact_mobile']);
            }

            $this->trace->traceException(
                $e,
                null,
                TraceCode::USERS_SEND_SMS_OTP_FAILED,
                compact('input'));

            switch ($e->getCode())
            {
                case ErrorCode::BAD_REQUEST_RESOURCE_EXHAUSTED:
                case Constants::STORK_RESOURCE_EXHAUSTED_MESSAGE:
                case ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED:
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED,
                        null,
                        [
                            "internal_error_code" => ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED
                        ],
                        $e->getMessage()
                    );
                default:
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_SMS_OTP_FAILED,
                        null,
                        null,
                        $e->getMessage()
                    );
            }
        }

        return array_only($otp, 'token');
    }

    /**
     * Returns a token returned by raven service that will be used for verification
     * @param array $input
     * @return array
     * @throws BadRequestException
     * @throws ServerErrorException
     */
    public function registerWithOtp(array $input): ?array
    {
        $this->getUserEntity()->getValidator()->validateInput('signupOtp', $input);

        // if by any change both email and contact number are present, prefer email
        if (isset($input[Entity::EMAIL]))
        {
            return $this->sendSignupOtpViaEmail($input);
        }
        else
        {
            return $this->sendSignupOtpViaSms($input);
        }
    }

    public function sendOtpSalesforce(array $input): ?array
    {
        $this->getUserEntity()->getValidator()->validateInput('salesforceOtp', $input);

        $input = array_merge($input, $this->getLoginSignupOtpPayload($input, Constants::VERIFY_SALESFORCE_USER_ACTION));

        $receiver = $input[Entity::CONTACT_MOBILE];

        $otp = $this->generateOtpForLoginSignup($receiver, $input);

        try
        {
            $payload = $this->getStorkSalesforceSignupPayload($input, $otp);
            $stork = $this->app['stork_service'];
            $stork->sendSms($this->mode,$payload);
        }
        catch (\Throwable $e)
        {
            if (isset($input['contact_mobile']) === true)
            {
                $input['contact_mobile'] = mask_phone($input['contact_mobile']);
            }

            $this->trace->traceException(
                $e,
                null,
                TraceCode::USERS_SEND_SMS_OTP_FAILED,
                compact('input'));

            switch ($e->getCode())
            {
                case ErrorCode::BAD_REQUEST_RESOURCE_EXHAUSTED:
                case Constants::STORK_RESOURCE_EXHAUSTED_MESSAGE:
                case ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED:
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED,
                        null,
                        [
                            "internal_error_code" => ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED
                        ],
                        $e->getMessage()
                    );
                default:
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_SMS_OTP_FAILED,
                        null,
                        null,
                        $e->getMessage()
                    );
            }
        }

        return array_only($otp, 'token');
    }

    public function verifySalesforceOtp(array $input) : bool
    {
        if(!isset($input["otp"]) || !isset($input["token"]) || (!isset($input["contact_mobile"]) && !isset($input["Phone"])) ) {
            throw new BadRequestValidationFailureException('Phone, otp, token is/are required');
        }

        if(!isset($input[Entity::CONTACT_MOBILE]))
        {
            $input[Entity::CONTACT_MOBILE] = $input[Entity::PHONE];
        }

        $receiver = $input[Entity::CONTACT_MOBILE];

        $input = array_merge($input, $this->getLoginSignupOtpPayload($input, Constants::VERIFY_SALESFORCE_USER_ACTION));

        LoginSignupRateLimit::validateKeyLimitExceeded(
            $receiver,
            Constants::VERIFY_SIGNUP_OTP_RATE_LIMIT_SUFFIX,
            Constants::VERIFY_SIGNUP_OTP_TTL,
            Constants::SIGNUP_OTP_VERIFICATION_THRESHOLD
        );

        $this->verifyLoginSignupOtp($receiver, $input, $receiver);

        LoginSignupRateLimit::resetKey(
            $receiver, Constants::VERIFY_SIGNUP_OTP_RATE_LIMIT_SUFFIX
        );

        return true;
    }

    public function getStorkSalesforceSignupPayload(array $input, array $otp)
    {
        $receiver = $input[Entity::CONTACT_MOBILE];

        $orgId = $this->app['basicauth']->getOrgId();

        $ownerId = "1000000000";

        $payload = [
            'ownerId'               => $ownerId,
            'ownerType'             => 'merchant',
            'orgId'                 => $orgId,
            'destination'           => $receiver,
            'source'                => 'api.user.' . $input[Entity::ACTION],
            'templateName'          => 'sms.user.' . $input[Entity::ACTION],
            'templateNamespace'     => 'platform_acquisition',
            'sender'                => 'RZRPAY',
            'language'              => 'english',
            'contentParams'   => [
                'otp'      => $otp['otp'],
                'validity' => Carbon::createFromTimestamp($otp['expires_at'], Timezone::IST)->format('H:i:s'),
            ],
            'THROW_SMS_EXCEPTION_IN_STORK' => true,
        ];

        return $payload;
    }

    protected function traceEmailOtpSignupRoute(array $input, string $traceCode)
    {
        $keysToTrace = [Entity::EMAIL];

        $data = [];
        foreach ($keysToTrace as $key)
        {
            $data[$key] = $input[$key] ?? null;
        }

        $this->trace->info($traceCode, $data);
    }

    /**
     * @param array $input
     * @return bool
     * @throws BadRequestException
     * @throws NumberParseException
     * @throws ServerErrorException
     */
    public function verifySignupOtp(array $input): bool
    {
        $this->getUserEntity()->getValidator()->validateInput('verifySignupOtp', $input);

        if (isset($input[Entity::CONTACT_MOBILE]) === true)
        {
            // if a user associated with the input contact_mobile exists, raise an error
            $receiver = $input[Entity::CONTACT_MOBILE];

            if ($this->checkIfMobileAlreadyExists($receiver)) {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS,
                    null,
                    [
                        "internal_error_code" => ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS
                    ]
                );
            }
            $signupMedium = Constants::CONTACT_MOBILE;
        }
        else
        {
            // if a user associated with the input email exists, raise an error
            $receiver = $input[Entity::EMAIL];

            if ($this->checkIfEmailAlreadyExists($receiver)) {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_EMAIL_ALREADY_EXISTS,
                    null,
                    [
                        "internal_error_code" => ErrorCode::BAD_REQUEST_EMAIL_ALREADY_EXISTS
                    ]
                );
            }
            $signupMedium = Constants::EMAIL;
        }

        $input = array_merge($input, $this->getLoginSignupOtpPayload($input, Constants::SIGNUP_OTP_ACTION));

        LoginSignupRateLimit::validateKeyLimitExceeded(
            $receiver,
            Constants::VERIFY_SIGNUP_OTP_RATE_LIMIT_SUFFIX,
            Constants::VERIFY_SIGNUP_OTP_TTL,
            Constants::SIGNUP_OTP_VERIFICATION_THRESHOLD
        );

        if($input[Entity::MEDIUM] === Org\Constants::SMS and
            $this->app['basicauth']->getRequestOriginProduct() === ProductType::PRIMARY and
            $input[Entity::ACTION] == Constants::SIGNUP_OTP_ACTION)
        {
            //It should come from frontend once experiment will be removed.
            $input[Entity::ACTION] = Constants::SIGNUP_OTP_ACTION_V2;
        }

        $this->verifyLoginSignupOtp($receiver, $input, $receiver);

        if($signupMedium === Constants::EMAIL)
        {
            LoginSignupRateLimit::resetKey(
                $receiver,
                Constants::SEND_EMAIL_SIGNUP_OTP_RATE_LIMIT_SUFFIX
            );
        }

        LoginSignupRateLimit::resetKey(
            $receiver, Constants::VERIFY_SIGNUP_OTP_RATE_LIMIT_SUFFIX
        );

        $this->traceEmailOtpSignupRoute($input, TraceCode::USER_VERIFY_EMAIL_OTP_FOR_REGISTER);

        return true;
    }

    public function create(array $input, string $operation = 'create', bool $isLinkedAccountUser = false): Entity
    {
        if($isLinkedAccountUser === false)
        {
            $this->validateAccountCreation($input);
        }

        unset($input[MerchantEntity::SIGNUP_SOURCE]);

        unset($input[MerchantEntity::COUNTRY_CODE]);

        unset($input['invitation']);

        unset($input['token_data']);

        $user = $this->getUserEntity()->build($input, $operation);

        $this->repo->transactionOnLiveAndTest(function() use ($user, $input)
        {
            $this->upsertSettings($user, $input[Entity::SETTINGS] ?? []);
            $this->repo->saveOrFail($user);
        });

        return $user;
    }

    //block pg merchants signup
    public function validateAccountCreation(array $input)
    {
        $merchantToken = $input[AdminLead\Constants::TOKEN_DATA];

        if ($merchantToken !== null)
        {
            $merchantType = $merchantToken[AdminLead\Constants::FORM_DATA][AdminLead\Constants::MERCHANT_TYPE] ?? null;

            if (empty($merchantType) === false and
                array_key_exists($merchantType, AdminLead\Constants::ALLOWED_MERCHANT_TYPE_FEATURE_MAPPING) === true)
            {
                return;
            }
        }

        //$signupSource is being used for capital cards and x submerchants account creation
        // as the RequestOriginProduct is primary for these two flows
        $signupSource = $input[MerchantEntity::SIGNUP_SOURCE] ?? null;

        $countryCode = $input[MerchantEntity::COUNTRY_CODE] ?? null;

        $invitation = $input['invitation'] ?? null;

        if (empty($invitation) === false)
        {
            $this->trace->info(TraceCode::SIGNUP_VALIDATIONS, ["invitation" => $invitation]);

            return;
        }

        if ($signupSource === Product::BANKING)
        {
            return;
        }

        if ($this->app['basicauth']->getRequestOriginProduct() === Product::BANKING)
        {
            return;
        }

        if ($countryCode === 'MY')
        {
            return;
        }

        $origin = $this->app['request']->header(RequestHeader::X_REQUEST_ORIGIN) ?? "";

        $rizeOriginHost = parse_url("https://rize-dashboard.razorpay.com", PHP_URL_HOST);

        $requestOriginHost = parse_url($origin, PHP_URL_HOST);

        $this->trace->info(TraceCode::SIGNUP_VALIDATIONS, [
            "rizeOriginHost" => $rizeOriginHost,
            "origin"         => $origin]);

        if ($requestOriginHost === $rizeOriginHost)
        {
            return;
        }

        $isProductionEnvironment = (new Merchant\Detail\Core())->isProductionEnvironment();

        if ($isProductionEnvironment === false)
        {
            return;
        }

        if ($this->isSignupEnabled() === true)
        {
            return;
        }

        throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ACTION);
    }

    public function isSignupEnabled()
    {
        $properties = [
            'id'            => substr(uniqid(), -14),
            'experiment_id' => $this->app['config']->get('app.enable_signups'),
        ];

        return (new Merchant\Core())->isSplitzExperimentEnable($properties, 'variables');
    }

    public function edit(Entity $user, array $input, $operation = 'edit')
    {
        $user->edit($input, $operation);

        try
        {
            $this->fireHubspotIfUserClickedNotNow($user, $input);
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::NEOSTONE_HUBSPOT_REQUEST_FAILED,
                [
                    $e->getMessage()
                ]);
        }

        $this->repo->transactionOnLiveAndTest(function() use ($user, $input)
        {
            $this->upsertSettings($user, $input[Entity::SETTINGS] ?? []);
            $this->repo->saveOrFail($user);
        });

        if ($operation === 'edit')
        {
            $this->trace->info(
                TraceCode::USER_EDIT,
                [
                    'user_id' => $user->getId(),
                    'input'   => $input
                ]);
        }

        return $user;
    }

    public function getUserFromEmail(array $input)
    {
        $user = $this->repo->user->getUserFromEmail($input[Entity::EMAIL]);

        return $user;
    }

    public function confirm(Entity $user, string $verificationType = null)
    {
        $user->setConfirmTokenNull();

        $this->repo->saveOrFail($user);

        $mid = $user->getMerchantId();

        $partnerIntent = false;

        if ($mid !== null and $this->merchant !== null)
        {
            $partnerIntentResponse = (new Merchant\Service())->fetchPartnerIntent();

            $partnerIntent = $partnerIntentResponse[Merchant\Constants::PARTNER_INTENT] ?? false;
        }

        $customProperties = [Entity::EMAIL                      => $user->getEmail(),
                             Entity::MERCHANT_ID                => $mid,
                             Entity::VERIFICATION_TYPE          => $verificationType ?? Entity::LINK,
                             Merchant\Constants::PARTNER_INTENT => $partnerIntent];

        $this->app['diag']->trackOnboardingEvent(EventCode::SIGNUP_EMAIL_VERIFICATION_SUCCESS, $this->merchant, null, $customProperties);

        $hubSpotProperties = [Entity::EMAIL => $user->getEmail(), Entity::VERIFICATION_TYPE => $verificationType ?? Entity::LINK];

        $this->app->hubspot->trackConfirmEmailEvent($hubSpotProperties);

        return $user;
    }

    /**
     * @param Entity $user
     * @param array $input
     * @return Entity
     * @throws BadRequestException
     */
    protected function setOldPasswords(Entity $user, array $input): Entity
    {
        $oldPasswords   = $user->getAttribute(Entity::OLD_PASSWORDS);
        $newPassword    = $input[Entity::PASSWORD];

        /**
         * this bit is for active data-migration from
         * separate old_password# columns to old_passwords column.
         */
        if(is_null($oldPasswords) === true)
        {
            $oldPasswords = array_filter(
                [
                    $user->getAttribute(Entity::PASSWORD),
                    $user->getAttribute(Entity::OLD_PASSWORD_1),
                    $user->getAttribute(Entity::OLD_PASSWORD_2)
                ]
            );
        }

        $this->validateNewPasswordIsNotSameAsLastNPasswords($newPassword, $oldPasswords);

        if (count($oldPasswords) >= Constants::MAX_PASSWORD_TO_RETAIN)
        {
            array_shift($oldPasswords);
        }

        $user->fill($input);

        $oldPasswords[] = $user->getPassword();
        $user->setAttribute(Entity::OLD_PASSWORDS, $oldPasswords);

        return $user;
    }

    /**
     * TODO: Following validation does not seem be invoked in other flows
     * e.g. reset by internal admin etc. Need to check in detail and plug
     * this validation at right place. Also need to modify test assertions
     * and add new if required.
     * @param $newPassword string password that user has entered
     * @param array|null $oldPasswords array list of hashes of old passwords
     * @throws BadRequestException BAD_REQUEST_NEW_PASSWORD_SAME_AS_OLD_PASSWORD
     */
    protected function validateNewPasswordIsNotSameAsLastNPasswords(string $newPassword, ?array $oldPasswords)
    {
        foreach ($oldPasswords as $oldPassword)
        {
            if (Hash::check($newPassword, $oldPassword) === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_NEW_PASSWORD_SAME_AS_OLD_PASSWORD);
            }
        }
    }

    /**
     * @param Entity $user
     * @param array $input
     * @return Entity
     * @throws BadRequestException
     */
    public function changePassword(Entity $user, array $input): Entity
    {
        $user = $this->setOldPasswords($user, $input);

        $user->setPasswordResetToken();

        $this->repo->saveOrFail($user);

        $merchant = $user->getMerchantEntity();

        if (empty($merchant) === false)
        {
            [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

            $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Password Updated';

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $segmentProperties, $segmentEventName
            );
        }

        $orgId = $this->app['basicauth']->getOrgId();

        //get Org and send it to mailer, deal with other orgs as well.
        $org = $this->repo->org->findByPublicId($orgId)->toArrayPublic();

        $org['hostname'] = $this->app['basicauth']->getOrgHostName();

        $requestOriginProduct = $this->app['basicauth']->getRequestOriginProduct();

        $changedAt = Carbon::now(Timezone::IST)->format('g:ia \o\n l jS F Y');

        $passwordChangedMail = new UserMail\PasswordChange($user, $org, $changedAt, $requestOriginProduct);

        Mail::queue($passwordChangedMail);

        return $user;
    }


    public function savePasswordResetTokenAndExpiry(Entity $user, string $token, int $expiry)
    {
        $user->setPasswordResetToken($token);

        $user->setPasswordResetExpiry($expiry);

        $this->repo->saveOrFail($user);

        return $user;
    }

    /**
     * In case of null saves the new value, in case of non existing oauth provider
     * update with new the oauth provider
     * @param Entity $user
     * @param string $newOauthProvider
     *
     * @return Entity
     */
    public function saveOauthProvider(Entity $user, string $newOauthProvider)
    {
        $decodedNewOauthProvider = json_decode($newOauthProvider);

        $currentOauthProvider = $user->getOauthProvider();

        if ($currentOauthProvider !== null)
        {
            $decodedCurrentOauthProvider = json_decode($currentOauthProvider);

            if (in_array($decodedNewOauthProvider[0], $decodedCurrentOauthProvider) === false)
            {
                array_push($decodedCurrentOauthProvider, $decodedNewOauthProvider[0]);

                $this->encodeOauthProviderAndSave($user, $decodedCurrentOauthProvider);
            }
        }
        else
        {
            $decodedCurrentOauthProvider = $decodedNewOauthProvider;

            $this->encodeOauthProviderAndSave($user, $decodedCurrentOauthProvider);
        }

        return $user;
    }

    private function encodeOauthProviderAndSave(Entity $user, array $decodedCurrentOauthProvider)
    {
        $currentOauthProvider = json_encode($decodedCurrentOauthProvider);

        $user->setOauthProvider($currentOauthProvider);

        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        // log user with timestamp and currentOauth Provider.
        $this->trace->info(TraceCode::USER_OAUTH_PROVIDER_LOGIN_SAVED,
                           ['user_id'          => $user->getId(),
                            'email'            => $user->getEmail(),
                            'currentTimestamp' => $currentTimestamp,
                            'oauth_provider'   => $decodedCurrentOauthProvider]);

        $this->repo->saveOrFail($user);
    }

    public function updateUserMerchantMapping(Entity $user, array $input)
    {
        $user->getValidator()->validateInput('action', $input);

        $function = $input[Entity::ACTION];

        $this->$function($user, $input);

        return $user;
    }

    public function resendOtp(Entity $user)
    {
        $this->checkUserAccountNotLockedOrThrowException($user);

        $medium = $this->get2FaAuthMode();

        $action = ($medium === Org\Constants::SMS and
                   $this->app['basicauth']->getRequestOriginProduct() === ProductType::BANKING and
                   $this->app['razorx']->getTreatment($this->app['request']->getTaskId(), Constants::API_STORK_RX_SEND_SMS_RAZORX_EXP , Mode::LIVE) === 'on')
                  ? Constants::X_SECOND_FACTOR_AUTH_ACTION
                  : Entity::SECOND_FACTOR_AUTH;

        $input = [
            Entity::MEDIUM => $medium,
            Entity::ACTION => $action,
            Entity::TOKEN => $user->getId()
        ];

        $this->sendOtp($input, null, $user);
    }

    private function checkUserAccountNotLockedOrThrowException(Entity $user)
    {
        if ($user->isAccountLocked() === true)
        {
            $this->trace->info(TraceCode::LOCKED_USER_LOGIN, ['user_id' => $user->getId()]);

            $this->trace->count(Metric::LOCKED_USER_LOGIN);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_LOCKED_USER_LOGIN,
                    null,
                    [
                    'internal_error_code'  => ErrorCode::BAD_REQUEST_LOCKED_USER_LOGIN,
                    'user_details'         => [
                        'restricted'     => $user->restricted,
                        'account_locked' => true,
                        'is_owner'       => $user->isOwner()
                        ],
                    ]);
        }
    }

    private function check2faSetupDoneOrThrowException(Entity $user)
    {
        if ($user->isSecondFactorAuthSetup() === false)
        {
            $this->trace->info(TraceCode::USER_2FA_NOT_SETUP, ['user_id'=> $user->getId()]);

            $this->trace->count(Metric::USER_2FA_NOT_SETUP);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_USER_2FA_SETUP_REQUIRED,
                null,
                [
                    'internal_error_code'    => ErrorCode::BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED,
                    'user_details'           => [
                        'restricted'                => $user->getRestricted(),
                        'user_id'                   => $user->getId(),
                        'contact_mobile'            => $user->getContactMobile(),
                        'account_locked'            => $user->isAccountLocked(),
                        'is_password_set'           => empty($user->getPassword()) === false,
                        'is_mobile_verified'        => $user->isContactMobileVerified()
                    ],
                ]);

        }
    }

    protected function restrictUserToOneRolePerMerchantAndProduct(Entity $user): bool
    {
        $oneRoleExp = $this->app->razorx->getTreatment(
            $user->getId(),
            Merchant\RazorxTreatment::RESTRICT_USER_TO_ONE_ROLE_PER_MERCHANT_AND_PRODUCT,
            $this->mode
        );

        if (strtolower($oneRoleExp) !== 'off')
        {
            return true;
        }

        return false;
    }

    /**
     * Method to lock/unlock a user account
     *
     * @param Entity $user
     * @param string $action
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function accountLockUnlock(Entity $user, string $action): array
    {
        $isAdminAuth = app('basicauth')->isAdminAuth();

        $traceInfo = [
            Entity::USER_ID => $user->getId(),
            Entity::ACTION  => $action,
            'admin_auth'    => $isAdminAuth,
        ];

        if ($isAdminAuth === false)
        {
            // merchant is not allowed to lock the user account.

            if ($action === Constants::LOCK)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED);
            }

            $merchant = app('basicauth')->getMerchant();

            $this->canMerchantUpdateUserDetails($merchant, $user);

            $dashboardUser = app('basicauth')->getUser();

            if ((empty($dashboardUser) === true) or ($user->getId() === $dashboardUser->getId()))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACTION_NOT_ALLOWED_FOR_SELF_USER);
            }

            $traceInfo[Entity::MERCHANT_ID] = $merchant->getId();
        }
        else
        {
            $traceInfo[Token\Entity::ADMIN_ID] = app('basicauth')->getAdmin()->getId();
        }

        $this->trace->info(TraceCode::USER_ACCOUNT_LOCK_UNLOCK_ACTION, $traceInfo);

        switch ($action)
        {
            case Constants::LOCK:

                $user->setAccountLocked(true);

                break;

            case Constants::UNLOCK:

                $user->setWrong2faAttempts(0);

                $user->setAccountLocked(false);

                break;

            case Constants::UN_VERIFY:

                $user->setContactMobileVerified(false);

                $user->setWrong2faAttempts(0);

                break;
        }

        $this->repo->saveOrFail($user);

        return [
            Entity::ACCOUNT_LOCKED => $user->isAccountLocked(),
            Entity::USER_ID        => $user->getId(),
        ];
    }

    public function verifyOauthIdToken(array &$input)
    {
        (new Validator)->validateOauthRequest($input);

        $oauthProvider = json_decode($input[Entity::OAUTH_PROVIDER], true);
        $oauthProvider = $oauthProvider[0];

        // for now we have only one provider and this is validated above
        switch ($oauthProvider)
        {
            case OauthProvider::GOOGLE:
                $verified = (new GoogleOauthVerify)->verifyGoogleOauthIdToken($input);
                if ($verified === false)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID_TOKEN);
                }
        }

        unset($input[Constants::OAUTH_SOURCE]);
        unset($input[Constants::ID_TOKEN]);
    }

    protected function traceLoginRoute(array $input)
    {
        $keysToTrace = [Entity::EMAIL, Entity::APP, Entity::OAUTH_PROVIDER, Constants::OAUTH_SOURCE, MerchantDetailEntity::REFERRAL_CODE];

        $data = [];
        foreach ($keysToTrace as $key)
        {
            $data[$key] = $input[$key] ?? null;
        }

        $this->trace->info(TraceCode::USER_LOGIN, $data);
    }

    protected function traceEmailOtpLoginRoute(array $input, string $traceCode = TraceCode::USER_LOGIN)
    {
        $keysToTrace = [Entity::EMAIL];

        $data = [];
        foreach ($keysToTrace as $key)
        {
            $data[$key] = $input[$key] ?? null;
        }

        $this->trace->info($traceCode, $data);
    }

    protected function traceMobileLoginRoute(array $input, string $traceCode = TraceCode::USER_MOBILE_LOGIN)
    {
        $keysToTrace = [Entity::CONTACT_MOBILE, MerchantDetailEntity::REFERRAL_CODE];

        $data = [];
        foreach ($keysToTrace as $key)
        {
            $data[$key] = $input[$key] ?? null;
        }

        $this->trace->info($traceCode, $data);
    }

    public function delIncorrectPasswordCount(string $merchantEmail)
    {
        try
        {
            $redis = $this->app->redis->Connection('mutex_redis');

            return $redis->del($merchantEmail);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::USER_INCORRECT_PASSWORD_REDIS_ERROR,
                ['key' => $merchantEmail]);

            return 0;
        }
    }

    public function trackIncorrectPasswordCount(array $input): bool
    {
        return $this->getUserEntity()->getValidator()->isCaptchaDisabled($input);
    }

    public function loginWithMobilePassword(array $input)
    {
        if ($this->isEnvironmentProduction() === true)
        {
            return null;
        }

        if (isset($input[Entity::CONTACT_MOBILE]) === false)
        {
            return null;
        }

        $this->getUserEntity()->getValidator()->validateInput('loginMobile', $input);

        $user = $this->repo->user->getUserFromMobileOrFail($input[Entity::CONTACT_MOBILE]);

        $this->verifyPassword($user, $input[Entity::PASSWORD]);

        $this->checkUserAccountNotLockedOrThrowException($user);

        $maskedInput[Entity::CONTACT_MOBILE] = $user->getMaskedContactMobile();

        $this->traceMobileLoginRoute($maskedInput);

        (new Core)->trackOnboardingEvent($user->getContactMobile(),
            EventCode::LOGIN_SUCCESS_WITH_MOBILE);

        $this->trace->count(
            Metric::USER_LOGIN_COUNT,
            [
                Constants::METHOD => Constants::PASSWORD,
                Constants::MEDIUM => Constants::CONTACT_MOBILE,
            ]
        );

        return $this->get($user, true);
    }

    public function login(array $input)
    {
        $browserDetails = $input[Constants::BROWSER_DETAILS] ?? null;

        unset($input[Constants::BROWSER_DETAILS]);

        $user = $this->loginWithMobilePassword($input);

        if ($user !== null)
        {
            return $user;
        }

        $this->getUserEntity()->getValidator()->validateInput('login', $input);

        $this->traceLoginRoute($input);

        $user = $this->getUserByEmailAndVerifyPassword($input[Entity::EMAIL], $input[Entity::PASSWORD]);

        $incorrectPasswordCountTrack = $this->trackIncorrectPasswordCount($input);

        // if login successful, delete incorrect password counter.
        if ($incorrectPasswordCountTrack === true) {
            $this->delIncorrectPasswordCount($input[Entity::EMAIL]);
        }

        $this->checkUserAccountNotLockedOrThrowException($user);

        $this->applyReferralIfApplicable($input, $user);

        $this->checkSecondFactorAuthAndSendOtp($user);

        (new Core)->trackOnboardingEvent($user->getEmail(),
            EventCode::MERCHANT_ONBOARDING_LOGIN_SUCCESS);

        $this->trace->count(
            Metric::USER_LOGIN_COUNT,
            [
                Constants::METHOD => Constants::PASSWORD,
                Constants::MEDIUM => Constants::EMAIL,
            ]
        );

        $loginMailNotificationEnabled = (new Merchant\Core())->isRazorxExperimentEnable($user->getId(),
            RazorxTreatment::USER_LOGIN_EMAIL_NOTIFICATION);

        // inform the user about the login activity (only if the email is verified and Razorx exp is enabled)
        if (($user->getConfirmedAttribute() === true) and ($loginMailNotificationEnabled === true))
        {
            $this->sendLoginMailToUser($user, $browserDetails);
        }

        $orgId = $this->app['basicauth']->getOrgId();

        if(empty($orgId) === true)
        {
            $orgId = Org\Entity::RAZORPAY_ORG_ID;
        }

        if($orgId === Org\Entity::BAJAJ_ORG_SIGNED_ID)
        {
            $merchant = $this->findMerchant($user[Entity::ID]);
        }
        else
        {
            $merchant = $this->findMerchantForOrg($user[Entity::ID], $orgId);
        }

        //  $this->mode === 'test', just a hack need to write proper test case after setting product as Banking in requests origin

        if(($merchant !== null) and (($this->app['basicauth']->getRequestOriginProduct() === ProductType::BANKING) or $this->mode === 'test')) {

            $this->app['x-segment']->sendEventToSegment(SegmentEvent::USER_LOGIN, $merchant);
        }

        return $this->get($user, true);
    }

    private function applyReferralIfApplicable(array $input, Entity $user)
    {
        if((isset($input[MerchantDetailEntity::REFERRAL_CODE]) === true) and (empty($input[MerchantDetailEntity::REFERRAL_CODE]) === false))
        {
            $merchant = $user->merchants()->first();

            if(empty($merchant) === false)
            {
                try
                {
                    (new MerchantDetailService())->applyReferralIfApplicable($input[MerchantDetailEntity::REFERRAL_CODE], $merchant);
                }
                catch (Throwable $ex)
                {
                    app('trace')->error(
                        TraceCode::BAD_REQUEST_COULD_NOT_APPLY_REFERRAL_CODE_FOR_CAPITAL_SUBMERCHANTS,
                        [
                            'exception'   => $ex,
                            'description' => 'Capital referral code cannot be applied during SignIn',
                        ]
                    );
                }
            }
        }
    }

    public function findMerchant($userId){

        try {
            $user = $this->repo->user->findOrFailPublic($userId);

            $merchant = $user->getFirstMerchantEntity();

            return $merchant;
        }
        catch (\Throwable $ex ){

            $this->trace->info(TraceCode::MERCHANT_FETCH_FAILED,
                [
                    'user_id' => $userId
                ]);
            return null;
        }

    }

    public function findMerchantForOrg($userId, $orgId)
    {
        try {
            $user = $this->repo->user->findOrFailPublic($userId);

            $merchant = $user->getFirstMerchantEntityForOrg($orgId);

            return $merchant;
        }
        catch (\Throwable $ex )
        {

            $this->trace->info(TraceCode::MERCHANT_FETCH_FAILED,
                [
                    'user_id' => $userId
                ]);
            return null;
        }

    }

    /**
     * @param Entity $user
     * @param array|null $browserDetails
     * @return void
     */
    private function sendLoginMailToUser(Entity $user, ?array $browserDetails)
    {
        $orgId = $this->app['basicauth']->getOrgId();

        $orgId =  Org\Entity::verifyIdAndStripSign($orgId);

        $orgHostname = $this->app['basicauth']->getOrgHostName();

        $loginAt = Carbon::now('UTC')->isoFormat('lll');

        // send login notification for Razorpay org only
        if ($orgId === Org\Entity::RAZORPAY_ORG_ID)
        {
            $this->trace->info(TraceCode::SEND_USER_LOGIN_EMAIL_ATTEMPT, [Entity::USER_ID => $user->getId()]);

            $loginMail = new UserMail\Login($user, $orgHostname, $browserDetails, $loginAt);

            Mail::queue($loginMail);
        }
    }

    public function getLoginSignupOtpPayload(array $input, string $action)
    {
        $medium = 'email';

        if (isset($input[Entity::CONTACT_MOBILE]) === true)
        {
            $medium = 'sms';
        }

        return [
            Entity::ACTION => $action,
            Entity::MEDIUM => $medium
        ];
    }

    public function getToken($userId, array $input)
    {
        $token = $input['token'] ?? Entity::generateUniqueId();

        $context = sprintf('%s:%s:%s', $userId, $input[Entity::ACTION], $token);

        $source = "api.user.{$input['action']}";

        if (isset($input[Entity::CONTACT_MOBILE]) === true)
        {
            $receiver = $input[Entity::CONTACT_MOBILE];
        }
        else
        {
            $receiver = $input[Entity::EMAIL];
        }

        return compact(
            'token',
            'receiver',
            'context',
            'source');
    }

    public function generateOtpForLoginSignup(string $userId, array $input)
    {
        $payload = $this->getToken($userId, $input);

        $token = array_pull($payload, 'token');

        $otp = $this->app->raven->generateOtp($payload);

        return $otp + array_only($payload, 'context') + compact('token');
    }

    public function getStorkLoginSignupPayload(array $input, array $otp, Entity $user = null)
    {
        $receiver = $input[Entity::CONTACT_MOBILE];

        $origin_value = getOrigin();

        $orgId = $this->app['basicauth']->getOrgId();

        $ownerId = "1000000000";

        if (is_null($user) === false)
        {
            $ownerId =$user->getId();
        }

        $payload = [
            'ownerId'               => $ownerId,
            'ownerType'             => 'merchant',
            'orgId'                 => $orgId,
            'destination'           => $receiver,
            'source'                => 'api.user.' . $input[Entity::ACTION],
            'templateName'          => 'sms.user.' . $input[Entity::ACTION],
            'templateNamespace'     => 'partnerships',
            'sender'                => 'RZRPAY',
            'language'              => 'english',
            'contentParams'   => [
                'otp'      => $otp['otp'],
                'validity' => Carbon::createFromTimestamp($otp['expires_at'], Timezone::IST)->format('H:i:s'),
                'origin'   => $origin_value,
            ],
            'THROW_SMS_EXCEPTION_IN_STORK' => true,
        ];

        if($this->app['razorx']->getTreatment($receiver , Constants::UPDATE_LOGIN_SIGNUP_TEMPLATE_RAZORX_EXP , Mode::LIVE) === 'on')
        {
            $autoReadOtpText = $origin_value.' #'.$otp['otp'];
            if(strlen($autoReadOtpText) > 30)
            {
                $autoReadOtpText = "";
            }

            $payload = [
                'ownerId'               => $ownerId,
                'ownerType'             => 'merchant',
                'orgId'                 => $orgId,
                'destination'           => $receiver,
                'source'                => 'api.user.' . $input[Entity::ACTION],
                'templateName'          => 'sms.user.' . $input[Entity::ACTION],
                'templateNamespace'     => 'platform_acquisition',
                'sender'                => 'RZRPAY',
                'language'              => 'english',
                'contentParams'   => [
                    'otp'      => $otp['otp'],
                    'autoread_text'   => $autoReadOtpText,
                ],
                'THROW_SMS_EXCEPTION_IN_STORK' => true,
            ];
        }

        return $payload;
    }

    public function getSmsPayload(array $input, array $otp)
    {
        $receiver = $input[Entity::CONTACT_MOBILE];

        $payload = [
            'receiver' => $receiver,
            'source'   => "api.user.{$input['action']}",
            'template' => 'sms.user.' . $input[Entity::ACTION],
            'params'   => [
                'otp'      => $otp['otp'],
                'validity' => Carbon::createFromTimestamp($otp['expires_at'], Timezone::IST)->format('H:i:s'),
            ],
            'context' => $otp['context'],
        ];

        return $payload;
    }

    public function getEmailPayload(array $input, array $otp)
    {

        $receiver = $input[Entity::EMAIL];

        $payload = [
            'receiver' => $receiver,
            'source'   => "api.user.{$input['action']}",
            'template' => 'sms.user.' . $input[Entity::ACTION],
            'params'   => [
                'otp'      => $otp['otp'],
                'validity' => Carbon::createFromTimestamp($otp['expires_at'], Timezone::IST)->format('H:i:s'),
            ],
        ];

        return array_merge($payload, $input);
    }

    /**
     * @param Entity $user
     * @throws BadRequestException
     */
    protected function checkIfOtpLoginLocked(Entity $user)
    {
        if ($user->isAccountLocked() === true)
        {
            $this->trace->info(TraceCode::USER_OTP_LOGIN_LOCKED, ['user_id' => $user->getId()]);
//            $this->trace->count(Metric::USER_2FA_LOCKED);
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_OTP_LOGIN_LOCKED,
                null,
                [
                    'internal_error_code'  => ErrorCode::BAD_REQUEST_OTP_LOGIN_LOCKED,
                    'user_details'         => [
                        'restricted'     => $user->restricted,
                        'account_locked' => true,
                        'is_owner'       => $user->isOwner()
                    ],
                ]);
        }
    }

    /**
     * @param array $input
     * @param Entity $user
     * @return array
     * @throws BadRequestException
     */
    public function sendLoginOtpViaSms(array $input, Entity $user): array
    {
        $this->checkIfOtpLoginLocked($user);

        $input = array_merge($input, $this->getLoginSignupOtpPayload($input, Constants::LOGIN_OTP_ACTION));

        $sendViaHelperMethod = false;

        if ($this->app['basicauth']->getRequestOriginProduct() === ProductType::BANKING and
            $this->app['razorx']->getTreatment($this->app['request']->getTaskId(), Constants::API_STORK_RX_SEND_SMS_RAZORX_EXP , Mode::LIVE) === 'on')
        {
            $input[Entity::ACTION] = Constants::X_LOGIN_OTP_ACTION;

            $sendViaHelperMethod = true;
        }

        $messageSendViaStork = false;

        if ($this->app['basicauth']->getRequestOriginProduct() === ProductType::PRIMARY and
            $input[Entity::ACTION] === Constants::LOGIN_OTP_ACTION)
        {
            $input[Entity::ACTION] = Constants::LOGIN_OTP_ACTION_V2;
            $messageSendViaStork = true;
        }

        $otp = $this->generateOtpForLoginSignup($user->getId(), $input);

        if (Environment::isLowerEnvironment($this->app['env']) === true)
        {
            if (isset($input[Entity::SKIP_SMS_REQUEST]) === true and
                $input[Entity::SKIP_SMS_REQUEST] === true)
            {
                return array_only($otp, 'token');
            }
        }

        // Raven payload
        $payload = $this->getSmsPayload($input, $otp);

        try
        {
            if ($sendViaHelperMethod === true)
            {
                $token = $this->sendOtpViaSms($input,null,$user,$otp);
            }
            else if($messageSendViaStork === true)
            {
                $payload = $this->getStorkLoginSignupPayload($input, $otp, $user);

                $stork = $this->app['stork_service'];

                $stork->sendSms($this->mode,$payload);
            }
            else
            {
                $this->app->raven->sendOtp($payload);
            }

        }
        catch (\Throwable $e)
        {
            $this->trace->count(Metric::USER_SMS_OTP_SEND_FAILED);

            if (isset($input['contact_mobile']) === true)
            {
                $input['contact_mobile'] = mask_phone($input['contact_mobile']);
            }

            $this->trace->traceException(
                $e,
                null,
                TraceCode::USERS_SEND_SMS_OTP_FAILED,
                compact('input')
            );

            if ($sendViaHelperMethod === true)
            {
                switch ($e->getMessage())
                {
                    case Constants::STORK_RESOURCE_EXHAUSTED_MESSAGE:
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED,
                            null,
                            [
                                "internal_error_code" => ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED
                            ],
                            $e->getMessage()
                        );
                    default:
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_SMS_OTP_FAILED,
                            null,
                            null,
                            $e->getMessage()
                        );
                }
            }
            else
            {
                switch ($e->getCode())
                {
                    case ErrorCode::BAD_REQUEST_RESOURCE_EXHAUSTED:
                    case Constants::STORK_RESOURCE_EXHAUSTED_MESSAGE:
                    case ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED:
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED,
                            null,
                            [
                                "internal_error_code" => ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED
                            ],
                            $e->getMessage()
                        );
                    default:
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_SMS_OTP_FAILED,
                            null,
                            null,
                            $e->getMessage()
                        );
                }
            }

        }

        $maskedInput[Entity::CONTACT_MOBILE] = $user->getMaskedContactMobile();

        $this->trace->count(Metric::USER_SMS_OTP_SENT);

        $this->traceMobileLoginRoute($maskedInput, TraceCode::USER_SEND_SMS_OTP_FOR_LOGIN);

        if ($sendViaHelperMethod === true)
        {
            return $token;
        }

        return array_only($otp, 'token');
    }

    /**
     * Send Login OTP to user Email
     * @param array $input
     * @param Entity $user
     * @return array
     * @throws BadRequestException
     * @throws Exception\ServerErrorException
     */
    public function sendLoginOtpViaEmail(array $input, Entity $user): array
    {
        $this->checkIfOtpLoginLocked($user);

        LoginSignupRateLimit::validateKeyLimitExceeded(
            $input[Entity::EMAIL],
            Constants::SEND_EMAIL_LOGIN_OTP_RATE_LIMIT_SUFFIX,
            Constants::EMAIL_LOGIN_OTP_SEND_TTL,
            Constants::EMAIL_LOGIN_OTP_SEND_THRESHOLD
        );

        $input = array_merge($input, $this->getLoginSignupOtpPayload($input, Constants::LOGIN_OTP_ACTION));

        $otp = $this->generateOtpForLoginSignup($user->getId(), $input);

        $payload = $this->getEmailPayload($input, $otp);

        $mailable = new OtpMail($payload, $user, $otp);

        try
        {
            Mail::queue($mailable);
        }
        catch (\Throwable $e)
        {
            $this->trace->count(Metric::USER_EMAIL_OTP_SEND_FAILED);

            $this->trace->traceException(
                $e,
                null,
                TraceCode::USERS_SEND_EMAIL_OTP_FAILED,
                compact('input'));

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_EMAIL_OTP_FAILED,
                null,
                null,
                $e->getMessage()
            );
        }

        $this->trace->count(Metric::USER_EMAIL_OTP_SENT);

        $this->traceEmailOtpLoginRoute($input, TraceCode::USER_SEND_EMAIL_OTP_FOR_LOGIN);

        return array_only($otp, 'token');
    }

    /**
     * @throws BadRequestException
     */
    public function mobileOtpLogin(array $input)
    {
        if (isset($input[Entity::CONTACT_MOBILE]) === false)
        {
            return null;
        }

        // we do not wish to disclose that an account does not exist for a phone number.
        // So, we'll just return a dummy token.
        try
        {
            $receiver = $this->getSingleUserByMobileOrFail($input[Entity::CONTACT_MOBILE]);
        }
        catch (Throwable $e)
        {
            switch ($e->getCode())
            {
                case ErrorCode::BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED:
                    return [
                        "token"=>$input['token'] ?? Entity::generateUniqueId()
                    ];
                default:
                    throw $e;
            }
        }

        try
        {
            $receiver = $this->isMobileVerified($receiver);
        }
        catch (Throwable $e)
        {
            switch ($e->getCode())
            {
                case ErrorCode::BAD_REQUEST_CONTACT_MOBILE_NOT_VERIFIED:
                    if($receiver->getConfirmedAttribute() === false)
                    {
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_MOBILE_OTP_LOGIN_NOT_ALLOWED,
                            null,
                            [
                                "internal_error_code"=> ErrorCode::BAD_REQUEST_MOBILE_OTP_LOGIN_NOT_ALLOWED
                            ]
                        );
                    }
                    throw $e;
                default:
                    throw $e;
            }
        }

        $token = $this->sendLoginOtpViaSms($input, $receiver);

        return $token;
    }

    public function loginWithOtp(array $input)
    {
        $this->getUserEntity()->getValidator()->validateInput('loginOtp', $input);

        $token = $this->mobileOtpLogin($input);

        // unsetting the skip_sms_request to not disturb the verification rules
        // after the send otp sms

        unset($input[Entity::SKIP_SMS_REQUEST]);

       // $this->trace->count(Merchant\Metric::Login_total);
        if ($token !== null)
        {
            return $token;
        }
        // we do not wish to disclose that an account does not exist for an email.
        // So, we'll just return a dummy token.
        try
        {
            $receiver = $this->repo->user->findByEmail($input[Entity::EMAIL]);
        }
        catch (Throwable $e)
        {
            switch($e->getCode())
            {
                case ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND:
                    return [
                        "token"=>$input['token'] ?? Entity::generateUniqueId()
                    ];
                default:
                    throw $e;
            }
        }

        $receiver = $this->isEmailVerified($receiver);

        $token = $this->sendLoginOtpViaEmail($input, $receiver);


        return $token;
    }

    /**
     * @param array $input
     * @return Entity
     * @throws BadRequestException
     * @throws NumberParseException
     * @throws Throwable
     */
    public function fetchUser(array $input)
    {
        if (isset($input[Entity::CONTACT_MOBILE]) === true)
        {
            $user = $this->getSingleUserByMobileOrFail($input[Entity::CONTACT_MOBILE]);

            $user = $this->isMobileVerified($user);

            return $user;
        }

        $user = $this->repo->user->findByEmail($input[Entity::EMAIL]);

        $user = $this->isEmailVerified($user);

        return $user;
    }

    /**
     * Verify OTP with Raven service
     * @param $receiver
     * @param $input
     * @param $userId
     * @throws BadRequestException on incorrect OTP
     */
    private function verifyLoginSignupOtp($receiver, $input, $userId)
    {
        $payload = [
            'receiver' => $receiver,
            'source'   => "api.user.{$input['action']}"
        ];

        $payload = array_merge($payload, $this->getToken($userId, $input));

        $payload = array_only($payload, ['context', 'receiver', 'source']) + array_only($input, 'otp');

        try
        {
            $this->app->raven->verifyOtp($payload);
        }
        catch (\Throwable $e)
        {
            $this->trace->count(
                Constants::VERIFY_LOGIN_SIGNUP_OTP_METRICS[$input['action']],
                [
                    Constants::METHOD => Constants::OTP,
                    Constants::MEDIUM => isset($input[Entity::CONTACT_MOBILE]) ? Constants::CONTACT_MOBILE : Constants::EMAIL,
                    Constants::ACTION => $input['action']
                ]
            );

            switch ($e->getCode())
            {
                case ErrorCode::BAD_REQUEST_OTP_MAXIMUM_ATTEMPTS_REACHED:
                    throw new Exception\BadRequestException(
                        $e->getCode(),
                        null,
                        [
                            "internal_error_code"=>$e->getCode()
                        ]
                    );
                default:
                    throw new Exception\BadRequestException($e->getCode());
            }
        }
    }

    /**
     * Check if no. of OTP emails sent to user for logging in has exceeded a threshold and throw an exception.
     * User will not be sent another OTP email for 30 mins.
     * @param $email
     * @throws Exception\ServerErrorException
     * @throws BadRequestException
     */
    protected function checkLoginOtpVerificationLimitExceeded($receiver, $loginMedium, $user)
    {
        $errorDescription = 'An error occurred while interacting with redis on login otp verification route.';

        $count = LoginSignupRateLimit::incrementAndGetKeyCount(
            $receiver, Constants::VERIFY_LOGIN_OTP_RATE_LIMIT_SUFFIX,
            Constants::LOGIN_OTP_VERIFICATION_TTL,
            TraceCode::LOGIN_OTP_VERIFICATION_REDIS_ERROR,
            ErrorCode::SERVER_ERROR_LOGIN_OTP_VERIFICATION_REDIS_ERROR,
            $errorDescription
        );

        if ($count > Constants::LOGIN_OTP_VERIFICATION_THRESHOLD)
        {
            if ($loginMedium === Constants::EMAIL)
            {
                $traceData = ['email'=>$receiver];
            }
            else
            {
                $traceData = ['contact_mobile'=>mask_phone($receiver)];
            }
            $this->trace->info(TraceCode::LOGIN_OTP_VERIFICATION_THRESHOLD_EXHAUSTED, $traceData);

            // Lock account
            $user->setAccountLocked(true);

            $this->repo->saveOrFail($user);

            $this->trace->info(TraceCode::USER_LOGIN_2FA_ACCOUNT_LOCKED, ['user_id' => $user->getId()]);

            if (
                ($user->isAccountLocked() === true) and
                (isset($user[Entity::EMAIL])) and
                ($user->getConfirmedAttribute() === true))
            {
                $this->notifyUserAboutAccountLocked($user);
            }

            LoginSignupRateLimit::resetKey($receiver, Constants::VERIFY_LOGIN_OTP_RATE_LIMIT_SUFFIX);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LOGIN_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
                null,
                [
                    'internal_error_code' => ErrorCode::BAD_REQUEST_LOGIN_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
                    'user_details'        => [
                        'account_locked' => true,
                        'is_owner'       => $user->isOwner()
                    ]
                ]

            );
        }

    }

    /**
     * @throws BadRequestException
     */
    protected function checkSecondFactorAuthForOtpLogin(Entity $user)
    {
        if (($user->isSecondFactorAuth() === true) or
            ($user->isSecondFactorAuthEnforced() === true))
        {
            $this->trace->info(TraceCode::USER_LOGIN_2FA_ENABLED, ['user_id' => $user->getId()]);

            $this->trace->count(Metric::LOGIN_USER_2FA_ENABLED);

            if(empty($user->getPassword()) === true and $this->app['basicauth']->getRequestOriginProduct() === ProductType::BANKING) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED,
                    null,
                    [
                        'internal_error_code' => ErrorCode::BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED,
                        'user_details'        => [
                            'user_id'                   => $user->getId(),
                            'account_locked'            => $user->isAccountLocked(),
                            'user_mobile'               => $user->getMaskedContactMobile(),
                            'email'                     => $user->getMaskedEmail(),
                            'confirmed'                 => $user->getConfirmedAttribute(),
                            'is_password_set'           => false,
                            'is_mobile_verified'        => $user->isContactMobileVerified()
                        ]
                    ]);
            }

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_2FA_LOGIN_PASSWORD_REQUIRED,
                null,
                [
                    'internal_error_code' => ErrorCode::BAD_REQUEST_USER_2FA_LOGIN_PASSWORD_REQUIRED,
                    'user_details'        => [
                        'user_id'                   => $user->getId(),
                        'account_locked'            => $user->isAccountLocked(),
                        'user_mobile'               => $user->getMaskedContactMobile(),
                        'email'                     => $user->getMaskedEmail(),
                        'confirmed'                 => $user->getConfirmedAttribute(),
                        'access_token_2fa'          => $this->add2faToken($user)
                    ],
                ]);
        }
        // TODO: what about this? this is the same as 2FA with OTP
        if ($user->isOrgEnforcedSecondFactorAuth() === true)
        {
            $this->trace->info(TraceCode::LOGIN_ORG_ENFORCED_2FA_SUCCESS, ['user_id' => $user->getId()]);

            $this->trace->count(Metric::LOGIN_ORG_ENFORCED_2FA_SUCCESS);
        }
    }


    /**
     * @description Creates 2fa token for requests which contains x-mobile-oauth header
     * @param Entity $user
     * @return string
     * @throws BadRequestException
     * @throws Exception\ServerErrorException|Throwable
     */
    public function add2faToken(Entity $user) :? string
    {
        $isMobileOauthRequest = $this->app['request']->header(RequestHeader::X_MOBILE_OAUTH) === 'true';

        if ($isMobileOauthRequest === true)
        {
            $currentMerchant = $this->selectCurrentMerchant($user->getId(), $user);

            if (empty($currentMerchant) === false)
            {
                try
                {
                    $oAuthTokenService = new \RZP\Models\OAuthToken\Service();

                    $input = [
                        OAuthApplicationConstants::OAUTH_TOKEN_SCOPE        => OAuthApplicationConstants::RX_MOBILE_APP_2FA_TOKEN_SCOPE,
                        OAuthApplicationConstants::OAUTH_TOKEN_GRANT_TYPE   => OAuthApplicationConstants::RX_MOBILE_TOKEN_GRANT_TYPE,
                        OAuthApplicationConstants::OAUTH_TOKEN_MODE         => OAuthApplicationConstants::RX_MOBILE_TOKEN_MODE,
                        OAuthApplicationConstants::OAUTH_APP_TYPE           => OAuthApplicationConstants::RX_MOBILE_APP_TYPE,
                        OAuthApplicationConstants::OAUTH_APP_NAME           => OAuthApplicationConstants::RX_MOBILE_APP_NAME,
                        OAuthApplicationConstants::OAUTH_APP_WEBSITE        => OAuthApplicationConstants::RX_MOBILE_APP_WEBSITE,
                    ];

                    $this->trace->info(TraceCode::MOBILE_OAUTH_REQUEST_FOR_2FA_TOKEN, $input);

                    $responseToken = $oAuthTokenService->createOauthAppAndTokenForMobileApp($user->getId(),
                        $currentMerchant,
                        $input
                        );

                    return $responseToken[OAuthApplicationConstants::ACCESS_TOKEN];
                }
                catch (\Exception $e)
                {
                    $this->trace->traceException($e, Trace::ERROR, TraceCode::MOBILE_OAUTH_2FA_TOKEN_GENERATION_ERROR);

                    throw new Exception\ServerErrorException(
                        "Server Error encountered while generating 2fa token",
                        ErrorCode::SERVER_ERROR
                    );
                }
            }
            else
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_NOT_FOUND,
                    null,
                    [
                        'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_FOUND
                    ]);
            }
        }

        return null;
    }

    /**
     * @description This function returns merchant for a given user based on role and product priority
     * @param string $userId
     * @param Entity|null $user
     * @return MerchantEntity|null
     */
    public function selectCurrentMerchant(string $userId, Entity $user=null):?MerchantEntity
    {
        if ($user === null)
        {
            /** @var Entity $user */
            $user = $this->repo->user->findByPublicId($userId);
        }

        $currentProduct = $this->app['basicauth']->getRequestOriginProduct();

        $bankingOwnerMerchant = $user->merchantsByProductAndRole(ProductType::BANKING)->first();

        $primaryOwnerMerchant = $user->merchantsByProductAndRole()->first();

        if ($currentProduct === Product::BANKING)
        {
            if (empty($bankingOwnerMerchant) === false)
            {
                return $bankingOwnerMerchant;
            }

            if (empty($primaryOwnerMerchant) === false)
            {
                return $primaryOwnerMerchant;
            }

            $bankingMerchant = $user->bankingMerchants()->first();

            if (empty($bankingMerchant) === false)
            {
                return $bankingMerchant;
            }
        }

        else
        {
            if (empty($primaryOwnerMerchant) === false)
            {
                return $primaryOwnerMerchant;
            }

            if (empty($bankingOwnerMerchant) === false)
            {
                return $bankingOwnerMerchant;
            }

            $primaryMerchant = $user->primaryMerchants()->first();

            if (empty($primaryMerchant) === false)
            {
                return $primaryMerchant;
            }
        }

        return null;
    }

    /**
     * @param array $input
     * @return array
     * @throws BadRequestException
     * @throws Exception\ServerErrorException
     */
    public function verifyLoginOtp(array $input): array
    {
        $this->getUserEntity()->getValidator()->validateInput('verifyLoginOtp', $input);

        if (isset($input[Entity::CONTACT_MOBILE]) === true)
        {
            $loginMedium = Constants::CONTACT_MOBILE;

            $receiver = $input[Entity::CONTACT_MOBILE];
        }
        else
        {
            $loginMedium = Constants::EMAIL;

            $receiver = $input[Entity::EMAIL];
        }

        try
        {
            $user = $this->fetchUser($input);
        }
        catch (Throwable $e)
        {
            switch($e->getCode())
            {
                case ErrorCode::BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED:
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_INCORRECT_OTP
                    );
                case ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND:
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_INCORRECT_OTP
                    );
                default:
                    throw $e;
            }
        }

        // check if account is locked before checking for limits
        $this->checkIfOtpLoginLocked($user);

        $this->checkLoginOtpVerificationLimitExceeded($receiver, $loginMedium, $user);

        $input = array_merge($input, $this->getLoginSignupOtpPayload($input, Constants::LOGIN_OTP_ACTION));

        if ($input[Entity::MEDIUM] === Org\Constants::SMS and
            $this->app['basicauth']->getRequestOriginProduct() === ProductType::BANKING and
            $this->app['razorx']->getTreatment($this->app['request']->getTaskId(), Constants::API_STORK_RX_SEND_SMS_RAZORX_EXP, Mode::LIVE) === 'on')
        {
            $input[Entity::ACTION] = Constants::X_LOGIN_OTP_ACTION;
        }

        if($input[Entity::MEDIUM] === Org\Constants::SMS and
            $this->app['basicauth']->getRequestOriginProduct() === ProductType::PRIMARY and
            $input[Entity::ACTION] == Constants::LOGIN_OTP_ACTION)
        {
            $input[Entity::ACTION] = Constants::LOGIN_OTP_ACTION_V2;
        }

        $this->verifyLoginSignupOtp($receiver, $input, $user->getId());

        LoginSignupRateLimit::resetKey($receiver, Constants::VERIFY_LOGIN_OTP_RATE_LIMIT_SUFFIX);

        if ($loginMedium === Constants::EMAIL)
        {
            LoginSignupRateLimit::resetKey($receiver, Constants::SEND_EMAIL_LOGIN_OTP_RATE_LIMIT_SUFFIX);
        }

        if (isset($input[Entity::CONTACT_MOBILE]) === true)
        {
            $maskedInput[Entity::CONTACT_MOBILE] = $user->getMaskedContactMobile();

            $this->traceMobileLoginRoute($maskedInput, TraceCode::USER_VERIFY_SMS_OTP_FOR_LOGIN);
        }
        else
        {
            $this->traceEmailOtpLoginRoute($input, TraceCode::USER_VERIFY_EMAIL_OTP_FOR_LOGIN);
        }

        $this->trace->count(
            Metric::USER_LOGIN_COUNT,
            [
                Constants::METHOD => Constants::OTP,
                Constants::MEDIUM => $loginMedium,
            ]
        );

        $this->applyReferralIfApplicable($input, $user);

        $this->checkSecondFactorAuthForOtpLogin($user);

        return $this->get($user);
    }

    /**
     * @param $userId
     * @throws Exception\ServerErrorException|BadRequestException
     */
    protected function check2faWithPasswordAttemptsExhausted($userId)
    {
        $errorDescription = 'An error occurred while interacting with redis on 2fa with password route.';

        $count = LoginSignupRateLimit::incrementAndGetKeyCount(
            $userId, Constants::TWO_FA_PASSWORD_RATE_LIMIT_SUFFIX,
            Constants::INCORRECT_LOGIN_TTL,
            TraceCode::INCORRECT_2FA_PASSWORD_REDIS_ERROR,
            ErrorCode::SERVER_ERROR_2FA_INCORRECT_PASSWORD_REDIS_ERROR,
            $errorDescription
        );

        if ($count > Constants::INCORRECT_LOGIN_2FA_PASSWORD_THRESHOLD_COUNT)
        {
            $this->trace->info(TraceCode::LOGIN_2FA_PASSWORD_SUSPENDED, ['userId'=>$userId]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_2FA_LOGIN_PASSWORD_SUSPENDED,
                null,
                [
                    'internal_error_code'    => ErrorCode::BAD_REQUEST_2FA_LOGIN_PASSWORD_SUSPENDED,
                    'user_details'           => [
                        'user_id' => $userId
                    ],
                ]
            );
        }
    }

    /**
     * @param Entity $user
     * @param array $input
     * @return array
     * @throws BadRequestException|Exception\ServerErrorException
     */
    public function loginOtp2faPassword(Entity $user, array $input): array
    {
        $this->getUserEntity()->getValidator()->validateInput('login_otp_2fa_password', $input);

        $this->checkUserAccountNotLockedOrThrowException($user);

        $userId = $user->getId();

        if ((new BcryptHasher)->check($input[Entity::PASSWORD], $user->getPassword()) == false)
        {
            $this->trace->count(Metric::LOGIN_2FA_INCORRECT_PASSWORD);

            $this->check2faWithPasswordAttemptsExhausted($userId);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_PASSWORD,
                null,
                [
                    'internal_error_code'    => ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_PASSWORD,
                    'user_details'           => [
                        'user_id' => $userId,
                        'restricted' => $user->restricted,
                        'account_locked' => $user->isAccountLocked()
                    ],
                ]);
        }

        LoginSignupRateLimit::resetKey($userId, Constants::TWO_FA_PASSWORD_RATE_LIMIT_SUFFIX);

        $this->trace->count(Metric::LOGIN_2FA_CORRECT_PASSWORD);

        return $this->get($user);
    }

    /** Send an otp to an email to verify it.
     * @param array $input
     * @param Entity $user
     * @return array
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     * @throws Exception\ServerErrorException
     */
    public function sendVerificationOtpViaEmail(array $input, Entity $user): array
    {
        if ($user->getConfirmedAttribute() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_EMAIL_ALREADY_VERIFIED,
                null,
                [
                    'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_ALREADY_VERIFIED
                ]);
        }

        LoginSignupRateLimit::validateKeyLimitExceeded(
            $input[Entity::EMAIL],
            Constants::SEND_EMAIL_OTP_VERIFICATION_RATE_LIMIT_SUFFIX,
            Constants::EMAIL_VERIFICATION_OTP_SEND_TTL,
            Constants::EMAIL_VERIFICATION_OTP_SEND_THRESHOLD
        );

        $input = array_merge($input, $this->getLoginSignupOtpPayload($input, Constants::VERIFY_USER_ACTION));

        $otp = $this->generateOtpForLoginSignup($user->getId(), $input);

        $payload = $this->getEmailPayload($input, $otp);

        $mailable = new OtpMail($payload, $user, $otp);

        try
        {
            Mail::queue($mailable);
        }
        catch (\Throwable $e)
        {
            $this->trace->count(Metric::USER_EMAIL_OTP_SEND_FAILED);

            $this->trace->traceException(
                $e,
                null,
                TraceCode::USER_SEND_VERIFICATION_EMAIL_OTP_FAILED,
                compact('input'));

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_EMAIL_OTP_FAILED);
        }

        $this->traceEmailOtpLoginRoute($input, TraceCode::USER_SEND_EMAIL_OTP_FOR_VERIFICATION);

        return array_only($otp, 'token');
    }

    public function sendVerificationOtpViaSms(array $input, Entity $user)
    {
        if ($user->isContactMobileVerified() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_VERIFIED,
                null,
                [
                    'internal_error_code' => ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_VERIFIED
                ]);
        }

        if($user->getConfirmedAttribute() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MOBILE_OTP_LOGIN_NOT_ALLOWED,
                null,
                [
                    "internal_error_code"=> ErrorCode::BAD_REQUEST_MOBILE_OTP_LOGIN_NOT_ALLOWED
                ]
            );
        }

        $input = array_merge($input, $this->getLoginSignupOtpPayload($input, Constants::VERIFY_USER_ACTION));

        $sendViaHelperMethod = false;

        if ($this->app['basicauth']->getRequestOriginProduct() === ProductType::BANKING and
            $this->app['razorx']->getTreatment($this->app['request']->getTaskId(), Constants::API_STORK_RX_SEND_SMS_RAZORX_EXP , Mode::LIVE) === 'on')
        {
            $input[Entity::ACTION] = Constants::X_VERIFY_USER_ACTION;

            $sendViaHelperMethod = true;
        }

        $otp = $this->generateOtpForLoginSignup($user->getId(), $input);

        $payload = $this->getSmsPayload($input, $otp);

        try
        {

            if ($sendViaHelperMethod === true)
            {
                $token = $this->sendOtpViaSms($input,null,$user,$otp);
            }
            else
            {
                $this->app->raven->sendOtp($payload);
            }

        }
        catch (\Throwable $e)
        {
            $this->trace->count(Metric::USER_SMS_OTP_SEND_FAILED);

            if (isset($input['contact_mobile']) === true)
            {
                $input['contact_mobile'] = mask_phone($input['contact_mobile']);
            }

            $this->trace->traceException(
                $e,
                null,
                TraceCode::USERS_SEND_SMS_OTP_FAILED,
                compact('input')
            );

            if ($sendViaHelperMethod === true)
            {
                switch ($e->getMessage())
                {
                    case Constants::STORK_RESOURCE_EXHAUSTED_MESSAGE:
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED,
                            null,
                            [
                                "internal_error_code" => ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED
                            ],
                            $e->getMessage()
                        );
                    default:
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_SMS_OTP_FAILED,
                            null,
                            null,
                            $e->getMessage()
                        );
                }
            }
            else
            {
                switch ($e->getCode())
                {
                    case ErrorCode::BAD_REQUEST_RESOURCE_EXHAUSTED:
                    case ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED:
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED,
                            null,
                            [
                                "internal_error_code" => ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED
                            ],
                            $e->getMessage()
                        );
                    default:
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_SMS_OTP_FAILED,
                            null,
                            null,
                            $e->getMessage()
                        );
                }
            }
        }

        $maskedInput[Entity::CONTACT_MOBILE] = $user->getMaskedContactMobile();

        $this->traceMobileLoginRoute($maskedInput, TraceCode::USER_SEND_SMS_OTP_FOR_VERIFICATION);

        if ($sendViaHelperMethod === true)
        {
            return $token;
        }

        return array_only($otp, 'token');
    }

    /**
     * @param array $input
     * @return null
     * @throws BadRequestException
     * @throws NumberParseException
     * @throws Throwable
     */
    public function fetchUserForVerification(array $input)
    {
        if (isset($input[Entity::CONTACT_MOBILE]) === true)
        {
            $user = $this->getSingleUserByMobileOrFail($input[Entity::CONTACT_MOBILE]);

            return $user;
        }

        $user = $this->repo->user->findByEmail($input[Entity::EMAIL]);

        return $user;
    }

    /**
     * sendVerificationOtp fetches the user based on email or mobile. On successful authentication, an OTP is sent to
     * the user's login medium. The generated token is returned.
     *
     * @param array $input
     *
     * @return array
     * @throws BadRequestException
     * @throws Throwable
     */
    public function sendVerificationOtp(array $input)
    {
        $this->getUserEntity()->getValidator()->validateInput('sendVerificationOtp', $input);

        try
        {
            $receiver = $this->fetchUserForVerification($input);
        }
        catch (Throwable $e)
        {
            switch($e->getCode())
            {
                case ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND:
                case ErrorCode::BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED:
                    return [
                        "token"=>$input['token'] ?? Entity::generateUniqueId()
                    ];
                default:
                    throw $e;
            }
        }

        $isPasswordEqual = (new BcryptHasher)->check($input[Entity::PASSWORD], $receiver->getPassword());

        if ($isPasswordEqual === false)
        {
            $this->trace->count(Metric::USER_NOT_AUTHENTICATED);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PASSWORD_INCORRECT);
        }

        if (isset($input[Entity::EMAIL]) === true)
        {
            $token = $this->sendVerificationOtpViaEmail($input, $receiver);
        }
        else if (isset($input[Entity::CONTACT_MOBILE]) === true)
        {
            $token = $this->sendVerificationOtpViaSms($input, $receiver);
        }

        return $token;
    }

    public function checkIfContactMobileOrEmailIsVerified(array $input, Entity $user)
    {
        $receiver = null;

        if (isset($input[Entity::CONTACT_MOBILE]) === true)
        {
            $receiver = $input[Entity::CONTACT_MOBILE];

            if ($user->isContactMobileVerified() === true)
            {
                $this->trace->count(Metric::USER_MOBILE_ALREADY_VERIFIED);

                throw new BadRequestValidationFailureException('Contact mobile is already verified');
            }
        }
        else if (isset($input[Entity::EMAIL]) === true)
        {
            $receiver = $input[Entity::EMAIL];

            if ($user->getConfirmedAttribute() === true)
            {
                $this->trace->count(Metric::USER_EMAIL_ALREADY_VERIFIED);

                throw new BadRequestValidationFailureException('Email is already verified');
            }
        }

        return $receiver;
    }

    public function setContactMobileOrEmailVerify(array $input, Entity $user)
    {
        if (isset($input[Entity::CONTACT_MOBILE]) === true)
        {
            $user->setContactMobileVerified(true);

            $this->repo->saveOrFail($user);

            $maskedInput[Entity::CONTACT_MOBILE] = $user->getMaskedContactMobile();

            $this->trace->count(Metric::VERIFY_USER_MOBILE);

            $this->traceMobileLoginRoute($maskedInput, TraceCode::USER_VERIFY_SMS_OTP_FOR_VERIFICATION);

            return Constants::CONTACT_MOBILE;
        }
        else if (isset($input[Entity::EMAIL]) === true)
        {
            $this->confirm($user, Entity::OTP);

            $this->trace->count(Metric::VERIFY_USER_EMAIL);

            $this->traceEmailOtpLoginRoute($input, TraceCode::USER_VERIFY_EMAIL_OTP_FOR_VERIFICATION);

            return Constants::EMAIL;
        }
    }

    /**
     * Check if no. of OTP emails sent to user for logging in has exceeded a threshold and throw an exception.
     * User will not be sent another OTP email for 30 mins.
     * @param $receiver
     * @param $loginMedium
     * @param $userId
     * @throws BadRequestException
     * @throws Exception\ServerErrorException
     */
    protected function checkVerifyOtpVerificationLimitExceeded($receiver, $loginMedium, $userId)
    {
        $errorDescription = 'An error occurred while interacting with redis on email otp login verify route.';

        $count = LoginSignupRateLimit::incrementAndGetKeyCount(
            $receiver, Constants::VERIFY_OTP_VERIFICATION_RATE_LIMIT_SUFFIX,
            Constants::VERIFICATION_OTP_VERIFICATION_TTL,
            TraceCode::VERIFY_OTP_VERIFICATION_REDIS_ERROR,
            ErrorCode::SERVER_ERROR_VERIFY_OTP_VERIFICATION_REDIS_ERROR,
            $errorDescription
        );

        if ($count > Constants::VERIFICATION_OTP_VERIFICATION_THRESHOLD)
        {
            if ($loginMedium === Constants::EMAIL)
            {
                $traceData = ['email'=>$receiver];
            }
            else
            {
                $traceData = ['contact_mobile'=>mask_phone($receiver)];
            }
            $this->trace->info(TraceCode::VERIFICATION_OTP_VERIFICATION_THRESHOLD_EXHAUSTED, $traceData);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VERIFICATION_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
                null,
                [
                    'internal_error_code'   => ErrorCode::BAD_REQUEST_VERIFICATION_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
                ]
            );
        }

    }

    /**
     * verifyVerificationOtp fetches the user based on email or mobile. Based on the token, it verifies the OTP and
     * returns the user on successful verification.
     *
     * @param array $input
     *
     * @return array
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     * @throws Exception\ServerErrorException
     */
    public function verifyVerificationOtp(array $input): array
    {
        $this->getUserEntity()->getValidator()->validateInput('verifyVerificationOtp', $input);

        try
        {
            $user = $this->fetchUserForVerification($input);
        }
        catch (Throwable $e)
        {
            switch($e->getCode())
            {
                case ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND:
                case ErrorCode::BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED:
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_INCORRECT_OTP
                    );
                default:
                    throw $e;
            }
        }

        $receiver = $this->checkIfContactMobileOrEmailIsVerified($input, $user);

        if (isset($input[Entity::CONTACT_MOBILE]) === true)
        {
            $dimensionsForUserLogin = [
                Constants::METHOD => Constants::OTP,
                Constants::MEDIUM => Constants::CONTACT_MOBILE,
            ];
        }
        else
        {
            $dimensionsForUserLogin = [
                Constants::METHOD => Constants::OTP,
                Constants::MEDIUM => Constants::EMAIL,
            ];
        }

        $this->checkVerifyOtpVerificationLimitExceeded($receiver, $dimensionsForUserLogin[Constants::MEDIUM], $user->getId());

        $input = array_merge($input, $this->getLoginSignupOtpPayload($input, Constants::VERIFY_USER_ACTION));

        if ($input[Entity::MEDIUM] === Org\Constants::SMS and
            $this->app['basicauth']->getRequestOriginProduct() === ProductType::BANKING and
            $this->app['razorx']->getTreatment($this->app['request']->getTaskId(), Constants::API_STORK_RX_SEND_SMS_RAZORX_EXP , Mode::LIVE) === 'on')
        {
            $input[Entity::ACTION] = Constants::X_VERIFY_USER_ACTION;
        }

        $this->verifyLoginSignupOtp($receiver, $input, $user->getId());

        if (isset($input[Entity::EMAIL]))
        {
            LoginSignupRateLimit::resetKey($receiver, Constants::SEND_EMAIL_OTP_VERIFICATION_RATE_LIMIT_SUFFIX);
        }

        LoginSignupRateLimit::resetKey($receiver, Constants::VERIFY_OTP_VERIFICATION_RATE_LIMIT_SUFFIX);

        $this->setContactMobileOrEmailVerify($input, $user);


        $this->trace->count(Metric::USER_LOGIN_COUNT, $dimensionsForUserLogin);

        return $this->get($user);
    }

    public function checkSecondFactorAuthAndSendOtp($user)
    {
        //check if second factor auth is enabled for the user
        if (($user->isSecondFactorAuth() === true) or
            ($user->isSecondFactorAuthEnforced() === true))
        {
            $this->trace->info(TraceCode::USER_LOGIN_2FA_ENABLED, ['user_id' => $user->getId()]);

            $this->trace->count(Metric::LOGIN_USER_2FA_ENABLED);

            $this->sendOtpForSecondFactorAuthOnLogin($user);
        }

        if ($user->isOrgEnforcedSecondFactorAuth() === true)
        {
            $this->trace->info(TraceCode::LOGIN_ORG_ENFORCED_2FA_SUCCESS, ['user_id' => $user->getId()]);

            $this->trace->count(Metric::LOGIN_ORG_ENFORCED_2FA_SUCCESS);
        }

        $this->trace->count(Metric::LOGIN_2FA_SUCCESS);
    }

    public function oauthLogin(array $input)
    {
        $this->getUserEntity()->getValidator()->validateInput('loginOauth', $input);

        $user = $this->repo->user->getUserFromEmailOrFail($input[Entity::EMAIL]);

        $this->verifyOauthIdToken($input);

        $this->saveOauthProvider($user, $input[Entity::OAUTH_PROVIDER]);

        $response = [];

        $response[Constants::INVALIDATE_SESSIONS] = false;

        // For this User signs up via email/password and drops off in the middle
        // comes back and logs in via Google OAUth
        // We will reset and invalidate user's password for security reasons.
        if ($user->getConfirmedAttribute() === false and $user->isSignupViaEmail() === true)
        {
            $response[Constants::INVALIDATE_SESSIONS] = true;

            $this->confirm($user);

            $this->trace->info(TraceCode::USER_CONFIRM_INVALIDATE_INFO, ['user_id' => $user->getId()]);

            // reset the password of the user as well.
            $this->invalidatePassword($user);

            $this->repo->transactionOnLiveAndTest(function() use ($user) {
                $this->invalidateContactInfo($user);
            });
        }

        $this->applyReferralIfApplicable($input, $user);

        $this->checkSecondFactorAuthAndSendOtp($user);

        (new Core)->trackOnboardingEvent($user->getEmail(),
                                         EventCode::MERCHANT_ONBOARDING_LOGIN_SUCCESS);

        $orgId = $this->app['basicauth']->getOrgId();

        if(empty($orgId) === true)
        {
            $orgId = Org\Entity::RAZORPAY_ORG_ID;
        }

        if($orgId === Org\Entity::BAJAJ_ORG_SIGNED_ID)
        {
            $merchant = $this->findMerchant($user[Entity::ID]);
        }
        else
        {
            $merchant = $this->findMerchantForOrg($user[Entity::ID], $orgId);
        }


        //  $this->mode === 'test', just a hack need to write proper test case after setting product as Banking in requests origin

        if(($merchant !== null) and (($this->app['basicauth']->getRequestOriginProduct() === ProductType::BANKING) or $this->mode === 'test')) {

            $this->app['x-segment']->sendEventToSegment(SegmentEvent::USER_LOGIN, $merchant);
        }

        $response = array_merge($response, $this->get($user, true));

        return $response;
    }

    //verify otp on the new added number
    public function verifyOtpAndUpdateContactMobile(array $input, Merchant\Entity $merchant, Entity $user)
    {
        $input[Constants::UNIQUE_ID] = $user->getId();

        $input[Constants::ACTION]    = Entity::SECOND_FACTOR_AUTH;

        $smsOtpAuth = $this->app['module']->secondFactorAuth::make('SmsOtpAuth');

        $contact = $input[Constants::RECEIVER];

        if($smsOtpAuth->is2faCredentialValid($input) == false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INCORRECT_OTP);
        }

        $this->repo->transactionOnLiveAndTest(function() use ($user, $contact)
        {
            $user->setContactMobile($contact);

            $this->repo->saveOrFail($user);

            $user->setContactMobileVerified(true);

            $this->repo->saveOrFail($user);

            $primaryOwnerMerchantIdList = $user->getPrimaryMerchantIds();

            $this->trace->info(TraceCode::PRIMARY_OWNER_MERCHANT_IDS_LIST, [
                'merchant_ids'   => $primaryOwnerMerchantIdList,
            ]);

            $affectedMerchantIdList = [];

            foreach ($primaryOwnerMerchantIdList as $ownerMerchantId)
            {
                $merchant = $this->repo->merchant->findOrFailPublic($ownerMerchantId);

                if ($merchant->users()
                        ->where(MerchantDetailEntity::ROLE, '=', Role::OWNER)
                        ->where(Entity::PRODUCT, '=', Product::PRIMARY)
                        ->count() === 1)
                {
                    $affectedMerchantIdList[] = $ownerMerchantId;

                    $merchant->merchantDetail->setAttribute(Entity::CONTACT_MOBILE, $contact);

                    $this->repo->merchant_detail->saveOrFail($merchant->merchantDetail);
                }
            }

            $this->trace->info(TraceCode::AFFECTED_MERCHANT_IDS_LIST, [
                'merchant_ids' => $affectedMerchantIdList,
            ]);
        });

        [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Mobile Updated';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $segmentProperties, $segmentEventName
        );

        $this->increaseCacheValueForThrottleContactMobile($user);

        $this->notifyUserAboutContactMobileUpdate($user, $merchant);

        return $user;
    }

    protected function getThrottleContactMobileCacheKey(Entity $user)
    {
        return sprintf(Constants::THROTTLE_UPDATE_CONTACT_MOBILE_CACHE_KEY_PREFIX, $user->getId());
    }

    protected function increaseCacheValueForThrottleContactMobile(Entity $user)
    {
        $cacheKey = $this->getThrottleContactMobileCacheKey($user);

        $attempts = Cache::get($cacheKey, 0);

        if($attempts === 0)
        {
            Cache::add($cacheKey, 1, Carbon::now()->addDays(30));

            $this->trace->info(TraceCode::THROTTLE_UPDATE_CONTACT_MOBILE_KEY_CACHE_CREATED, [
                'cache_key'   => $cacheKey,
                'attempts'    => Cache::get($cacheKey)
            ]);

            return;
        }

        Cache::increment($cacheKey, 1);

        $this->trace->info(TraceCode::UPDATED_CONTACT_MOBILE_CACHE_VALUE_INCREASE, [
            'cache_key'   => $cacheKey,
            'attempts'    => Cache::get($cacheKey)
        ]);
    }

    public function verifyUserSecondFactorAuth(Entity $user, array $input): array
    {
        $this->getUserEntity()->getValidator()->validateInput('verify_user_second_factor', $input);

        $this->checkUserAccountNotLockedOrThrowException($user);

        $response =  $this->verifyOtpForSecondFactorAuthOnLogin($user, $input);

        if($user->getEmail() !== null)
        {
            /** @var HubspotClient $hubspotClient */
            $hubspotClient = $this->app->hubspot;
            $hubspotClient->trackHubspotEvent($user->getEmail(), [
                'contact_verified' => true
            ]);
        }

        return $response;
    }

    // We are invalidating the password for the user who has signed up from google oauth
    protected function invalidatePassword(Entity $user)
    {
        $user->setPasswordNull();

        $this->repo->saveOrFail($user);
    }

    /** Invalidating contact_name, contact_number, transaction_volume, business_type from
     *  merchants, merchant_details, users table for security reasons.
     *
     * @param Entity $user
     */
    protected function invalidateContactInfo(Entity $user)
    {
        try
        {
            $this->trace->info(TraceCode::USER_INVALIDATE_DEBUG, ['flow' => 'started']);

            $this->invalidateUserContactInfo($user);

            $merchant = $user->getMerchantEntity();

            if (empty($merchant) === true)
            {
                $this->trace->info(TraceCode::USER_INVALIDATE_MERCHANT_ERROR, ['user_id' => $user->getId()]);

                return;
            }

            $features = $this->repo->feature->findMerchantWithFeatures($merchant->getId(), [FeatureConstant::CREATE_SOURCE_V2]);

            $this->trace->info(TraceCode::USER_INVALIDATE_DEBUG, ['user_id' => $user->getId(), 'features' => $features]);

            if (count($features) > 0)
            {
                $this->trace->info(TraceCode::USER_INVALIDATE_DEBUG, ['user_id' => $user->getId(), 'flow' => 'skipped']);
                $this->trace->info(TraceCode::USER_INVALIDATE_SKIPPED, ['user_id' => $user->getId()]);

                return;
            }

            $merchantDetail = $merchant->merchantDetail;

            if ($merchantDetail->getActivationFormMilestone() != DetailConstants::L2_SUBMISSION)
            {
                $this->invalidateMerchantContactInfo($merchant);

                $this->invalidateMerchantDetailInfo($merchant);
            }

            $this->repo->saveOrFail($user);

            $this->trace->info(TraceCode::USER_INVALIDATE_DEBUG, ['user_id' => $user->getId(), 'flow' => 'executed']);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::INVALIDATE_CONTACT_DETAILS_ERROR,
                                         ['user_id' => $user->getId()]);
        }
    }

    protected function invalidateUserContactInfo($user)
    {
        $user->setName('');
        $user->setContactMobileNull();
    }

    protected function invalidateMerchantContactInfo($merchant)
    {
        $merchant->setName('');
        $this->repo->saveOrFail($merchant);
    }

    protected function invalidateMerchantDetailInfo($merchant)
    {
        $merchantDetail = $merchant->merchantDetail;

        $merchantDetail->setContactNameNull();
        $merchantDetail->setBusinessTypeNull();
        $merchantDetail->setBusinessNameNull();
        $merchantDetail->setContactMobileNull();
        $merchantDetail->setTransactionVolumeNull();
        $this->repo->saveOrFail($merchantDetail);
    }

    // User 2fa is enabled and 2fa is setup. If the request has the otp, it will check
    // if the otp is correct. Else if the otp is not there it will throw an exception.
    private function verifyOtpForSecondFactorAuthOnLogin(Entity $user, array $input)
    {
        if ($this->isCorrectOtpForSecondFactorAuthOnLogin($user, $input) === true)
        {
            $this->trace->count(Metric::LOGIN_2FA_CORRECT_OTP);

            $this->resetUserWrong2faAttempts($user);

            return $this->get($user);
        }
        else
        {
            $this->trace->count(Metric::LOGIN_2FA_INCORRECT_OTP);

            //if the otp is incorrect, increment the number of wrong 2fa attempts.
            $this->incrementWrong2faAttempts($user);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
                    null,
                    [
                    'internal_error_code'    => ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
                    'user_details'           => [
                            'user_id' => $user->getId(),
                            'restricted' => $user->restricted,
                            'account_locked' => $user->isAccountLocked()
                        ],
                    ]);
        }
    }

    public function get2FaAuthMode()
    {
        $orgId = $this->app['basicauth']->getOrgId();

        $org = $this->repo->org->findByPublicId($orgId);

        return $org->get2FaAuthMode();
   }

    private function isCorrectOtpForSecondFactorAuthOnLogin($user, $input)
    {
        $medium =  $this->get2FaAuthMode();

        if ((is_null($user->getEmail()) === true) and
            ($user->isSignupViaEmail() === false))
        {
            $medium = Org\Constants::SMS;
        }

        $data = [
            Entity::MEDIUM => $medium,
            Entity::ACTION => Entity::SECOND_FACTOR_AUTH,
            Entity::TOKEN  => $user->getId(),
            Entity::OTP    => $input[Entity::OTP],
        ];

        if ($medium === Org\Constants::SMS and
            $this->app['basicauth']->getRequestOriginProduct() === ProductType::BANKING and
            $this->app['razorx']->getTreatment($this->app['request']->getTaskId(), Constants::API_STORK_RX_SEND_SMS_RAZORX_EXP , Mode::LIVE) === 'on')
        {
            $data[Entity::ACTION] = Constants::X_SECOND_FACTOR_AUTH_ACTION;
        }

        try
        {
            $response = $this->verifyOtp($data, null, $user);

            if((isset($response['success']) === false) or
                ($response['success'] !== true))
            {
                $success = false;
            }
            else
            {
                $success = true;
            }
        }
        catch (\Exception $e)
        {
            $this->app['trace']->info(TraceCode::VERIFY_2FA_OTP_SMS_FOR_ACTION_FAILED, [
                'exception' => $e->getMessage(),
                'action'    => $data[Entity::ACTION],
            ]);

            $success = false;
        }

        return $success;
    }

    public function send2faOtp(Entity $user)
    {
        $this->checkUserAccountNotLockedOrThrowException($user);

        $medium = $this->get2FaAuthMode();

        if ($medium !== Org\Constants::EMAIL)
        {
            $this->check2faSetupDoneOrThrowException($user);
        }

        if ((is_null($user->getEmail()) === true) and
           ($user->isSignupViaEmail() === false))
        {
            $medium = Org\Constants::SMS;
        }

        $action = ($medium === Org\Constants::SMS and
            $this->app['basicauth']->getRequestOriginProduct() === ProductType::BANKING and
            $this->app['razorx']->getTreatment($this->app['request']->getTaskId(), Constants::API_STORK_RX_SEND_SMS_RAZORX_EXP , Mode::LIVE) === 'on')
            ? Constants::X_SECOND_FACTOR_AUTH_ACTION
            : Entity::SECOND_FACTOR_AUTH;

        $input = [
            Entity::MEDIUM => $medium,
            Entity::ACTION => $action,
            Entity::TOKEN => $user->getId()
        ];

        $this->sendOtp($input, null, $user);

        $this->trace->info(TraceCode::USER_2FA_OTP_SENT, ['user_id' => $user->getId()]);

        return [];
    }

    private function sendOtpForSecondFactorAuthOnLogin(Entity $user)
    {
        $this->send2faOtp($user);

        $this->trace->info(TraceCode::USER_LOGIN_2FA_OTP_SENT, ['user_id' => $user->getId()]);

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,
                    null,
                    [
                    'internal_error_code' => ErrorCode::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,
                    'user_details'        => [
                            'user_id'            => $user->getId(),
                            'account_locked'     => $user->isAccountLocked(),
                            'user_mobile'        => $user->getMaskedContactMobile(),
                            'email'              => $user->getMaskedEmail(),
                            'confirmed'          => $user->getConfirmedAttribute(),
                            'access_token_2fa'   => $this->add2faToken($user),
                        ],
                    ]);
    }

    private function resetUserWrong2faAttempts(Entity $user)
    {
        if (($user->getWrong2faAttempts() !== 0) or ($user->isContactMobileVerified() === false))
        {
            $user->setContactMobileVerified(true);

            $user->setWrong2faAttempts(0);

            $this->repo->saveOrFail($user);
        }
    }

    //used by login flow to increment wrong attempts and lock account if required
    private function incrementWrong2faAttempts(Entity $user)
    {
        $wrongTries = $user->getWrong2faAttempts() + 1;

        $user->setWrong2faAttempts($wrongTries);

        $this->trace->info(TraceCode::USER_LOGIN_2FA_WRONG_OTP, ['user_id' => $user->getId()]);

        $maxWrongTries = $this->config->get('applications.user_2fa.max_incorrect_tries');

        $orgId = $this->app['basicauth']->getOrgId();

        $org = $this->repo->org->findByPublicId($orgId);

        $maxWrongTries = min($maxWrongTries, $org->getMerchantMaxWrong2FaAttempts());

        if ($wrongTries >= $maxWrongTries)
        {
            $user->setAccountLocked(true);

            $this->trace->info(TraceCode::USER_LOGIN_2FA_ACCOUNT_LOCKED, ['user_id' => $user->getId()]);
        }

        $this->repo->saveOrFail($user);

        if ($user->isAccountLocked() === true) {
            $this->notifyUserAboutAccountLocked($user);
        }
    }

    /**
     * This method is used when user is trying to login but
     * his/her 2fa is not setup. So, user won't be able to login.
     * This will allow the frontend to pass the username, password
     * and hence setup the mobile 2fa.
     * Pass mobile number for setting up 2fa. The method
     * sends an otp the number and store the number in users table.
     *
     * @param   array $input
     */
    public function setup2faContactMobile(Entity $user, array $input)
    {
        $this->getUserEntity()->getValidator()->validateInput('setup2faMobile', $input);

        $this->checkIfUserCanHitSetup2faRoute($user);

        $smsOtpAuthPayload = $this->getSmsOtpAuthBasePayload($user, $input);

        $user->setContactMobile($input[Entity::CONTACT_MOBILE]);

        $this->repo->saveOrFail($user);

        $action = (
            $this->app['basicauth']->getRequestOriginProduct() === ProductType::BANKING and
            $this->app['razorx']->getTreatment($this->app['request']->getTaskId(), Constants::API_STORK_RX_SEND_SMS_RAZORX_EXP , Mode::LIVE) === 'on')
            ? Constants::X_SECOND_FACTOR_AUTH_ACTION
            : Entity::SECOND_FACTOR_AUTH;

        $input += [
            Entity::MEDIUM => Entity::MEDIUM_SMS,
            Entity::ACTION => $action,
            Entity::TOKEN  => $user->getId()
        ];

        $this->sendOtp($input, null, $user);

        return [];
    }

     /**
     * Verifies the otp sent on the number in setup2faMobile.
     * If otp is correct, the login for the user needs to be successful.
     * The method returns the user object if the otp is correct.
     *
     * @param   array $input
     * @return  User
     */
    public function setup2faVerifyMobileOnLogin(array $input)
    {
        $this->getUserEntity()->getValidator()->validateInput('setup2faVerifyMobile', $input);

        $user = $this->getUserByEmailAndVerifyPassword($input[Entity::EMAIL], $input[Entity::PASSWORD]);

        $this->checkIfUserCanHitSetup2faRoute($user);

        $smsOtpAuthPayload = $this->getSmsOtpAuthBasePayload($user, $input);

        $smsOtpAuthPayload[Entity::OTP] = $input[Entity::OTP];

        $smsOtpAuth = $this->app['module']->secondFactorAuth::make(AuthConstants::SMS_OTP_AUTH);

        if ($smsOtpAuth->is2faCredentialValid($smsOtpAuthPayload) === true)
        {
            $user->setContactMobileVerified(true);

            $this->repo->saveOrFail($user);

            return $this->get($user);
        }
        else
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_2FA_SETUP_INCORRECT_OTP,
                    null,
                    [
                        'internal_error_code'   => ErrorCode::BAD_REQUEST_2FA_SETUP_INCORRECT_OTP,
                    ]);
        }
    }

    /**
     * Checks if the user can hit this route. Only a user
     * which doesn't belong to non-restrcited can hit this route.
     * User should not already have a verified mobile number.
     *
     * @param  Entity $user
     * @throws Exception\BadRequestException
     */
    protected function checkIfUserCanHitSetup2faRoute(Entity $user)
    {

        if (($user->isSecondFactorAuth() === false) and
            ($user->isSecondFactorAuthEnforced() === false))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_2FA_SETUP_USER_2FA_NOT_ENABLED,
                    null,
                    [
                        'internal_error_code'   => ErrorCode::BAD_REQUEST_2FA_SETUP_USER_2FA_NOT_ENABLED,
                    ]);
        }

        if ($user->isAccountLocked() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_2FA_SETUP_ACCOUNT_LOCKED,
                    null,
                    [
                    'internal_error_code'   => ErrorCode::BAD_REQUEST_2FA_SETUP_ACCOUNT_LOCKED,
                    'restricted'            => $user->restricted,
                    'account_locked'        => true,
                    ]);
        }

        if ($user->getRestricted() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_RESTRICTED_USER_CANNOT_SETUP_2FA,
                    null,
                    [
                        'internal_error_code'   => ErrorCode::BAD_REQUEST_RESTRICTED_USER_CANNOT_SETUP_2FA,
                    ]);
        }

        if ($user->isSecondFactorAuthSetup() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_2FA_ALREADY_SETUP,
                    null,
                    [
                        'internal_error_code'   => ErrorCode::BAD_REQUEST_USER_2FA_ALREADY_SETUP,
                    ]);
        }
    }

    /**
     * Changes the 2fa setting against a user.
     * It can only be changed if 2fa is not mandated by any
     * merchant, the user belongs to.
     *
     * @param Entity $user
     * @param array $input
     *
     * @return array
     */
    public function change2faSetting(Entity $user, array $input): array
    {
        if ($this->isBankingDemoAccount($user))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_2FA_DISABLED_FOR_DEMO_ACC);
        }

        if ($user->isOrgEnforcedSecondFactorAuth() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ORG_2FA_ENFORCED);
        }

        if ($user->isSecondFactorAuthEnforced() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_2FA_ENFORCED);
        }

        if ($user->isSecondFactorAuthSetup() === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_2FA_SETUP_REQUIRED);
        }

        if($user->isContactMobileVerified() === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CONTACT_MOBILE_NOT_VERIFIED);
        }

        $action = $input[Entity::SECOND_FACTOR_AUTH];

        $user->setSecondFactorAuth($action);

        $this->repo->saveOrFail($user);

        if ($action === true)
        {
            $merchant = $this->merchant;

            [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

            $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Enable 2FA';

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $segmentProperties, $segmentEventName
            );
        }

        return [
            Entity::SECOND_FACTOR_AUTH => $user->isSecondFactorAuth(),
        ];
    }

    /**
     * Payload for sms based 2fa auth
     */
    protected function getSmsOtpAuthBasePayload(Entity $user, array $input = null): array
    {
        $contact = isset($input[Entity::CONTACT_MOBILE]) === true ?
                    $input[Entity::CONTACT_MOBILE] : $user->getContactMobile();

        return [
            Entity::ACTION => 'second_factor_auth',
            'receiver'     => $contact,
            'unique_id'    => $user->getId(),
        ];
    }

    /**
     * Takes in username and password and returns
     * user entity
     *
     * @param string $email
     * @param string $password
     *
     * @return Entity
     * @throws BadRequestException
     */
    protected function getUserByEmailAndVerifyPassword(string $email, string $password): Entity
    {
        $user = $this->repo->user->findByEmail($email);

        $isPasswordEqual = (new BcryptHasher)->check($password, $user->getPassword());

        if ($isPasswordEqual === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED);
        }

        return $user;
    }

    /**
     * Takes in user entity and validates if contact
     * mobile is verified
     * @param Entity $user
     *
     * @return Entity
     * @throws BadRequestException
     */
    protected function isMobileVerified(Entity $user)
    {
        if ($user->isContactMobileVerified() === true)
        {
            return $user;
        }

        $this->trace->count(Metric::USER_MOBILE_NOT_VERIFIED);

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_CONTACT_MOBILE_NOT_VERIFIED,
            null,
            [
                'internal_error_code' => ErrorCode::BAD_REQUEST_CONTACT_MOBILE_NOT_VERIFIED,
            ]);
    }

    /**
     * Takes in user entity and validates if email
     * is verified
     * @param Entity $user
     *
     * @return Entity
     * @throws BadRequestException
     */
    protected function isEmailVerified(Entity $user)
    {
        if ($user->getConfirmedAttribute() === true)
        {
            return $user;
        }

        $this->trace->count(Metric::USER_EMAIL_NOT_VERIFIED);

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_EMAIL_NOT_VERIFIED,
            null,
            [
                'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_NOT_VERIFIED,
            ]
        );
    }

    /**
     * Takes in user entity and password and returns
     * user entity
     *
     * @param Entity $user
     * @param string $password
     *
     * @return Entity
     * @throws BadRequestException
     */
    protected function verifyPassword(Entity $user, string $password)
    {
        $isPasswordEqual = (new BcryptHasher)->check($password, $user->getPassword());

        if ($isPasswordEqual === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED);
        }
    }

    /**
     * @param $mobile
     * @return null
     * @throws BadRequestException
     * @throws NumberParseException
     * @throws Throwable
     */
    public function getSingleUserByMobileOrFail(string $mobile)
    {
        $user = $this->getUserFromMobile($mobile);

        // if none of the mobile number formats are associated with any user, raise an exception
        if(empty($user) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED,
                null,
                [
                    'internal_error_code' => ErrorCode::BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED,
                ]
            );
        }

        return $user;
    }

    /**
     * @throws NumberParseException
     * @throws BadRequestException
     */
    public function getUserFromMobile(string $mobile)
    {
        $validMobileNumberFormats = (new PhoneBook($mobile))->getMobileNumberFormats();

        $user = null;
        $userCount = 0;

        foreach ($validMobileNumberFormats as $mobileNumber)
        {
            // - if multiple users exist for a mobile number format, `getUserFromMobileOrFail` will raise an exception
            // - if no users exist for a mobile number, check for the next format
            // - $userCount keeps a track of how many users across multiple formats are present.
            //   if this exceeds 1, throw an exception
            try
            {
                $user = $this->repo->user->getUserFromMobileOrFail($mobileNumber);
                $userCount = $userCount + 1;
                if($userCount > 1)
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED,
                        null,
                        [
                            'internal_error_code' => ErrorCode::BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED,
                        ]
                    );
                }
            }
            catch (Throwable $e)
            {
                switch ($e->getCode())
                {
                    case ErrorCode::BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED:
                        break;
                    default:
                        throw $e;
                }
            }
        }

        return $user;
    }

    /**
     * Takes in mobile number and checks if users
     * corresponding to any format of that mobile number already exist.
     *
     * @param string $mobile
     * @return bool
     * @throws NumberParseException
     */
    public function checkIfMobileAlreadyExists(string $mobile): bool
    {
        $validMobileNumberFormats = (new PhoneBook($mobile))->getMobileNumberFormats();

        foreach ($validMobileNumberFormats as $mobileNumber)
        {
            $users = $this->repo->user->findByMobile($mobileNumber);

            if($users->count() > 0)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Takes in an email and checks if a user
     * corresponding to that email already exists.
     *
     * @param string $email
     * @return bool True if email already exist, false otherwise
     * @throws Exception\ServerErrorException
     * @author kartiksayani
     */
    public function checkIfEmailAlreadyExists(string $email)
    {
        try
        {
            $this->repo->user->findByEmail($email);
        }
        catch (Exception\BadRequestException $e)
        {
            switch ($e->getCode())
            {
                // findByEmail throws an exception if no records are found. If this exact exception is received, return a false
                // Only this exception is expected to be raised.
                // For anything else, raise a ServerError.
                case ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND:
                    return false;
                default:
                    throw new Exception\ServerErrorException(
                        "Unknown Error encountered while checking if the email exists",
                        $e->getCode()
                    );
            }
        }
        return true;
    }

    /**
     * Serializes user along with all the merchant it has access to, it's
     * settings etcetera. Primarily consumed by internal dashboard application.
     *
     * @param Entity $user
     * @param bool   $optimize
     *
     * @return array
     */
    public function get(Entity $user, bool $optimize = false): array
    {
        $response = $user->toArrayPublic();

        //$merchantEntities = $user->merchants()->where(Merchant\Entity::SUSPENDED_AT, null)->take(1000)->get();
        // Refer to SBB-1061.
        // - cross org access to a merchant needs to be restricted. i.e. a user can access a HDFC org merchant only from hdfc.razorpay.com
        //   and not from dashboard.razorpay.com or other org dashboards.
        // - the fix here is to only return those merchants that have the same org_id as the dashboard the user is logged in on.
        //
        // - Apple watch uses oauth to access user_fetch_self route.
        // - As of June 13, 2022, only apple-watch uses user_fetch_self.
        // - user_fetch_self is exposed on oAuth and requires APPLE_WATCH_READ_WRITE scope in the token to be accessible.
        // - isAppleWatchApp is true is APPLE_WATCH_READ_WRITE scope is present in the token.
        // - So the fix will be bypassed for apple watch calling user_fetch_self
        // - For other use cases, the fix will be present.

        $orgId = $this->app['basicauth']->getOrgId();

        if(empty($orgId) === true)
        {
            $orgId = Org\Entity::RAZORPAY_ORG_ID;
        }

        $orgId = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $limit = self::USER_MERCHANTS_FETCH_COUNT;

        $properties = [
            'id'            => $user->getId(),
            'experiment_id' => $this->app['config']->get('app.user_fetch_merchant_list_limit_exp_id'),
        ];

        $merchantListLimitExpEnabled = (new Merchant\Core())->isSplitzExperimentEnable($properties, Constants::ACTIVE, TraceCode::USER_FETCH_MERCHANT_LIST_LIMIT_ERROR);

        if ($merchantListLimitExpEnabled)
        {
            $limit = self::USER_MERCHANTS_FETCH_COUNT_EXPERIMENT;
        }

        $this->trace->info(TraceCode::USER_FETCH_MERCHANT_LIST_LIMIT, [
            'user_id'   => $user->getId(),
            'limit'     => $limit,
        ]);

        $merchantEntities = $user->merchants()->where(Merchant\Entity::SUSPENDED_AT, null)->take($limit)->get();

        $merchantIdsWithCrossOrgFeature = $this->app['dcs']->fetchEntityIdsByFeatureName(DcsConstants::CrossOrgLogin, Type::MERCHANT, $this->mode);

        $this->trace->info(TraceCode::FETCH_ENTITY_IDS_BY_FEATURE_NAME, [
            "Mids fetched from DCS for cross org login feature" => $merchantIdsWithCrossOrgFeature,
        ]);

        if (empty($merchantIdsWithCrossOrgFeature) ===  true) {
            $merchantIdsWithCrossOrgFeature = (new Feature\Repository)->findMerchantIdsHavingFeatures([Features::CROSS_ORG_LOGIN]);
        }
        $filteredMerchants = new Base\PublicCollection;

        if($this->app['basicauth']->isAppleWatchApp() === true or $orgId === Org\Entity::BAJAJ_ORG_ID)
        {
            $filteredMerchants = $merchantEntities;
        }
        else
        {
            foreach ($merchantEntities as $merchant)
            {
                if ($merchant->getOrgID() === $orgId or in_array($merchant->getId(), $merchantIdsWithCrossOrgFeature, true) === true)
                {
                    $filteredMerchants->add($merchant);
                }
            }
        }

        $merchants = $filteredMerchants->callOnEveryItem('toArrayUser');

        $merchantsUnique = $this->getUnifiedMerchants($merchants);

        // Additional resources for users.

        $userId = $user->getUserId();

        if ($optimize === false)
        {
            $merchantsUnique = $this->appendBankingSpecificDetails($merchantsUnique, $userId);

            $merchantsUnique = $this->addProductSpecificDetails($merchantsUnique);

            $invitations     = $user->invitations->callOnEveryItem('toArrayUser');
            $settings        = $user->getAllSettings();

            $response[Entity::INVITATIONS] = $invitations;
            $response[Entity::SETTINGS]    = $settings;
        }

        $response[Entity::MERCHANTS]   = $merchantsUnique;

        return $response;
    }

    public function getActorInfo(string $id): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $this->trace->info(TraceCode::USER_DETAILS,
            [
                'user_id' => $user->getId(),
            ]);

        /** @var $ba BasicAuth */
        $ba = app('basicauth');

        $ba->setUser($user);

        return Adapter\Base::getActorInfo();
    }

    private function fetchUserPermissions($merchant)
    {
        $isCACEnabled = $this->isCACEnabled($merchant[Entity::ID]);

        $this->trace->info(TraceCode::CAC_EXPERIMENT_STATUS,
            [
                'cac_status' => $isCACEnabled,
                'merchant_id' => $merchant[Entity::ID]
            ]);

        if ( $isCACEnabled === true)
        {
            try
            {
                $authzRoles = (new \RZP\Models\RoleAccessPolicyMap\Service())->getAuthzRolesForRoleId($merchant[Entity::BANKING_ROLE]);

                $authzPolicies = (new AuthzAdmin\Service())->adminAPIListPolicy($authzRoles);

                /*
                 * Currently there is no way to hide specific permissions/policies using CAC.
                 * Hence after fetching allowed policies from AuthZ, we are filtering out restricted
                 * permissions for sub merchants on Account <> Sub-Account flow.
                 * TODO: Migrate this filtering to AuthZ once permission filtering based on Razorx/Splitz is supported
                 */
                return $this->removePermissionsForSubMerchantOnAccountSubAccountFlow($authzPolicies, $merchant[Entity::ID]);
            }
            catch (\Exception $exception)
            {
                $this->trace->error(TraceCode::FETCH_AUTHZ_ROLES_FAILED,
                    [
                        'merchant_id' => $merchant[Entity::ID],
                        'role_id' => $merchant[Entity::BANKING_ROLE],
                        'exception' => $exception
                    ]);
            }
        }

        // Fetch static role permissions map
        $basePermissions = UserRolePermissionsMap::getRolePermissions($merchant[Entity::BANKING_ROLE]);

        $basePermissions = $this->removePermissionsForSubMerchantOnAccountSubAccountFlow($basePermissions, $merchant[Entity::ID]);

        try {
            // Fetch merchant role permissions preferences for this specific user role
            $merchantOverrides  = (new Merchant\Attribute\Core())->fetchKeyValuesByMerchantId(
                $merchant[Entity::ID],
                Product::BANKING,
                Merchant\Attribute\Group::X_TRANSACTION_VIEW,
                [$merchant[Entity::BANKING_ROLE]]
            )->toArrayPublic();

        } catch (\Exception $e) {
            $merchantOverrides = [];
        }

        // If merchant has no rules for this role configured then return static permissions
        if (sizeof($merchantOverrides) === 0 || sizeof($merchantOverrides['items']) === 0)
        {
            return $basePermissions;
        }

        $hasMerchantAllowedViewTransaction = $merchantOverrides['items'][0]['value'] === 'true';

        // If merchant has allowed access for this role then attach it to permissions list
        if ($hasMerchantAllowedViewTransaction)
        {
            // Unique is needed because for every other role than operations VIEW_TRANSACTION is by default allowed
            // Taking unique will keep the code open for extension in future without having to write permission level conditions
            return array_values(
                    array_unique(
                        array_merge($basePermissions, [Permission::VIEW_TRANSACTION_STATEMENT])
                    )
                );
        }

        // If merchant has denied access for this role then remove the item from array
        return array_values(
            array_diff($basePermissions, [Permission::VIEW_TRANSACTION_STATEMENT])
        );
    }

    public function isCACEnabled($merchantId) :bool
    {
        $isCACExperimentEnabledVariant = app('razorx')->getTreatment($merchantId,
            RazorxTreatment::RX_CUSTOM_ACCESS_CONTROL_ENABLED,
            MODE::LIVE);

        $isCACExperimentDisabledVariant = app('razorx')->getTreatment($merchantId,
            RazorxTreatment::RX_CUSTOM_ACCESS_CONTROL_DISABLED,
            MODE::LIVE);

        app('trace')->info(TraceCode::CAC_EXPERIMENT_VARIANTS_STATUS,
            [
                'isCACExperimentEnabledVariant' => $isCACExperimentEnabledVariant,
                'isCACExperimentDisabledVariant' => $isCACExperimentDisabledVariant,
                'merchant_id' => $merchantId
            ]);

        if ($isCACExperimentEnabledVariant != RazorxTreatment::RAZORX_VARIANT_ON)
        {
            return $isCACExperimentDisabledVariant != RazorxTreatment::RAZORX_VARIANT_ON;
        }
        else
        {
            return $isCACExperimentEnabledVariant === RazorxTreatment::RAZORX_VARIANT_ON;
        }
    }

    /**
     * Appends banking specific details in serialized unique list of merchants where applies.
     * @param array $merchants
     * @param string|null $userId
     * @return array
     */
    protected function appendBankingSpecificDetails(array $merchants, string $userId = null)
    {
        return array_map(
            function (array $merchant) use ($userId)
            {
                if ($merchant[Entity::BANKING_ROLE] === null)
                {
                    return $merchant;
                }

                // Owner Role Banking Signup time (Created_at of merchant_user)
                if (empty($userId) === false)
                {
                    $merchant[Merchant\Entity::BUSINESS_BANKING_SIGNUP_AT] = $this->repo->merchant_user->fetchBankingSignUpTimeStampOfOwner($merchant['id']);
                }

                // Attach Permissions
                $merchant[Constants::PERMISSIONS] = $this->fetchUserPermissions($merchant);

                // Attach merchant attributes of specific groups
                $signupAttributes = (new Merchant\Attribute\Core())->fetchKeyValuesByMerchantId(
                                                                            $merchant['id'],
                                                                            Product::BANKING,
                                                                            Merchant\Attribute\Group::X_SIGNUP
                                                                    )->toArrayPublic();

                $currentAccountAttributes = (new Merchant\Attribute\Core())->fetchKeyValuesByMerchantId(
                                                                            $merchant['id'],
                                                                            Product::BANKING,
                                                                            Merchant\Attribute\Group::X_MERCHANT_CURRENT_ACCOUNTS
                                                                    )->toArrayPublic();

                $currentAccountAttributesFromPG = (new Merchant\Attribute\Core())->fetchKeyValuesByMerchantId($merchant['id'], Product::PRIMARY, Merchant\Attribute\Group::X_MERCHANT_CURRENT_ACCOUNTS)->toArrayPublic();

                $merchantPreferencesAttributes = (new Merchant\Attribute\Core())->fetchKeyValuesByMerchantId($merchant['id'], Product::BANKING, Merchant\Attribute\Group::X_MERCHANT_PREFERENCES)->toArrayPublic();

                $xProductEnabledAttributes = (new Merchant\Attribute\Core())->fetchKeyValuesByMerchantId($merchant['id'], Product::BANKING, Merchant\Attribute\Group::PRODUCTS_ENABLED)->toArrayPublic();
                // This is a hack, to unblock for now, need to figure out how to merge public arrays
                $settableAttributes['entity'] = "collection";
                $settableAttributes['count'] = count($xProductEnabledAttributes['items'] ?? []) +  count($signupAttributes['items']?? []) + count($currentAccountAttributes['items'] ?? []) + count($merchantPreferencesAttributes['items'] ?? []) + count($currentAccountAttributesFromPG['items'] ?? []);
                $settableAttributes['items'] = array_merge($xProductEnabledAttributes['items'] ?? [], $signupAttributes['items'] ?? [], $currentAccountAttributes['items'] ?? [], $merchantPreferencesAttributes['items'] ?? [], $currentAccountAttributesFromPG['items'] ?? []);
                $this->trace->info(TraceCode::USERS_SEND_OTP_FOR_ACTION_WITH_CONTACT, $settableAttributes);

                $merchant['attributes'] = $settableAttributes;


                /** @var Merchant\Balance\Entity $vaBalance */
                $vaBalance = $this->repo->balance->getMerchantBalanceByTypeAndAccountType(
                    $merchant['id'],
                    Merchant\Balance\Type::BANKING,
                    Merchant\Balance\AccountType::SHARED);

                // Balance entity doesn't have a status, so checking for active current accounts via BASD entity
                $caActiveBalanceIds = $this->repo->banking_account_statement_details->getBalanceIdsForActiveDirectAccounts($merchant['id']);

                /** @var Merchant\Balance\Entity $caBalance */
                $caBalance = $this->repo->balance->getMerchantBalancesByTypeAndAccountTypeAndBalanceIds(
                    $merchant['id'],
                    Merchant\Balance\Type::BANKING,
                    Merchant\Balance\AccountType::DIRECT,
                    $caActiveBalanceIds)->first();

                // We hit this flow during /login too where merchant even though of X,
                // doesn't have balance etc created yet.

                // If both VA and CA are not there
                if ($vaBalance === null and $caBalance === null)
                {
                    return $merchant;
                }

                $caActivationStatus = null;

                if (empty($caBalance) === false)
                {
                    $bankingAccountCA = $this->repo->banking_account->getActivatedBankingAccountFromBalanceId($caBalance->getId());

                    $caActivationStatus = optional($bankingAccountCA)->getStatus();

                    //Fetching icici ca status from banking account service.
                    if(empty($caActivationStatus) === true or
                       $caActivationStatus !== 'activated')
                    {
                        $caActivationStatus = (new BankingAccountService\Core())->fetchBasCaStatus($merchant['id']);
                    }
                }

                // If Only CA is there
                if (empty($vaBalance) === true)
                {
                    return $merchant + [
                        Merchant\Entity::CA_ACTIVATION_STATUS   => $caActivationStatus,
                        Merchant\Entity::ACCOUNTS               => $this->fetchBankingAccountWithBalance(
                            $merchant['id']),
                        ];
                }

                // If either VA is there or both VA and CA are there
                $bankingAccount = $this->repo->banking_account->getActivatedBankingAccountFromBalanceId($vaBalance->getId());

                $bulkUserType = $this->getBulkPayoutsUserType($vaBalance);

                return $merchant +
                    [
                        // balance for business banking activated at should have account_type shared
                        // Relevant slack thread : https://razorpay.slack.com/archives/CE4DMABE3/p1574075046102400

                        // Below fields except accounts and ca_activation_status are related to only virtual account

                        Merchant\Entity::BANKING_ACTIVATED_AT   => $vaBalance->getCreatedAt(),
                        Merchant\Entity::CA_ACTIVATION_STATUS   => $caActivationStatus,
                        Merchant\Entity::BANKING_BALANCE        => $vaBalance->only([Merchant\Balance\Entity::BALANCE,
                                                                                   Merchant\Balance\Entity::CURRENCY]),
                        Merchant\Entity::BANKING_ACCOUNT        => $bankingAccount->toArrayPublic(),
                        Merchant\Entity::ACCOUNTS               => $this->fetchBankingAccountWithBalance(
                                                                                    $merchant['id']),
                        Merchant\Entity::CREDIT_BALANCE         => $this->fetchBankingCreditBalances(
                                                                                    $merchant['id'],
                                                                                    Product::BANKING,
                                                                                    $vaBalance->getAccountType(),
                                                                                    $bankingAccount->getPublicId()),
                        Merchant\Entity::BULK_PAYOUTS_USER_TYPE => $bulkUserType,
                    ];
            },
            $merchants);
    }

    protected function addProductSpecificDetails(array $merchants)
    {
        try
        {
            return array_map(
                function (array $merchant)
                {
                    $merchantEntity = $this->repo->merchant->findOrFailPublic($merchant['id']);

                    $methods = $merchantEntity->getMethods();

                    $merchant['methods'] = $methods;

                    return $merchant;
                },
                $merchants);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            return $merchants;
        }
    }

    protected function fetchBankingAccountWithBalance($merchantId)
    {
        $bankingAccounts = $this->repo->banking_account->getBankingAccountsWithBalance($merchantId);

        $result = [];

        foreach ($bankingAccounts as $bankingAccount)
        {
            $bankingAccountArray = $bankingAccount->toArrayPublic();

            $bankingAccountArray['banking_balance'] = optional($bankingAccount->balance)->toArrayPublic();

            $result[] = $bankingAccountArray;
        }

        //fetches icici ca details from banking account service
        $result = (new BankingAccountService\Service())->fetchBankingAccountWithBalanceFromBas($merchantId, $result);

        return $result;
    }

    protected function fetchBankingCreditBalances($merchantId, $product, string $accountType, string $bankingAccountId)
    {
        $creditBalances = $this->repo
                               ->credits
                               ->getTypeAggregatedMerchantCreditsForProductForDashboard($merchantId, $product);

        $merchant = $this->repo->merchant->find($merchantId);

        // Calling ledger when merchant has "ledger_journal_reads" feature flag enabled
        // and balance is of type "shared".
        if (($merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true) &&
            ($accountType === Merchant\Balance\AccountType::SHARED))
        {
            $ledgerResponse = (new LedgerCore())->fetchBalanceFromLedger($merchantId, $bankingAccountId);

            if ((empty($ledgerResponse) === false) &&
                (empty($ledgerResponse[LedgerCore::REWARD_BALANCE]) === false) &&
                (empty($ledgerResponse[LedgerCore::REWARD_BALANCE][LedgerCore::BALANCE]) === false))
            {
                (new LedgerCore())->constructCreditBalanceFromLedger($creditBalances, $ledgerResponse);
            }
        }

        return $creditBalances;
    }

    /**
     * This function is used to add new relationship between user and merchant
     * This uses laravel attach which will create a new mapping.
     *
     * @param  Entity $user
     * @param  array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    protected function attach(Entity $user, array $input)
    {
        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $role = $input[Entity::ROLE];

        $product = $input[Entity::PRODUCT] ?? $this->app['basicauth']->getRequestOriginProduct();

        $mappingParams = [
             'role'       => $role,
             'product'    => $product,
             'created_at' => $currentTimestamp,
             'updated_at' => $currentTimestamp
        ];

        $merchantId = $input[Entity::MERCHANT_ID];

        $this->repo->merchant->findOrFailPublic($merchantId);

        $this->trace->info(TraceCode::MERCHANT_USER_MAPPING_QUERY);

        // On PG, X a user can have only 1 role. If we user with any role is already present throw an exception
        $mapping = $this->repo->merchant->getMerchantUserMapping($merchantId,
                                                                 $user->getId(),
                                                                 null,
                                                                 $product);

        $this->trace->info(TraceCode::MERCHANT_USER_MAPPING_QUERY_SUCCESSFUL);

        // Adding an experiment to restrict this behaviour in production if required
        // Ideally this should never happen, but we do have some users which have multiple roles per merchant, product
        // Since this is an unexpected use case, still adding an experiment to be safe
        // TODO: remove this experiment once the validation has been completed
        if ((empty($mapping) === false) and
            ($this->restrictUserToOneRolePerMerchantAndProduct($user) === true))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_WITH_ROLE_ALREADY_EXISTS);
        }

        $this->repo->attach($user, $product . Entity::MERCHANTS, [$merchantId => $mappingParams]);

        if ((new Merchant\Core())->isRazorxExperimentEnable(
                $user->getId(), RazorxTreatment::CREATE_ACCOUNT_API_PERFORMANCE_ANALYSIS) === true)
        {
            return [];
        }

        $this->trace->info(TraceCode::MERCHANT_USER_ATTACH_SUCCESSFUL);

        $response = $user->toArrayPublic();

        $this->trace->info(TraceCode::MERCHANT_USER_ENTITY_RESPONSE);

        return $response;
    }

     /**
     * This function is used to remove relationship between user and merchant
     * This uses laravel detach which will remove the existing mapping
     * @param  Entity $user
     * @param  array  $input
     * @return array
     */
    protected function detach(Entity $user, array $input)
    {
        $merchantId = $input[Entity::MERCHANT_ID];

        $product = $input[Entity::PRODUCT] ?? $this->app['basicauth']->getRequestOriginProduct();

        $this->repo->merchant->findOrFailPublic($merchantId);

        $this->repo->detach($user, $product . Entity::MERCHANTS, $merchantId);

        // TODO: detach roles() for RX banking workflows

        return $user->toArrayPublic();
    }

     /**
     * This function is used to update the exiting relationship between user and merchant
     * This uses laravel sync which will update the mapping only if detaching is false.
     * If detaching is passed as true (default value), then all the old mapping would be deleted.
     * and the new only will be inserted.
     * @param  Entity $user
     * @param  array  $input
     * @return array
     */
    protected function update(Entity $user, array $input)
    {
        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $role = $input[Entity::ROLE];

        $product = $input[Entity::PRODUCT] ?? $this->app['basicauth']->getRequestOriginProduct();

        $merchantId = $input[Entity::MERCHANT_ID];

        $mappingParams = [
            'role'       => $role,
            'updated_at' => $currentTimestamp,
            'created_at' => $currentTimestamp,
        ];

        $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);

        $this->repo->sync($user, $product . 'Merchants', [$merchantId => $mappingParams], false);

        return $user->toArrayPublic();
    }

    public function subscribeToMailingList($user)
    {
        $data = [
            'name'  => $user['name'],
            'email' => $user['email'],
        ];

        MailChimpSubscribe::dispatch($data);
    }

    /**
     * @return string
     */
    public function generateToken()
    {
        return str_random(Entity::PASSWORD_TOKEN_LENGTH);
    }

    /**
     * @param Entity $user
     * @param string $merchantId
     * @param string $role
     * @param string $product
     *
     * @return User
     */
    public function detachAndAttachMerchantUser(Entity $user, string $merchantId, string $role, string $product)
    {
        // Detach the existing merchant User.
        $userMerchantMappingData = [
            'action'      => 'detach',
            'merchant_id' => $merchantId,
            'product'     => $product,
        ];

        $this->updateUserMerchantMapping($user, $userMerchantMappingData);

        // Attach the merchant with the role.

        $userMerchantMappingData['action'] = 'attach';

        $userMerchantMappingData['role'] = $role;

        $userMerchantMappingData['product'] = $product;

        return $this->updateUserMerchantMapping($user, $userMerchantMappingData);
    }

    /**
     * Sends OTP to user's contact/email basis specified medium and action.
     * Input format:
     *     - action - E.g. create_payout, verify_contact
     *     - medium - sms|email, when empty does both sms & email
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param Entity          $user
     *
     * @return array
     */
    public function sendOtp(array $input, $merchant, Entity $user): array
    {
        $this->trace->info(TraceCode::USERS_SEND_OTP_FOR_ACTION, compact('input'));

        $input[Entity::MEDIUM] = $input[Entity::MEDIUM] ?? 'sms_and_email';

        $func = 'sendOtpVia' . studly_case($input[Entity::MEDIUM]);

        return $this->$func($input, $merchant, $user);
    }

    /**
     * @throws Throwable
     */
    public function sendOtpViaSmsAndEmail(array $input, $merchant, Entity $user): array
    {
        $otp = $this->generateOtpFromRaven($input, $merchant, $user);

        try
        {
            $this->sendOtpViaSms($input, $merchant, $user, $otp);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::USERS_SEND_SMS_OTP_FAILED,
                compact('input'));
        }

        try
        {
            $this->sendOtpViaEmail($input, $merchant, $user, $otp);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::USERS_SEND_EMAIL_OTP_FAILED,
                compact('input'));
        }

        return array_only($otp, 'token');
    }

    /**
     * Ref: sendOtp()
     *
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @param  Entity          $user
     * @param  array|null      $otp
     * @return array
     * @throws Throwable
     */
    public function sendOtpViaSms(array $input, $merchant, Entity $user, array $otp = null): array
    {
        if ((isset($input[Entity::MEDIUM]) === false) and
            (in_array($input[Entity::ACTION], Constants::ACTIONS_FOR_OTP_CONTACT_VERIFICATION, true) === false) and
            ($user->isContactMobileVerified() === false))
        {
            return [];
        }

        if ((in_array($input[Entity::ACTION], ['create_payout'], true) === true) and
            ((empty($user->getContactMobile()) === true) or
                ($user->isContactMobileVerified() === false)))
        {
            $this->trace->info(
                TraceCode::SKIPPING_SENDING_OTP_VIA_SMS_FOR_EMPTY_RECEIVER,
                [
                    'user_id' => $user->getId(),
                    'action' => $input[Entity::ACTION],
                ]
            );

            return [];
        }

        // Optimization: Do just one call to raven when input.medium = sms.
        $otp = $otp ?: $this->generateOtpFromRaven($input, $merchant, $user);

        $action = $input[Entity::ACTION];

        if (is_null($merchant) === true)
        {
            $mid = '10000000000000';
        }

        else
        {
            $mid = $merchant->getId();
        }

        // Using the existing create_payout template for Scan & Pay Feature
        // Will use a new template if SMS copy changes in future
        if ($action === Constants::CREATE_COMPOSITE_PAYOUT_WITH_OTP)
        {
            $action = Constants::CREATE_PAYOUT;
        }
        if ($action === Constants::CREATE_PAYOUT_BATCH_V2)
        {
            $action = Constants::CREATE_PAYOUT_BATCH;
        }

        if ($action === Constants::BULK_PAYOUT_APPROVE)
        {
            $templateShiftKey = (new Admin\Service)->getConfigKey(['key' => ConfigKey::SHIFT_BULK_PAYOUT_APPROVE_TO_BULK_APPROVE_PAYOUT_SMS_TEMPLATE]);

            if (array_key_exists(Constants::BULK_PAYOUT_APPROVE_TO_BULK_APPROVE_PAYOUT, $templateShiftKey) === true)
            {
                $merchants = $templateShiftKey[Constants::BULK_PAYOUT_APPROVE_TO_BULK_APPROVE_PAYOUT];

                if (($merchants == "*") or
                    (in_array($mid, $merchants) == true))
                {
                    $action                = Constants::BULK_APPROVE_PAYOUT;
                    $input[Entity::ACTION] = Constants::BULK_APPROVE_PAYOUT;
                }
            }

        }

        try
        {
            // Note: Add the actions to SEND_SMS_VIA_STORK since we are migrating sms delivery from raven to stork
            if (in_array($input[Entity::ACTION], Constants::SEND_SMS_VIA_STORK, true) === true)
            {
                $smsPayload = $this->generateStorkSmsPayload($input, $merchant, $user);

                $smsPayload['contentParams'] += [
                    'otp' => $otp['otp'],
                    'validity' => Carbon::createFromTimestamp($otp['expires_at'], Timezone::IST)->format('H:i:s'),
                ];

                $extraParams = $this->getExtraStorkSmsPayload($input);

                $smsPayload['contentParams'] = array_merge($extraParams, $smsPayload['contentParams']);

                $stork = $this->app['stork_service'];

                $stork->sendSms($this->mode, $smsPayload);
            }
            else
            {
                if (is_null($merchant) === true)
                {
                   $mid = '10000000000000';
                }

                else
                {
                    $mid = $merchant->getId();
                }

                $payload = [
                    'receiver' => $user->getContactMobile(),
                    'source' => 'api.user.' . $action,
                    'template' => $this->chooseSmsTemplate($mid, $action),
                    'params' => [
                        'otp' => $otp['otp'],
                        'validity' => Carbon::createFromTimestamp($otp['expires_at'], Timezone::IST)->format('H:i:s'),
                    ],
                ];

                $payload['params'] += $this->getExtraRavenSmsPayload($input, $merchant);

                $this->updateSmsTemplate($input, $payload);

                $this->app->raven->sendSms($payload);
            }
        }
        catch (\Throwable $e)
        {
            switch ($e->getCode())
            {
                case ErrorCode::BAD_REQUEST_RESOURCE_EXHAUSTED:
                case ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED:
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED,
                        null,
                        [
                            "internal_error_code" => ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED
                        ],
                        $e->getMessage()
                    );
                default:
                    throw $e;
            }
        }

        return array_only($otp, 'token');
    }

    public function chooseSmsTemplate($merchantId, string $action)
    {
        $templateName = 'sms.user.' . $action;

        if (array_key_exists($action, self::$actionToTemplateMapping))
        {
            $updatedSmsTemplates = (new Admin\Service)->getConfigKey(['key' => ConfigKey::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS]);

            $this->trace->info(
                TraceCode::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS_CONFIG_KEY,
                [
                    ConfigKey::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS => $updatedSmsTemplates
                ]
            );

            $templateNameFromMapping = self::$actionToTemplateMapping[$action] ?? 'sms.user.' . $action;

            if (array_key_exists($templateNameFromMapping, $updatedSmsTemplates) === true)
            {
                $merchants = $updatedSmsTemplates[$templateNameFromMapping];

                if (($merchants == "*") or
                    (in_array($merchantId, $merchants) == true))
                {
                    $templateName = $templateNameFromMapping;
                }
            }
        }

        $this->trace->info(
            TraceCode::SMS_TEMPLATE_NAME,
            [
                'template_name' => $templateName
            ]
        );

        return $templateName;
    }

    protected function updateSmsTemplate(array $input, array &$payload)
    {
        if ($input['action'] == 'create_payout_link')
        {
            $payload['template'] = 'sms.user.create_payout_link_v2';
        }
        else if ($input['action'] == 'create_bulk_payout_link')
        {
            $payload['template'] = 'sms.user.create_bulk_payout_link_v2';
        }
    }

    /**
     * Gets extra attributes in payload for stork sms request if applicable.
     *
     * @param array $input
     * @return array|string[]
     */
    protected function getExtraStorkSmsPayload(array $input): array
    {
        $payload = [];

        // Note: Existence of various key in $input is(and must be) ensured at validation layer.

        $action = $input[Entity::ACTION];

        switch ($action) {
            case Constants::CREATE_WORKFLOW_CONFIG:
                $payload += [
                    'action' => Constants::WORKFLOW_SELF_SERVE_ACTION_CREATE
                ];
                break;

            case Constants::UPDATE_WORKFLOW_CONFIG:
                $payload += [
                    'action' => Constants::WORKFLOW_SELF_SERVE_ACTION_UPDATE
                ];
                break;

            case Constants::DELETE_WORKFLOW_CONFIG:
                $payload += [
                    'action' => Constants::WORKFLOW_SELF_SERVE_ACTION_DELETE
                ];
                break;

            case Constants::BULK_APPROVE_PAYOUT:
                $payload += $this->getExtraStorkPayloadForBulkPayoutAction($input);
                break;

        }

        return $payload;
    }

    public function sendOtpWithContact(array $input, Merchant\Entity $merchant, Entity $user, array $otp = null): array
    {
        $this->trace->info(TraceCode::USERS_SEND_OTP_FOR_ACTION_WITH_CONTACT, compact('input'));

        if ($input['action'] === self::VERIFY_SUPPORT_CONTACT)
        {
            $otp = $otp ?: $this->generateOtpFromRaven($input, $merchant, $user, false);
        }
        else
        {
            $otp = $otp ?: $this->generateOtpFromRaven($input, $merchant, $user);
        }

        $payload = [
            'receiver' => $input[Entity::CONTACT_MOBILE],
            'source'   => "api.user.{$input['action']}",
            'template' => 'sms.user.' . $input[Entity::ACTION],
            'params'   => [
                'otp'      => $otp['otp'],
                'validity' => Carbon::createFromTimestamp($otp['expires_at'], Timezone::IST)->format('H:i:s'),
            ],
        ];

        $payload['params'] += $this->getExtraRavenSmsPayload($input, $merchant);

        if ($input['action'] === self::VERIFY_SUPPORT_CONTACT)
        {
            $this->app->raven->sendSms($payload, false);
        }
        else
        {
            $this->app->raven->sendSms($payload);
        }

        return array_only($otp, 'token');
    }

    /**
     * Ref: `sendOtp()`
     * Sends OTP to user's email.
     *
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @param  Entity          $user
     * @param  array|null      $otp
     *
     * @return array
     */
    public function sendOtpViaEmail(array $input, $merchant, Entity $user, array $otp = null): array
    {
        $otp = $otp ?: $this->generateOtpFromRaven($input, $merchant, $user);

        $payload = $input + $this->getExtraRavenSmsPayload($input, $merchant);

        $mailable = new OtpMail($payload, $user, $otp);

        Mail::queue($mailable);

        return array_only($otp, 'token');
    }

    /**
     * Verifies input otp against specific action(hence raven's context) i.e. verify_contact.
     * Additionally marks users.contact_mobile_verified flag as true if success.
     *
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @param  Entity          $user
     */
    public function verifyContactWithOtp(array $input, Merchant\Entity $merchant, Entity $user)
    {
        if ((isset($input[Entity::ACTION]) === true) and
            ($input[Entity::ACTION] === Constants::VERIFY_USER))
        {
            $this->verifyOtp($input, $merchant, $user);
        }

        else
        {
            $this->verifyOtp($input + ['action' => 'verify_contact'], $merchant, $user);
        }

        $user->setContactMobileVerified(true);
        $this->repo->saveOrFail($user);
    }

    public function verifyUserContactForOwnerInRbl(Entity $user)
    {
        $user->setContactMobileVerified(true);

        $this->repo->saveOrFail($user);
    }

    /**
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param Entity          $user
     * @param string          $action
     */
    public function verifyEmailWithOtp(array $input, Merchant\Entity $merchant, Entity $user, string $action = 'verify_email')
    {
        $this->verifyOtp($input + ['action' => $action], $merchant, $user);

        $this->trace->info(
            TraceCode::USER_EMAIL_VERIFY_WITH_OTP,
            [
                'merchantId'      => $merchant->getId(),
            ]);

        $this->confirm($user, Entity::OTP);

        $data = $user->toArrayPublic();

        $this->subscribeToMailingList($data);
    }

    /**
     * Verifies otp for given input(action, token & otp).
     *
     * @param array $input
     * @param Merchant\Entity $merchant
     * @param Entity $user
     * @param bool $mock
     * @return array
     */
    public function verifyOtp(array $input, $merchant, Entity $user, bool $mock = false)
    {
        $otp = $input['otp'];
        unset($input['otp']);

        $contactMobile = null;

        if (isset($input['contact_mobile']) === true)
        {
            $contactMobile = $input['contact_mobile'];

            unset($input['contact_mobile']);
        }
        //Unset OTP for logging
        $this->trace->info(TraceCode::USERS_VERIFY_OTP_FOR_ACTION, compact('input'));

        if (is_null($contactMobile) === false)
        {
            $input['contact_mobile'] = $contactMobile;
        }

        $input['otp'] = $otp;

        $payload = $this->getTokenAndRavenOtpReqParams($input, $merchant, $user);

        $payload = array_only($payload, ['context', 'receiver', 'source']) + array_only($input, 'otp');

        return $this->app->raven->verifyOtp($payload, $mock);
    }

    /**
     * Generates otp from remote raven service.
     *
     * @param array $input
     * @param Merchant\Entity $merchant
     * @param Entity $user
     * @param bool $mockInTestMode
     * @return array
     */
    protected function generateOtpFromRaven(array $input, $merchant, Entity $user, $mockInTestMode = true): array
    {
        $payload = $this->getTokenAndRavenOtpReqParams($input, $merchant, $user);

        $token = array_pull($payload, 'token');

        $otp = $this->app->raven->generateOtp($payload, $mockInTestMode);

        return $otp + compact('token');
    }

    /**
     * Gets paylaod for either raven generate or verify otp requests.
     *
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @param  Entity          $user
     * @return array
     */
    protected function getTokenAndRavenOtpReqParams(array $input, $merchant, Entity $user): array
    {
        $token = $input['token'] ?? Entity::generateUniqueId();

        if (isset($merchant) === true)
        {
            $context = $this->getContextFromAction($merchant, $user, $input, $token);
        }
        else
        {
            $context = sprintf('%s:%s:%s', $user->getId(), $input[Entity::ACTION], $token);
        }

        // Should have used api.user.{action} similar to post sms request to Raven. But in Raven otp.source is 10 char.
        $source = 'api';

        if ($input[Entity::ACTION] === 'verify_email' || $input[Entity::ACTION] === 'x_verify_email')
        {
            $expires_at = 20;

            $receiver = $user->getEmail();

            if(empty($receiver) === true)
            {
                $receiver = $input['email'] ?? null;
            }

            $response = compact(
                'token',
                'receiver',
                'context',
                'source',
                'expires_at');
        }
        else if (($input[Entity::ACTION] === 'user_auth') or
                 ((isset($input['medium']) === true) and ($input['medium'] === 'email')))
        {
            $receiver = $user->getEmail();

            $response = compact(
                'token',
                'receiver',
                'context',
                'source');
        }
        else
        {
            $receiver = $input[Entity::CONTACT_MOBILE] ?? $user->getContactMobile();

            if ((empty($receiver) === true) and
                (((isset($input['medium']) === true) and
                ($input['medium'] === 'sms_and_email')) ||
                ($input[Entity::ACTION] === 'create_payout') ||
                ($input[Entity::ACTION] === 'create_payout_link')))
            {
                $this->trace->info(TraceCode::SUCCESSFUL_OTP_GENERATION_WITHOUT_CONTACT, [
                    Entity::USER_ID => $user->getId(),
                    'input' => $input,
                ]);

                $receiver = $user->getEmail();
            }

            $response = compact(
                'token',
                'receiver',
                'context',
                'source');
        }

        return $response;
    }

    protected function getContextFromAction($merchant, $user, $input, $token)
    {
        $action = $input[Entity::ACTION];

        $context = null;

        switch ($action)
        {
            case Constants::CREATE_PAYOUT:
                $requiredParams = [Payout\Entity::AMOUNT,
                                   Payout\Entity::FUND_ACCOUNT_ID,
                                   Payout\Entity::ACCOUNT_NUMBER];

                if (empty(array_diff_key(array_flip($requiredParams), $input)) === true)
                {
                    $context = sprintf('%s:%s:%s:%s:%s:%s:%s',
                                       $merchant->getId(),
                                       $user->getId(),
                                       $action,
                                       $token,
                                       $input[Payout\Entity::AMOUNT],
                                       $input[Payout\Entity::FUND_ACCOUNT_ID],
                                       $input[Payout\Entity::ACCOUNT_NUMBER]);

                    $context = hash('sha3-512', $context);
                }
                else
                {
                    $context = $this->getDefaultContextFromActionWithMerchant($merchant, $user, $action, $token);
                }

                break;

            case Constants::CREATE_COMPOSITE_PAYOUT_WITH_OTP:
                $requiredParams = [Payout\Entity::AMOUNT,
                    Payout\Entity::VPA,
                    Payout\Entity::ACCOUNT_NUMBER];

                $action = Constants::CREATE_PAYOUT;

                if (empty(array_diff_key(array_flip($requiredParams), $input)) === true)
                {
                    $context = sprintf('%s:%s:%s:%s:%s:%s:%s',
                        $merchant->getId(),
                        $user->getId(),
                        $action,
                        $token,
                        $input[Payout\Entity::AMOUNT],
                        $input[Payout\Entity::VPA],
                        $input[Payout\Entity::ACCOUNT_NUMBER]);

                    $context = hash('sha3-512', $context);
                }
                else
                {
                    $context = $this->getDefaultContextFromActionWithMerchant($merchant, $user, $action, $token);
                }

                break;

            case Constants::APPROVE_PAYOUT:
                if (empty($input[Payout\Entity::PAYOUT_ID]) === false)
                {
                    $context = sprintf('%s:%s:%s:%s:%s',
                                       $merchant->getId(),
                                       $user->getId(),
                                       $action,
                                       $token,
                                       $input[Payout\Entity::PAYOUT_ID]);

                    $context = hash('sha3-512', $context);
                }
                else
                {
                    $context = $this->getDefaultContextFromActionWithMerchant($merchant, $user, $action, $token);
                }

                break;

            case Constants::CREATE_PAYOUT_LINK:
                $context = $this->getContextFromActionForPayoutLinkCreation($merchant, $user, $input, $token);
                break;

            case Constants::IP_WHITELIST:
                $context = sprintf('%s:%s:%s:%s:%s',
                    $merchant->getId(),
                    $user->getId(),
                    $action,
                    $token,
                    json_encode($input['whitelisted_ips']));

                $context = hash('sha3-512', $context);

                break;

            case Constants::CREATE_PAYOUT_BATCH_V2:
                $requiredParams = [Payout\Entity::AMOUNT,
                    Constants::BATCH_FILE_ID,
                    Payout\Entity::ACCOUNT_NUMBER];

                if (empty(array_diff_key(array_flip($requiredParams), $input)) === true)
                {
                    $context = sprintf('%s:%s:%s:%s:%s:%s:%s',
                        $merchant->getId(),
                        $user->getId(),
                        $action,
                        $token,
                        $input[Payout\Entity::AMOUNT],
                        $input[Constants::BATCH_FILE_ID],
                        $input[Payout\Entity::ACCOUNT_NUMBER]);

                    $context = hash('sha3-512', $context);
                }
                else
                {
                    $context = $this->getDefaultContextFromActionWithMerchant($merchant, $user, $action, $token);
                }

                break;

            default:
                // Fallback to default context
                $context = $this->getDefaultContextFromActionWithMerchant($merchant, $user, $action, $token);
        }

        return $context;
    }

    protected function getDefaultContextFromActionWithMerchant($merchant, $user, $action, $token)
    {
        return sprintf('%s:%s:%s:%s', $merchant->getId(), $user->getId(), $action, $token);
    }

    protected function upsertSettings(Entity $user, array $settings)
    {
        if (empty($settings) === false)
        {
            $user->getSettingsAccessor()->upsert($settings)->save();
        }
    }

    /**
     * Gets extra attributes in payload for raven sms request if applicable. E.g. for payout
     * we send related fund account details, contact name etc in mail and sms otp content.
     *
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @return
     */
    protected function getExtraRavenSmsPayload(array $input, $merchant)
    {
        $payload = [];

        // Note: Existence of various key in $input is(and must be) ensured at validation layer.

        $action = $input[Entity::ACTION];

        if ($action === 'create_payout')
        {
            $payload += [
                'amount'         => amount_format_IN($input['amount']),
                'account_number' => mask_except_last4($input['account_number']),
                'purpose'        => $input['purpose'],
            ];

            // Gets fund account entity and appends few more contact details in payload.
            $fa = $this->repo->fund_account->findByPublicIdAndMerchant($input['fund_account_id'], $merchant);
            $payload += [
                'contact'             => $fa->contact->toArrayPublic(),
                'account_destination' => $fa->getAccountDestinationAsText(),
                'account_type'        => $fa->getAccountTypeAsText(),
            ];
        }
        else if ($action === Constants::CREATE_COMPOSITE_PAYOUT_WITH_OTP)
        {
            $payload += [
                'amount'         => amount_format_IN($input['amount']),
                'account_number' => mask_except_last4($input['account_number']),
                'purpose'        => $input['purpose'],
            ];
        }
        else if ($action === 'sub_virtual_account_transfer')
        {
            $payload += [
                'amount'         => amount_format_IN($input['amount']),
                'account_number' => mask_except_last4($input['master_account_number']),
            ];
        }
        else if ($action === 'create_payout_batch')
        {
            //HACK : TODO Remove this after migrating all to create_payout_batch with total_payout_amount
            if (array_key_exists('total_payout_amount', $input)) {
                $payload += [
                    'account_number'      => mask_except_last4($input['account_number']),
                    'total_payout_amount' => amount_format_IN($input['total_payout_amount']),
                ];
            } else {
                //Setting total_payout_amount to -1 to allow for raven to use the old OTP template
                $payload += [
                    'account_number'      => mask_except_last4($input['account_number']),
                    'total_payout_amount' => -1
                ];
            }
        }
        else if ($action === 'approve_payout')
        {
            $payload += [
                'amount'         => amount_format_IN($input['amount']),
                'account_number' => mask_except_last4($input['account_number']),
                'payout_id'      => $input['payout_id'],
            ];
        }
        else if ($action === 'approve_payout_bulk')
        {
            $payload += [
                'payout_total_amount' => amount_format_IN($input['payout_total_amount']),
                'payout_count'        => $input['payout_count'],
                'account_number'      => mask_except_last4($input['account_number']),
            ];
        }
        else if ($action === 'create_payout_link')
        {
            $payload += [
                'amount'         => amount_format_IN($input['amount']),
                'account_number' => mask_except_last4($input['account_number']),
                'purpose'        => $input['purpose'],
            ];
        }
        else if ($action === 'bulk_payout_approve') {
            $payload += [
                'approved_payout_count'  => $input['approved_payout_count'],
                'approved_payout_amount' => $input['approved_payout_amount'],
                'rejected_payout_count'  => $input['rejected_payout_count'],
                'rejected_payout_amount' => $input['rejected_payout_amount'],
            ];
        }
        else if ($action === 'create_bulk_payout_link')
        {
            $payload += [
                'total_payout_link_amount'  => amount_format_IN($input['total_payout_link_amount']),
                'account_number'            => mask_except_last4($input['account_number']),
            ];
        }
        else if ($action === Constants::CREATE_WORKFLOW_CONFIG)
        {
            $payload += [
                'workflow_action'  => Constants::WORKFLOW_SELF_SERVE_ACTION_CREATE,
            ];
        }
        else if ($action === Constants::UPDATE_WORKFLOW_CONFIG)
        {
            $payload += [
                'workflow_action'  => Constants::WORKFLOW_SELF_SERVE_ACTION_UPDATE,
            ];
        }
        else if ($action === Constants::DELETE_WORKFLOW_CONFIG)
        {
            $payload += [
                'workflow_action'  => Constants::WORKFLOW_SELF_SERVE_ACTION_DELETE,
            ];
        }
        else if ($action === Constants::CREATE_PAYOUT_BATCH_V2)
        {
            if (array_key_exists('total_payout_amount', $input)) {
                $payload += [
                    'account_number'      => mask_except_last4($input['account_number']),
                    'total_payout_amount' => amount_format_IN($input['total_payout_amount']),
                ];
            } else {
                //Setting total_payout_amount to -1 to allow for raven to use the old OTP template
                $payload += [
                    'account_number'      => mask_except_last4($input['account_number']),
                    'total_payout_amount' => -1
                ];
            }
        }

        return $payload;
    }

    /**
     * Add default stork payload parameters and parameters than are generated during runtime
     *
     * @param  array            $input
     * @param  ?Merchant\Entity $merchant
     * @param  Entity           $user
     * @return array
     */
    public function generateStorkSmsPayload(array $input, ?Merchant\Entity $merchant,Entity $user): array
    {
        if (is_null($merchant) === true)
        {
            $orgId = $this->app['basicauth']->getOrgId();
            // Stork only support merchant and application ownerType hence sending userId
            // since merchant can be null in some flows
            $ownerId = $user->getId();

            $mid = '10000000000000';
        }
        else
        {
            $orgId = $merchant->getOrgId();

            $ownerId = $merchant->getId();

            $mid = $merchant->getId();
        }

        $smsPayload = [
            'ownerId'               => $ownerId,
            'ownerType'             => 'merchant',
            'templateName'          => 'sms.user.' . $input[Entity::ACTION],
            'templateNamespace'     => '',
            'orgId'                 => $orgId,
            'destination'           => $user->getContactMobile(),
            'sender'                => 'RZRPAY',
            'language'              => 'english',
            'contentParams'         => [
            ],
        ];

        switch ($input[Entity::ACTION])
        {
            case Constants::X_LOGIN_OTP_ACTION:
            case Constants::X_VERIFY_USER_ACTION:
                $smsPayload[Constants::THROW_SMS_EXCEPTION_IN_STORK] = true;
                $smsPayload['templateNamespace'] = 'razorpayx_acquisition';
                break;

            case Constants::X_SECOND_FACTOR_AUTH_ACTION:
                $smsPayload['templateNamespace'] = 'razorpayx_acquisition';
                break;

            case Constants::BULK_APPROVE_PAYOUT:
                $this->populateTemplateMetaForBulkPayoutAction($input, $smsPayload, $mid);
                break;

            case Constants::CREATE_WORKFLOW_CONFIG:
            case Constants::UPDATE_WORKFLOW_CONFIG:
            case Constants::DELETE_WORKFLOW_CONFIG:
                $smsPayload['sender'] = 'RZPAYX';
                $smsPayload['templateName'] = 'sms.user.otp_workflow_config_v2';
                $smsPayload['templateNamespace'] = 'razorpayx_neobanking';
                break;
        }

        return $smsPayload;
    }

    /**
     * @param Entity $user
     * @param array  $input
     */
    public function setNewPassword(Entity $user, array $input)
    {
        $changePasswordData = [
            'password'              => $input['password'],
            'password_confirmation' => $input['password_confirmation'],
        ];

        $this->changePassword($user, $changePasswordData);
    }

    /**
     * For checking user has set password two conditions are considered :
     *  1. Mobile signup user who has not set the password yet.
     *  2. User has signup up via Google authentication in that case password is not present in Rzp-org db.
     *
     * @param Entity $user
     *
     */
    public function checkUserHasSetPassword(Entity $user)
    {
        $this->trace->info(TraceCode::USER_CHECK_HAS_PASSWORD_ACTION, [
            Entity::USER_ID => $user->getId()
        ]);

        $setPassword = (($user->getPassword() !== null) or ($user->isSignupViaEmail() === true));

        return [Constants::SET_PASSWORD => $setPassword];
    }

    /**
     * @param Entity $user
     * @param array  $input
     */
    public function setUserPassword(Entity $user, array $input)
    {
        $this->trace->info(TraceCode::USER_SET_PASSWORD_ACTION, [
            Entity::USER_ID => $user->getId()
        ]);

        $user->fill($input);

        $this->repo->saveOrFail($user);

        return $user->toArrayPublic();
    }

    /**
     * @param Entity $user
     * @param array $input
     */
    public function patchUserPassword(Entity $user, array $input)
    {
        $this->trace->info(TraceCode::USER_SET_PASSWORD_ACTION, [
            Entity::USER_ID => $user->getId()
        ]);

        $user->fill($input);

        $this->repo->saveOrFail($user);

        return $this->get($user);
    }


    /**
     *  User sending otp to update his contact mobile
     *  Send OTP to new mobile number.
     *
     * @param array  $input
     * @param Entity $user
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function sendOtpForContactMobileUpdate(array $input, Entity $user)
    {
        if ($user->getRestricted() === true)
        {
            //
            // if merchant_user role is admin/owner
            // allow editing contact mobile.
            //
            $userMapping    = $this->repo->merchant->getMerchantUserMapping($this->merchant->getId(),
                                                                            $user->getId());
            $this->userRole = $userMapping->pivot->role;

            if (in_array($this->userRole, [Role::ADMIN, Role::OWNER], true) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_RESTRICTED_USER_CANNOT_PERFORM_ACTION);
            }
        }

        $smsOtpAuth = $this->app['module']->secondFactorAuth::make('SmsOtpAuth');

        $smsOtpAuthPayload = $this->getSmsOtpAuthBasePayload($user, $input);

        $smsOtpAuth->sendOtp($smsOtpAuthPayload);

        $this->trace->info(TraceCode::USER_CONTACT_MOBILE_UPDATE, [
            mask_phone($input[Entity::CONTACT_MOBILE])
        ]);

        return $user;
    }

    /**
     *  User updating its contact mobile
     *
     *  1) Send OTP to mobile number.
     *  2) Verify OTP send to the number.
     *  3) Update the contact mobile and set mobile verified as true.
     *
     * @param array  $input
     * @param Entity $user
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function editContactMobile(array $input, Entity $user)
    {
        if ($user->getRestricted() === true)
        {
            //
            // if merchant_user role is admin/owner
            // allow editing contact mobile.
            //
            $userMapping    = $this->repo->merchant->getMerchantUserMapping($this->merchant->getId(),
                                                                            $user->getId());
            $this->userRole = $userMapping->pivot->role;

            if (in_array($this->userRole, [Role::ADMIN, Role::OWNER], true) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_RESTRICTED_USER_CANNOT_PERFORM_ACTION);
            }
        }

        $smsOtpAuth = $this->app['module']->secondFactorAuth::make('SmsOtpAuth');

        $smsOtpAuthPayload = $this->getSmsOtpAuthBasePayload($user, $input);

        $smsOtpAuth->sendOtp($smsOtpAuthPayload);

        $user->setContactMobile($input[Entity::CONTACT_MOBILE]);

        $this->repo->saveOrFail($user);

        $this->notifyUserAboutContactMobileUpdate($user, $this->merchant);

        return $user;
    }

    /**
     * update contact mobile of a user using userId
     *
     * @param array  $input
     *
     * @param Entity $user
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function updateContactMobile(array $input, Entity $user): Entity
    {
        // for admin contact_mobile_verified is set to false

        if (app('basicauth')->isAdminAuth() === true)
        {
            $contactMobileVerified = false;

            // https://docs.google.com/document/d/1Y0S7g3HvPPeo-xpUAzZ4bxKopeWlSbusjRZlW6KMvUc/edit?pli=1#
            $autoVerifyContact = $this->userHasFeatureOnAllMerchantsOrgs(FeatureConstant::ORG_CONTACT_VERIFY_DEFAULT, $user);

            if($autoVerifyContact === true)
            {
                $contactMobileVerified = true;
            }

            $this->trace->info(
                TraceCode::USER_CONTACT_MOBILE_UPDATE,
                [
                    Entity::USER_ID        => $user->getId(),
                    Entity::CONTACT_MOBILE => $input[Entity::CONTACT_MOBILE],
                    'admin_id'             => $this->app['basicauth']->getAdmin()->getId(),
                    'autoVerifyContact'    => $autoVerifyContact,
                ]);
        }
        else
        {
            // Refer: https://razorpay.atlassian.net/browse/SBB-1035
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                "Action only permitted for admin users."
            );
        }

        $user->setContactMobile($input[Entity::CONTACT_MOBILE]);

        $this->repo->saveOrFail($user);

        $user->setContactMobileVerified($contactMobileVerified);

        $this->repo->saveOrFail($user);

        return $user;
    }

    /**
     *  This function checks if
     *  1) user is associated with merchant
     *  2) merchant whose updating user details should have owner/admin role
     *
     * @param Merchant\Entity $merchant
     * @param Entity          $user
     *
     * @throws Exception\BadRequestException
     */
    protected function canMerchantUpdateUserDetails(Merchant\Entity $merchant, Entity $user)
    {
        // check if user belongs to same merchant.
        $user->getValidator()->validateMerchantUserRelation($merchant, $user);

        $dashboardUser = app('basicauth')->getUser();

        $userMapping = $this->repo->merchant->getMerchantUserMapping($merchant->getId(), $dashboardUser->getId());

        $this->userRole = $userMapping->pivot->role;

        // check if the userRole is only admin/owner.
        (new Role())->validateMerchantUserRoleForUpdateUserDetails($this->userRole);
    }

    /**
     * This functions checks if all the merchants for this user have a feature flag enabled
     *
     * @param string $feature
     * @param Entity $user
     *
     */
    protected function userHasFeatureOnAllMerchantsOrgs(string $feature, Entity $user)
    {
        $mids = $this->repo->merchant_user->returnMerchantIdsForUserId($user->getId());

        if(count($mids) === 0)
        {
            return false;
        }

        foreach ($mids as $mid)
        {
            $merchant = $this->repo->merchant->findOrFail($mid);

            if($merchant->org->isFeatureEnabled($feature) === false)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Prepares unified
     * @param Array       $merchants
     */
    protected function getUnifiedMerchants($merchants): array
    {
        $merchantsUnique = [];

        array_walk($merchants, function ($merchant) use (& $merchantsUnique)
        {
            $id = $merchant[Entity::ID];
            $role = $merchant[Entity::ROLE];

            if(isset($merchantsUnique[$id]) === false)
            {
                $merchantsUnique[$id]                       = $merchant;
                $merchantsUnique[$id][Entity::BANKING_ROLE] = null;
                $merchantsUnique[$id][Entity::ROLE]         = null;
            }

            // Push pivot's role to one of the keys in response basis product type.
            $key = $merchant[Entity::PRODUCT] === Product::BANKING ? Entity::BANKING_ROLE : Entity::ROLE;
            $merchantsUnique[$id][$key] = $role;

            if($merchant[Entity::PRODUCT] === Product::BANKING)
            {
                $merchantsUnique[$id][Entity::BANKING_ROLE_NAME] =
                    $this->repo->roles->fetchRoleName($merchantsUnique[$id][$key]);
            }

        });

        return array_values($merchantsUnique);
    }

    /**
     * This function checks if
     * the current user has an access on a certain merchant
     *
     * @param ser\Entity $user
     * @param String      $merchantId
     * @param String      $product
     * @throws Exception\BadRequestException
     *
     *
     */
    public function checkAccessForMerchant(Entity $user, $merchantId, $product)
    {
        $merchants = $user->belongsToMany(Merchant\Entity::class, Table::MERCHANT_USERS)
                        ->withPivot([Entity::ROLE, Entity::PRODUCT])
                        ->where(Merchant\Entity::ID,$merchantId)
                        ->get()
                        ->callOnEveryItem('toArrayUser');

        // this is to verify if user has access to merchant
        // for the given product
        $merchantForCurrentProduct = array_filter(
            $merchants,
            function ($entity) use ($product) {
                return $entity[Entity::PRODUCT] === $product;
            });

        if(empty($merchantForCurrentProduct) === true)
        {

            $this->trace->count(Metric::DASHBOARD_SWITCH_FAILURE_TOTAL , []);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID,
                null,
                [
                    Entity::USER_ID      => $user->getId(),
                    Entity::MERCHANT_ID  => $merchantId,
                    Entity::PRODUCT      => $product,
                ]
            );
        }

        $merchants = $this->getUnifiedMerchants($merchants);

        $merchants = $this->appendBankingSpecificDetails($merchants);

        $this->trace->count(Metric::DASHBOARD_SWITCH_SUCCESS_TOTAL , []);

        return [
            // just to maintain backward compatibility
            // sending access key
            // dashboard application determines the access currently
            // based value of access key being true or false
            'access'   => true,
            'merchant' => $merchants[0],
        ];
    }

    /**
     * Tracking Onboarding event along with User Email.
     *
     * @param string         $userEmail
     * @param array          $eventCode
     * @param Throwable|null $ex
     */
    public function trackOnboardingEvent(string $userEmail, array $eventCode, Throwable $ex = null)
    {
        $customProperties = ['email' => $userEmail];

        $this->app['diag']->trackOnboardingEvent($eventCode, $this->merchant, $ex, $customProperties);
    }

    public function trackOnboardingEventByContactMobile(string $userMobile, array $eventCode, Throwable $ex = null)
    {
        $customProperties = ['contact_mobile' => $userMobile];

        $this->app['diag']->trackOnboardingEvent($eventCode, $this->merchant, $ex, $customProperties);
    }


    //Verify user through otp sent to the provided Email
    public function verifyUserThroughEmail(array $input, Merchant\Entity $merchant, Entity $user): array
    {
        $input[Entity::MEDIUM] = 'email';

        $input[Entity::ACTION] = 'user_auth';

        return $this->verifyUserThroughMode($input, $merchant, $user);
    }

    /**
     * Verify user through otp sent to the provided mode.
     * Generates and stores a user verification token in redis.
     * This token has to be passed in subsequent calls which need user authorization.
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param Entity          $user
     *
     * @return array
     */
    public function verifyUserThroughMode(array $input, Merchant\Entity $merchant, Entity $user): array
    {
        /** @var Validator $validator */
        $validator = $user->getValidator();

        $validator->validateInput('verify_user_through_mode', $input);

        $this->verifyOtp($input, $merchant, $user);

        /** @var TokenService $tokenService */
        $tokenService = $this->app['token_service'];

        $token = $tokenService->generate($user->getId());

        return [Entity::OTP_AUTH_TOKEN => $token];
    }

    private function notifyUserAboutContactMobileUpdate(Entity $user, Merchant\Entity $merchant)
    {
        $userUpdatedAt = Carbon::createFromTimestamp($user->getUpdatedAt(), Timezone::IST);

        $data = [
            'user'          => [
                Entity::EMAIL               => $user->getEmail(),
                Entity::NAME                => $user->getName(),
                Entity::CONTACT_MOBILE      => $user->getMaskedContactMobile(),
                'updated_on'                => $userUpdatedAt->format('jS M, Y'),
                Entity::UPDATED_AT          => $userUpdatedAt->format('g:i A'),
            ],

            'merchant'      => [
                Entity::ID                  => $merchant->getId(),
                Entity::NAME                => $merchant->getName(),
            ],
        ];

        $email = new ContactMobileUpdatedMail($data);

        Mail::send($email);
    }

    private function notifyUserAboutAccountLocked(Entity $user)
    {
        $orgId = $this->app['basicauth']->getOrgId();

        $org = $this->repo->org->findByPublicId($orgId);

        // feature at org level in feature entity
        $showAxisSupportUrl = $org->isFeatureEnabled(FeatureConstant::SHOW_SUPPORT_URL);

        $data = [
            'showAxisSupportUrl' => $showAxisSupportUrl,
            'user'  => [
                Entity::ID              => $user->getId(),
                Entity::EMAIL           => $user->getEmail(),
                Entity::NAME            => $user->getName(),
            ],
        ];

        $email = new AccountLockedWrongAttemptMail($data);

        Mail::send($email);
    }

    /**
     * Fetch role id for a particular user by fetching through merchant_users table for the
     * product banking. Note that only one id should and will be returned.
     * This function fetches a user's role id through the merchant_users table,
     * since the role_map table being used earlier didn't have merchant context.
     *
     * @param string $userId
     * @return array
     * @throws Exception\UserWorkflowNotApplicableException
     */
    public function getUserRoleIdInMerchantForWorkflow(string $userId, string $merchantId = null) : array
    {
        if (empty($merchantId) === true)
        {
            $merchantId = $this->merchant->getId();
        }

        $mapping = $this->repo->merchant->getMerchantUserMapping($merchantId,
            $userId,
            null,
            'banking'
        );

        if (empty($mapping) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_DOES_NOT_BELONG_TO_MERCHANT);
        }

        $roleCode = $mapping->pivot->role;

        $roleNamesArray = [];

        if (BankingRole::isWorkflowRole($roleCode) === false)
        {
            throw new Exception\UserWorkflowNotApplicableException($roleCode);
        }

        $roleNamesArray []= (new BankingRole())->getNameForWorkflowRole($roleCode);

        $roleId = $this->repo->role->fetchIdsByOrgIdNames(Org\Entity::RAZORPAY_ORG_ID,
                                                          $roleNamesArray)
                                                          ->pluck('id')
                                                          ->toArray();

        return $roleId;
    }

    // returns user entity required for unit testing to mock the entity
    public function getUserEntity() : Entity
    {
        return new Entity();
    }

    protected function getBulkPayoutsUserType($balance)
    {
        $payoutAmountType = (new Payout\Service)->getAmountTypeForPayouts($balance->merchant);

        if ($payoutAmountType === Payout\Entity::PAISE)
        {
            return Payout\Entity::EXISTING_BULK_USER_PAISE;
        }
        if ($payoutAmountType === Payout\Entity::RUPEES)
        {
            return Payout\Entity::EXISTING_BULK_USER_RUPEES;
        }
        else
        {
            $newUserCutoffTimeStamp = (new Admin\Service)->getConfigKey(
                [
                    'key' => Admin\ConfigKey::BULK_PAYOUTS_NEW_MERCHANT_CUTOFF_TIMESTAMP
                ]);

            if ($balance->getCreatedAt() > $newUserCutoffTimeStamp)
            {
                return Payout\Entity::NEW_USER;
            }
            else
            {
                return Payout\Entity::EXISTING_NON_BULK_USER;
            }
        }
    }

    /**
     * it'll fetch the user details given the user email id
     * user details contains
     * - basic user information
     * - all primary merchant accounts associated with the user
     * - business specific details
     *
     * @param array $input
     * @return array
     */
    public function getDetails(array $input): array
    {
        $user = $this->repo
                     ->user
                     ->getUserFromEmailOrFail($input['email']);

        $merchantDetails = $this->getMerchantDetails($user);

        return [
            'user_id'                 => $user->getId(),
            'name'                    => $user->getName(),
            'email'                   => $user->getEmail(),
            'contact_mobile'          => $user->getContactMobile(),
            'contact_mobile_verified' => $user->isContactMobileVerified(),
            'account_locked'          => $user->isAccountLocked(),
            'confirmed'               => $user->confirmed,
            'merchants'               => $merchantDetails,
        ];
    }

    public function getDetailsUnified(array $input): array
    {
        $user = $this->repo
                     ->user
                     ->getUserFromEmailOrFail($input['email']);

        $merchantEntities = $user->merchants()->where(Merchant\Entity::SUSPENDED_AT, null)->take(1000)->get();

        $merchants = $merchantEntities->callOnEveryItem('toArrayUser');

        $merchantDetails = $this->getUnifiedMerchants($merchants);

        return [
            'id'                      => $user->getId(),
            'name'                    => $user->getName(),
            'email'                   => $user->getEmail(),
            'merchants'               => $merchantDetails,
        ];
    }

    public function getUserAllRoles($userID, $merchantID)
    {
        $products   = [Product::BANKING, Product::PRIMARY];

        return $this->getUserRoles($userID, $merchantID, $products);
    }

    protected function getUserRoles(string $userID, string $merchantID, array $products)
    {
        $result = [];
        $roles = $this->repo->merchant_user->getMerchantUserRoles($userID, $merchantID);
        foreach ($products as $product)
        {
            $result = [
                $product => [],
            ];
        }

        foreach($roles as $role) {
            if(in_array($role->product, $products) === true)
            {
                $result[$role->product][] = $role->getRole();
            }
        }

        return $result;
    }

    /**
     * it'll collect all the accounts (pg + banking accounts) associated with the user
     * along with their business details
     *
     * we will be adding first merchant who is associated with the user (product requirement)
     *
     * @param Entity $user
     * @return array
     */
    protected function getMerchantDetails(Entity $user): array
    {
        $merchant = $user->merchants()->first();

        // if there is no merchant details then return empty result
        if ($merchant === NULL)
        {
            return [];
        }

        $merchantDetails = [
            'gstin'           => NULL,
            'pan'             => NULL,
            'billing_address' => NULL,
            'description'     => NULL,
            'iec_code'        => NULL,
            'purpose_code'    => NULL,
            'purpose_code_desc' => NULL,
        ];

        $details = $merchant->merchantDetail;

        // update merchant details if there is data
        if ($details !== NULL)
        {
            $merchantDetails = [
                'gstin'             => $details->getGstin(),
                'pan'               => $details->getPan(),
                'billing_address'   => $details->getBusinessAddress(),
                'description'       => $details->getBusinessDescription(),
                'iec_code'          => $details->getIecCode(),
            ];
        }

        $merchantDetails += [
            'id'                => $merchant->getId(),
            'activated'         => $merchant->isActivated(),
            'website'           => $merchant->getWebsite(),
            'name'              => $merchant->getName(),
            'billing_label'     => $merchant->getBillingLabelNotName(),
            'purpose_code'      => $merchant->getPurposeCode(),
            'purpose_code_desc' => $merchant->getPurposeCodeDescription(),
        ];

        return [$merchantDetails];
    }

    /**
     * To Fire Event if User clicks on not now button for current account
     * via neoStone flow
     * @param Entity $user
     * @param array  $input
     */
    private function fireHubspotIfUserClickedNotNow(Entity $user, array $input)
    {
        if (isset($input[Entity::SETTINGS]) === true)
        {
            $settings = $input[Entity::SETTINGS];

            if (array_key_exists('clicked_rbl_self_serve_not_now', $settings) === true)
            {
                if ($settings['clicked_rbl_self_serve_not_now'] === '1')
                {
                    $merchant = $user->getMerchantEntity();

                    $merchantEmail = $merchant->getEmail();

                    $payload = ['ca_clicked_not_now_at' => 'TRUE'];

                    $this->trace->info(
                        TraceCode::NEOSTONE_HUBSPOT_REQUEST,
                        [
                            $payload
                        ]);

                    $this->app->hubspot->trackHubspotEvent($merchantEmail, $payload);
                }
            }
        }
    }

    public function removeIncorrectPasswordCount(array $emails)
    {
        $success = [];
        $failed  = [];

        foreach ($emails as $email)
        {
            if ($this->delIncorrectPasswordCount($email) === 1)
            {
                $success[] = $email;
            }
            else
            {
                $failed[] = $email;
            }
        }

        $summary = [
            'failed_count'  => count($failed),
            'success_count' => count($success),
            'success'       => $success,
            'failed'        => $failed,
        ];

        $this->trace->info(TraceCode::ADMIN_REMOVE_INCORRECT_PASSWORD_COUNT_SUMMARY, $summary);

        return $summary;
    }

    /**
     * @throws ServerErrorException
     */
    public function sendXMobileAppDownloadLinkSms(array $input, $merchant)
    {
        $smsPayload = [
            'ownerId'               => $merchant->getId(),
            'ownerType'             => 'merchant',
            'templateName'          => 'sms.user.x-app_download',
            'templateNamespace'     => 'razorpayx_apps',
            'orgId'                 => $merchant->getOrgId(),
            'destination'           => $input['contact_number'],
            'sender'                => 'RZPAYX',
            'language'              => 'english',
            'contentParams'         => [
                'app_link'  => 'https://bit.ly/RX-APP',
            ],
        ];

        /** @var $stork \RZP\Services\Stork */
        $stork = $this->app['stork_service'];

        $storkResponse = $stork->sendSms($this->mode,$smsPayload);

        if (empty($storkResponse))
        {
            throw new ServerErrorException('Failed to send SMS',
                ErrorCode::SERVER_ERROR_USER_X_MOBILE_APP_DOWNLOAD_LINK_SENDING_FAILED,
                [
                    'merchant_id' => $merchant->getMerchantId(),
                ]
            );
        }

        $response = [
            'sms_id' => $storkResponse[$stork::MESSAGE_ID]
        ];

        $this->trace->info(TraceCode::USER_X_MOBILE_APP_DOWNLOAD_LINK, $response);

        return $response;
    }

    /**
     * @param string $email
     * @param Entity $user
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     * @throws ServerErrorException
     */
    protected function validateAddEmailAllowed(string $email, Entity $user)
    {
        $userMapping = $this->repo->merchant->getMerchantUserMapping($this->merchant->getId(), $user->getId());
        $userRole = $userMapping->pivot->role;

        if (($userRole === Role::OWNER) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_RESTRICTED_USER_CANNOT_PERFORM_ACTION);
        }

        if($user->getEmail() !== NULL)
        {
            throw new BadRequestValidationFailureException('Email is already present.');
        }

        if ($this->checkIfEmailAlreadyExists($email)) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_EMAIL_ALREADY_EXISTS
            );
        }
    }

    /**
     * @param array $input
     * @param Entity $user
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException|Exception\ServerErrorException
     */
    public function sendOtpForAddEmail(array $input, Entity $user)
    {
        $this->validateAddEmailAllowed($input[Entity::EMAIL], $user);
        $user->setEmail($input[Entity::EMAIL]);
        $input["token"] = $input[Entity::EMAIL];
        $this->sendVerificationOtpViaEmail($input, $user);
    }

    /**
     * @param array $input
     * @param Entity $user
     * @return Entity
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     * @throws Exception\ServerErrorException|Throwable
     */
    public function verifyOtpForAddEmail(array $input, Entity $user): Entity
    {
        $this->validateAddEmailAllowed($input[Entity::EMAIL], $user);

        $email = $input[Entity::EMAIL];
        $user->setEmail($email);
        $input["token"] = $email;
        $this->checkVerifyOtpVerificationLimitExceeded($email, Constants::EMAIL, $user);

        $input = array_merge($input, $this->getLoginSignupOtpPayload($input, Constants::VERIFY_USER_ACTION));

        $this->verifyLoginSignupOtp($email, $input, $user->getId());

        if (isset($input[Entity::EMAIL]))
        {
            LoginSignupRateLimit::resetKey($email, Constants::SEND_EMAIL_OTP_VERIFICATION_RATE_LIMIT_SUFFIX);
        }

        LoginSignupRateLimit::resetKey($email, Constants::VERIFY_OTP_VERIFICATION_RATE_LIMIT_SUFFIX);

        $merchant = $this->merchant;
        $merchant_detail = $merchant->merchantDetail()->first();

        $this->repo->transactionOnLiveAndTest(function() use ($user, $merchant, $merchant_detail, $email)
        {
            $user->setEmail($email);
            $user->setConfirmTokenNull();
            $this->repo->saveOrFail($user);

            $merchant->setEmail($email);
            $this->repo->saveOrFail($merchant);

            $merchant_detail->setContactEmail($email);
            $this->repo->saveOrFail($merchant_detail);
        });

        return $user;

    }

    public function getUserByVerifiedContact(array $input) {
        $response = null;
        $user = null;

        if (isset($input[Entity::CONTACT_MOBILE])) {
            $mobile = $input[Entity::CONTACT_MOBILE];

            $user = $this->repo->user->getUserFromMobileOrFail($mobile);
            $user = $this->isMobileVerified($user);
        }

        if ($user) {
            $merchantEntities = $user->merchants()->where(
                Merchant\Entity::SUSPENDED_AT, null
            )->take(1000)->get();

            $merchants = $merchantEntities->callOnEveryItem('toArrayUser');
            $merchantDetails = $this->getUnifiedMerchants($merchants);

            $response = $user->toArrayPublic();
            $response['merchants'] = $merchantDetails;
        }

        return $response;
    }

    /**
     * @param Entity $user
     * @return bool
     */
    protected function isBankingDemoAccount(Entity $user): bool
    {
        // Email is used instead of ID as email can be same across ENVs
        return in_array($user->getEmail(),Constants::BANKING_DEMO_USER_EMAILS,true);
    }

    /**
     * it'll fetch the user details given the user email id
     * - purpose code and it's description
     * - all primary merchant accounts associated with the user
     * - IEC code for the merchant.
     * @param string $email
     * @return array
     */
    public function getInternationalDetails($email): array
    {
        $user = $this->repo
            ->user
            ->getUserFromEmailOrFail($email);

        $merchantDetails = $this->getMerchantDetails($user);

        return [
            'user_id'                 => $user->getId(),
            'name'                    => $user->getName(),
            'email'                   => $user->getEmail(),
            'contact_mobile'          => $user->getContactMobile(),
            'contact_mobile_verified' => $user->isContactMobileVerified(),
            'account_locked'          => $user->isAccountLocked(),
            'confirmed'               => $user->confirmed,
            'merchants'               => $merchantDetails,
        ];
    }

    public function fetchSubmerchantUser(string $submerchantId)
    {
        $merchantUsers = $this->repo->merchant_user->fetchPrimaryUserIdForMerchantIdAndRole($submerchantId,'owner');

        $partner = $this->repo->merchant->getPartnerMerchantFromSubMerchantId($submerchantId);

        $partnerUser = $this->repo->merchant_user->fetchPrimaryUserIdForMerchantIdAndRole($partner->getId(), 'owner');

        $merchantUser = array_diff($merchantUsers, $partnerUser);

        $userId = array_first($merchantUser);

        $user = $this->repo->user->findOrFailPublic($userId);

        $this->trace->info(TraceCode::USER_MAPPED_TO_THE_SUBMERCHANT,[
            'user' => $user
        ]);

        return $user;
    }

    public function updateContactNumberForSubMerchantUser(string $submerchantId,string $contact)
    {
        $user = $this->fetchSubmerchantUser($submerchantId);

        $this->repo->transactionOnLiveAndTest(function() use ($user, $contact)
        {
            $user->setContactMobile($contact);

            $user->setContactMobileVerified(true);

            $this->repo->saveOrFail($user);
        });

        return $user;
    }

    public function pushSelfServeSuccessEventsToSegment()
    {
        $segmentProperties = [];

        $segmentEventName = SegmentEvent::SELF_SERVE_SUCCESS;

        $segmentProperties[SegmentConstants::OBJECT] = SegmentConstants::SELF_SERVE;

        $segmentProperties[SegmentConstants::ACTION] = SegmentConstants::SUCCESS;

        $segmentProperties[SegmentConstants::SOURCE] = SegmentConstants::BE;

        return [$segmentEventName, $segmentProperties];
    }

    protected function getExtraStorkPayloadForBulkPayoutAction(array $input): array
    {
        $contentParams = array();

        $approvedPayoutCount = array_pull($input, 'approved_payout_count', 0);

        $rejectedPayoutCount = array_pull($input, 'rejected_payout_count', 0);

        if ($approvedPayoutCount > 0)
        {
            $contentParams += [
                'approved_payout_count'     => $approvedPayoutCount,
                'approved_payout_amount'    => $input['approved_payout_amount'],
            ];
        }
        if ($rejectedPayoutCount > 0)
        {
            $contentParams += [
                'rejected_payout_count'     => $rejectedPayoutCount,
                'rejected_payout_amount'    => $input['rejected_payout_amount'],
            ];
        }

        return $contentParams;
    }

    protected function populateTemplateMetaForBulkPayoutAction(array $input, array &$smsPayload, $merchantId)
    {
        $updatedSmsTemplates = (new Admin\Service)->getConfigKey(['key' => ConfigKey::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS]);

        $this->trace->info(
            TraceCode::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS_CONFIG_KEY,
            [
                ConfigKey::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS => $updatedSmsTemplates
            ]
        );

        $approvedPayoutCount = array_pull($input, 'approved_payout_count', 0);

        $rejectedPayoutCount = array_pull($input, 'rejected_payout_count', 0);

        if (($approvedPayoutCount > 0) and ($rejectedPayoutCount == 0))
        {
            $templateName = 'Sms.User.Bulk_payouts_approve';

            if (array_key_exists('Sms.User.Bulk_payouts_approve.V1', $updatedSmsTemplates) === true)
            {
                $merchants = $updatedSmsTemplates['Sms.User.Bulk_payouts_approve.V1'];

                if (($merchants == "*") or
                    (in_array($merchantId, $merchants) == true))
                {
                    $templateName = 'Sms.User.Bulk_payouts_approve.V1';
                }
            }
        }
        else
        {
            if (($approvedPayoutCount == 0) and ($rejectedPayoutCount > 0))
            {
                $templateName = 'Sms.User.Bulk_payouts_reject';

                if (array_key_exists('Sms.User.Bulk_payouts_reject.V1', $updatedSmsTemplates) === true)
                {
                    $merchants = $updatedSmsTemplates['Sms.User.Bulk_payouts_reject.V1'];

                    if (($merchants == "*") or
                        (in_array($merchantId, $merchants) == true))
                    {
                        $templateName = 'Sms.User.Bulk_payouts_reject.V1';
                    }
                }
            }
            else
            {
                $templateName = 'Sms.User.Bulk_payouts_approve_reject_action';

                if (array_key_exists('Sms.User.Bulk_payouts_approve_reject_action.V2', $updatedSmsTemplates) === true)
                {
                    $merchants = $updatedSmsTemplates['Sms.User.Bulk_payouts_approve_reject_action.V2'];

                    if (($merchants == "*") or
                        (in_array($merchantId, $merchants) == true))
                    {
                        $templateName = 'Sms.User.Bulk_payouts_approve_reject_action.V2';
                    }
                }
            }
        }

        $this->trace->info(
            TraceCode::SMS_TEMPLATE_NAME,
            [
                'template_name' => $templateName
            ]
        );

        $smsPayload['sender'] = 'RZPAYX';

        $smsPayload['templateNamespace'] = 'razorpayx_payouts_core';

        $smsPayload['templateName'] = $templateName;
    }

    public function getBankingUsersForMerchantRoles(array $merchantIdToRolesMapping): Base\PublicCollection
    {
        return $this->repo->merchant_user->getBankingUsersForMerchantRoles($merchantIdToRolesMapping);
    }

    protected function getContextFromActionForPayoutLinkCreation($merchant, $user, $input, $token)
    {
        $requiredParams = [Constants::AMOUNT,
                           Constants::CONTACT];

        if (empty(array_diff_key(array_flip($requiredParams), $input)) === true)
        {
            $amount = $input[Constants::AMOUNT];

            $contact = $input[Constants::CONTACT];

            $contactNumber = array_pull($contact, Constants::CONTACT, '');

            $contactEmail = array_pull($contact, Constants::EMAIL, '');

            if (empty($contactNumber) === false)
            {
                $beneficiary = $contactNumber;
            }
            else if (empty($contactEmail) === false)
            {
                $beneficiary = $contactEmail;
            }

            if (empty($amount) === false and empty($beneficiary) === false)
            {
                $context = sprintf('%s:%s:%s:%s:%s:%s:%s',
                                   $merchant->getId(),
                                   $user->getId(),
                                   Constants::CREATE_PAYOUT_LINK,
                                   $input[Constants::ACCOUNT_NUMBER],
                                   $token,
                                   $amount,
                                   $beneficiary);

                $context = hash('sha3-512', $context);
            }
            else
            {
                $context = $this->getDefaultContextFromActionWithMerchant($merchant, $user, Constants::CREATE_PAYOUT_LINK, $token);
            }
        }
        else
        {
            $context = $this->getDefaultContextFromActionWithMerchant($merchant, $user, Constants::CREATE_PAYOUT_LINK, $token);
        }

        return $context;
    }

    protected function removePermissionsForSubMerchantOnAccountSubAccountFlow($basePermissions, $merchantId)
    {
        $this->merchant = $this->repo->merchant->findOrFail($merchantId);

        if ($this->merchant->isFeatureEnabled(Features::ASSUME_SUB_ACCOUNT) === false)
        {
            return $basePermissions;
        }

        $accountSubAccountRestrictedPermissions = $this->fetchSubAccountRestrictedPermissions();

        $subMerchantRestrictedPermissions = $accountSubAccountRestrictedPermissions[SubVaConstants::SUB_MERCHANT];

        $filteredPermissions = array_diff($basePermissions, $subMerchantRestrictedPermissions);

        $this->trace->info(TraceCode::SUB_MERCHANT_PERMISSIONS_FILTERED,
                           [
                               'feature' => SubVaConstants::ACCOUNT_SUB_ACCOUNT,
                               'permissions' => $filteredPermissions
                           ]);

        return array_values($filteredPermissions);
    }

    protected function fetchSubAccountRestrictedPermissions(): array
    {
        $restrictedPermissions = Admin\ConfigKey::get(Admin\ConfigKey::ACCOUNT_SUB_ACCOUNT_RESTRICTED_PERMISSIONS_LIST, []);

        if (empty($restrictedPermissions) === false)
        {
            return $restrictedPermissions;
        }

        return UserRolePermissionsMap::$restrictedPermissions[SubVaConstants::ACCOUNT_SUB_ACCOUNT];
    }

    /**
     * Update the user's name.
     *
     * @param $userName data containing the new name.
     * @param Entity $user  The user entity to be updated.
     * @return Entity The updated user entity.
     * @throws \Exception If there is an error while saving the user.
    */
    public function postUpdateUserName(string $userName, Entity $user)
    {
        // Check if the new name is different from the current name
        if ($userName === $user->getName())
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USERNAME_MUST_BE_DIFFERENT);
        }

        $user->setName($userName);

        $this->repo->user->saveOrFail($user);

        return $user;
    }
}
