<?php

namespace RZP\Models\User;

use Hash;
use Config;
use Carbon\Carbon;
use Illuminate\Hashing\BcryptHasher;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Jobs\MailChimpSubscribe;

class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        $user = (new Entity)->build($input);

        $this->repo->transaction(function() use ($user, $input)
        {
            $this->upsertSettings($user, $input[Entity::SETTINGS] ?? []);
            $this->repo->saveOrFail($user);
        });

        return $user;
    }

    public function edit(Entity $user, array $input, $operation = 'edit')
    {
        $user->edit($input, $operation);

        $this->repo->transaction(function() use ($user, $input)
        {
            $this->upsertSettings($user, $input[Entity::SETTINGS] ?? []);
            $this->repo->saveOrFail($user);
        });

        if ($operation === 'edit')
        {
            $this->trace->info(
                TraceCode::USER_EDIT,
                [
                    'user_id'     => $user->getId(),
                    'input'       => $input
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

    public function get(Entity $user)
    {
        $userArray = $user->toArrayPublic();

        $merchants = $user->merchants
                          ->where(Merchant\Entity::SUSPENDED_AT, null)
                          ->callOnEveryItem('toArrayUser');

        $invitations = $user->invitations
                            ->callOnEveryItem('toArrayUser');

        $userArray[Entity::MERCHANTS] = $merchants;

        $userArray[Entity::INVITATIONS] = $invitations;

        return $userArray;
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
     *     - medium - sms|email
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @param  Entity          $user
     */
    public function sendOtp(array $input, Merchant\Entity $merchant, Entity $user)
    {
        $this->trace->info(TraceCode::USERS_SEND_OTP_FOR_ACTION, compact('input'));

        $func = 'sendOtpVia' . studly_case($input[Entity::MEDIUM]);
        $this->$func($input, $merchant, $user);
    }

    /**
     * Ref: sendOtp()
     * Sends OTP to user's contact via Raven.
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @param  Entity          $user
     */
    public function sendOtpViaSms(array $input, Merchant\Entity $merchant, Entity $user)
    {
        $action   = $input[Entity::ACTION];
        $context  = sprintf('%s:%s:%s', $merchant->getId(), $user->getId(), $action);
        $receiver = $user->getContactMobile();
        $source   = 'api';
        $template = 'sms.user_action_otp';
        // Action must read as verb so can be used like to {action} in raven's generic template.
        $params   = [Entity::ACTION => str_replace('_', ' ', $action)];

        $payload = compact('context', 'receiver', 'source', 'template', 'params');
        $this->app->raven->sendOtp($payload);
    }

    /**
     * Ref: sendOtp()
     * Sends OTP to user's email.
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @param  Entity          $user
     */
    public function sendOtpViaEmail(array $input, Merchant\Entity $merchant, Entity $user)
    {
        // Todo
    }

    /**
     * Verifies input OTP against specific action(hence context) i.e. verify_contact.
     * Additionally marks users.contact_mobile_verified flag as true if success.
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @param  Entity          $user
     */
    public function verifyOtpForContactVerification(array $input, Merchant\Entity $merchant, Entity $user)
    {
        $this->trace->info(TraceCode::USERS_VERIFY_OTP_FOR_CONTACT_VERIFY, compact('input'));

        $context  = sprintf('%s:%s:%s', $merchant->getId(), $user->getId(), Entity::ACTION_VERIFY_CONTACT);
        $receiver = $user->getContactMobile();
        $source   = 'api';
        $otp      = $input[Entity::OTP];

        $payload = compact('context', 'receiver', 'source', 'otp');
        $this->app->raven->verifyOtp($payload);

        $user->setContactMobileVerified(true);
        $this->repo->saveOrFail($user);
    }

    protected function upsertSettings(Entity $user, array $settings)
    {
        if (empty($settings) === false)
        {
            $user->getSettingsAccessor()->upsert($settings)->save();
        }
    }
}
