<?php

namespace RZP\Models\User;

use Mail;
use Hash;
use Config;
use Carbon\Carbon;
use Illuminate\Hashing\BcryptHasher;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Constants\Timezone;
use RZP\Jobs\MailChimpSubscribe;
use RZP\Mail\User\Otp as OtpMail;

class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        $user = (new Entity)->build($input);

        $this->repo->transactionOnLiveAndTest(function() use ($user, $input)
        {
            $this->upsertSettings($user, $input[Entity::SETTINGS] ?? []);
            $this->repo->saveOrFail($user);
        });

        return $user;
    }

    public function edit(Entity $user, array $input, $operation = 'edit')
    {
        $user->edit($input, $operation);

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

    public function confirm(Entity $user)
    {
        $user->setConfirmTokenNull();

        $this->repo->saveOrFail($user);

        return $user;
    }

    public function changePassword(Entity $user, array $input)
    {
        $user->fill($input);

        $user->setPasswordResetToken();

        $this->repo->saveOrFail($user);

        return $user;
    }

    public function savePasswordResetTokenAndExpiry(Entity $user, string $token, int $expiry)
    {
        $user->setPasswordResetToken($token);

        $user->setPasswordResetExpiry($expiry);

        $this->repo->saveOrFail($user);

        return $user;
    }

    public function updateUserMerchantMapping(Entity $user, array $input)
    {
        $user->getValidator()->validateInput('action', $input);

        $function = $input[Entity::ACTION];

        $this->$function($user, $input);

        return $user;
    }

    public function login(array $input)
    {
        (new Entity)->getValidator()->validateInput('login', $input);

        $user = $this->repo->user->findByEmail($input[Entity::EMAIL]);

        $isPasswordEqual = (new BcryptHasher)->check($input[Entity::PASSWORD], $user->getPassword());

        if ($isPasswordEqual === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED);
        }

        return $this->get($user);
    }

    /**
     * Serializes user along with all the merchant it has access to, it's
     * settings etcetera. Primarily consumed by internal dashboard application.
     *
     * @param  Entity $user
     * @return array
     */
    public function get(Entity $user): array
    {
        $response = $user->toArrayPublic();

        $merchants = $user->merchants
                          ->where(Merchant\Entity::SUSPENDED_AT, null)
                          ->callOnEveryItem('toArrayUser');

        // Prepares unique list of merchants for users out of pivot relations.
        $merchantsUnique = [];

        array_walk($merchants, function ($merchant) use (& $merchantsUnique)
        {
            $id   = $merchant[Entity::ID];
            $role = $merchant[Entity::ROLE];

            if (isset($merchantsUnique[$id]) === false)
            {
                $merchantsUnique[$id]                       = $merchant;
                $merchantsUnique[$id][Entity::BANKING_ROLE] = null;
                $merchantsUnique[$id][Entity::ROLE]         = null;
            }

            // Push pivot's role to one of the keys in response basis product type.
            $key = $merchant[Entity::PRODUCT] === Product::BANKING ? Entity::BANKING_ROLE : Entity::ROLE;
            $merchantsUnique[$id][$key] = $role;
        });

        // Additional resources for users.
        $merchantsUnique = $this->appendBankingSpecificDetails(array_values($merchantsUnique));
        $invitations     = $user->invitations->callOnEveryItem('toArrayUser');
        $settings        = $user->getAllSettings();

        $response[Entity::MERCHANTS]   = $merchantsUnique;
        $response[Entity::INVITATIONS] = $invitations;
        $response[Entity::SETTINGS]    = $settings;

        return $response;
    }

    /**
     * Appends banking specific details in serialized unique list of merchants where applies.
     * @param  array $merchants
     * @return array
     */
    protected function appendBankingSpecificDetails(array $merchants)
    {
        return array_map(
            function (array $merchant)
            {
                if ($merchant[Entity::BANKING_ROLE] === null)
                {
                    return $merchant;
                }

                $balance = $this->repo->balance->getMerchantBalanceByType($merchant[Entity::ID], Product::BANKING);

                // We hit this flow during /login too where merchant even though of X, doesn't have balance etc created yet.
                if ($balance === null)
                {
                    return $merchant;
                }

                $bankAccount = $this->repo->bank_account->getMerchantBankAccountsFromAccountNumber($balance->getAccountNumber());

                return $merchant +
                    [
                        Merchant\Entity::BANKING_BALANCE => $balance->only([Merchant\Balance\Entity::BALANCE, Merchant\Balance\Entity::CURRENCY]),
                        Merchant\Entity::BANKING_ACCOUNT => $bankAccount->toArrayHosted(),
                    ];
            },
            $merchants);
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

        $mappingParams = [
             'role'       => $input[Entity::ROLE],
             'product'    => $input[Merchant\Entity::PRODUCT],
             'created_at' => $currentTimestamp,
             'updated_at' => $currentTimestamp
        ];

        $merchantId = $input[Entity::MERCHANT_ID];

        $this->repo->merchant->findOrFailPublic($merchantId);

        $mapping = $this->repo->merchant->getMerchantUserMapping($merchantId,
                                                                 $user->getId(),
                                                                 $input[Entity::ROLE],
                                                                 $input[Merchant\Entity::PRODUCT]);

        if (empty($mapping) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_WITH_ROLE_ALREADY_EXISTS);
        }

        $this->repo->attach($user, Entity::MERCHANTS, [$merchantId => $mappingParams]);

        return $user->toArrayPublic();
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
        $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);

        $this->repo->detach($user, Entity::MERCHANTS, $input[Entity::MERCHANT_ID]);

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

        $mappingParams = [
            'role'       => $input[Entity::ROLE],
            'created_at' => $currentTimestamp,
            'updated_at' => $currentTimestamp
        ];

        $merchantId = $input[Entity::MERCHANT_ID];

        $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);

        $this->repo->sync($user, 'merchants', [$merchantId => $mappingParams], false);

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
     * @param  array           $input
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @param  Entity          $user
     * @return array
     */
    public function sendOtp(array $input, Merchant\Entity $merchant, Entity $user): array
    {
        $this->trace->info(TraceCode::USERS_SEND_OTP_FOR_ACTION, compact('input'));

        $func = 'sendOtpVia' . studly_case($input[Entity::MEDIUM] ?? 'sms_and_email');

        return $this->$func($input, $merchant, $user);
    }

    public function sendOtpViaSmsAndEmail(array $input, Merchant\Entity $merchant, Entity $user): array
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
     */
    public function sendOtpViaSms(array $input, Merchant\Entity $merchant, Entity $user, array $otp = null): array
    {
        if ((isset($input[Entity::MEDIUM]) === false) and
            ($input[Entity::ACTION] !== 'verify_contact') and
            ($user->isContactMobileVerified() === false))
        {
            return [];
        }

        // Optimization: Do just one call to raven when input.medium = sms.
        $otp = $otp ?: $this->generateOtpFromRaven($input, $merchant, $user);

        $payload = [
            'receiver' => $user->getContactMobile(),
            'source'   => 'api',
            'template' => 'sms.user.' . $input[Entity::ACTION],
            'params'   => [
                'otp'      => $otp['otp'],
                'validity' => Carbon::createFromTimestamp($otp['expires_at'], Timezone::IST)->format('H:i:s'),
            ],
        ];

        // Temporary: Need to send these payloads for raven's sms content.
        if (($input[Entity::ACTION] === 'create_payout') and
            (isset($input['amount'], $input['account_number']) === true))
        {
            $payload['params'] += [
                'amount'         => amount_format_IN($input['amount']),
                'account_number' => mask_except_last4($input['account_number']),
            ];
        }

        $this->app->raven->sendSms($payload);

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
    public function sendOtpViaEmail(array $input, Merchant\Entity $merchant, Entity $user, array $otp = null): array
    {
        $otp = $otp ?: $this->generateOtpFromRaven($input, $merchant, $user);

        $mailable = new OtpMail($input[Entity::ACTION], $user, $otp);

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
        $this->verifyOtp($input + ['action' => 'verify_contact'], $merchant, $user);

        $user->setContactMobileVerified(true);

        $this->repo->saveOrFail($user);
    }

    /**
     * Verifies otp for given input(action, token & otp).
     *
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @param  Entity          $user
     */
    public function verifyOtp(array $input, Merchant\Entity $merchant, Entity $user)
    {
        $this->trace->info(TraceCode::USERS_VERIFY_OTP_FOR_ACTION, compact('input'));

        $payload = $this->getTokenAndRavenOtpReqParams($input, $merchant, $user);

        $payload = array_only($payload, ['context', 'receiver', 'source']) + array_only($input, 'otp');

        $this->app->raven->verifyOtp($payload);
    }

    /**
     * Generates otp from remote raven service.
     *
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @param  Entity          $user
     * @return array
     */
    protected function generateOtpFromRaven(array $input, Merchant\Entity $merchant, Entity $user): array
    {
        $payload = $this->getTokenAndRavenOtpReqParams($input, $merchant, $user);

        $token = array_pull($payload, 'token');

        $otp = $this->app->raven->generateOtp($payload);

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
    protected function getTokenAndRavenOtpReqParams(array $input, Merchant\Entity $merchant, Entity $user): array
    {
        $token    = $input['token'] ?? Entity::generateUniqueId();
        $context  = sprintf('%s:%s:%s:%s', $merchant->getId(), $user->getId(), $input[Entity::ACTION], $token);
        $receiver = $user->getContactMobile();
        $source   = 'api';

        return compact(
            'token',
            'receiver',
            'context',
            'source');
    }

    protected function upsertSettings(Entity $user, array $settings)
    {
        if (empty($settings) === false)
        {
            $user->getSettingsAccessor()->upsert($settings)->save();
        }
    }
}
