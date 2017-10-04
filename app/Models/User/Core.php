<?php

namespace RZP\Models\User;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Illuminate\Hashing\BcryptHasher;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $user = (new Entity)->build($input);

        $this->repo->saveOrFail($user);

        return $user;
    }

    public function edit(Entity $user, array $input)
    {
        $user->edit($input);

        $this->repo->saveOrFail($user);

        $this->trace->info(
            TraceCode::USER_EDIT,
            [
                'user_id'     => $user->getId(),
                'input'       => $input,
            ]);

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

    public function confirmUserByData(array $input)
    {
        $user = null;

        (new Entity)->getValidator()->validateInput('confirm', $input);

        // need to validate if it is only a confirm_token or an email
        if (empty($input[Entity::CONFIRM_TOKEN]) === false)
        {
            $user = $this->repo->user->findByToken($input[Entity::CONFIRM_TOKEN]);
        }
        else if (empty($input[Entity::EMAIL]) === false)
        {
            $user = $this->repo->user->findByEmail($input[Entity::EMAIL]);
        }
        else
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_NOT_FOUND);
        }

        return $this->confirm($user);
    }

    public function changePassword(Entity $user, array $input)
    {
        $user->edit($input, 'change_password');

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
                          ->where(Merchant\Entity::SUSPENDED_AT, NULL)
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

        $this->repo->sync($user, 'merchants', [$merchantId => $mappingParams], false);

        return $user->toArrayPublic();
    }
}
