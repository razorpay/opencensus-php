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

        $this->repo->saveOrFail($user);

        return $user;
    }

    public function edit(Entity $user, array $input, $operation = 'edit')
    {
        $user->edit($input, $operation);

        $this->repo->saveOrFail($user);

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
     * @param  Entity $user
     * @param  array  $input
     * @return array
     */
    protected function attach(Entity $user, array $input)
    {
        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $mappingParams = [
             'role'       => $input[Entity::ROLE],
             'created_at' => $currentTimestamp,
             'updated_at' => $currentTimestamp
        ];

        $merchantId = $input[Entity::MERCHANT_ID];

        $this->repo->merchant->findOrFailPublic($merchantId);

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
     * @param $userId
     * @param $expiryTime
     *
     * @return string
     */
    public function generateToken($userId, $expiryTime)
    {
        // Using encryption key and combination of userid and time.
        return hash_hmac('sha256', 'password.reset' . '_' . $userId . '_' . $expiryTime, config('app.key'));
    }

    /**
     * @param Entity $user
     * @param string $merchantId
     * @param string $role
     *
     * @return User
     */
    public function detachAndAttachMerchantUser(Entity $user, string $merchantId, string $role)
    {
        // Detach the existing merchant User.
        $userMerchantMappingData = [
            'action'      => 'detach',
            'merchant_id' => $merchantId,
        ];

        $this->updateUserMerchantMapping($user, $userMerchantMappingData);

        // Attach the merchant with the role.

        $userMerchantMappingData['action'] = 'attach';

        $userMerchantMappingData['role'] = $role;

        return $this->updateUserMerchantMapping($user, $userMerchantMappingData);
    }
}
