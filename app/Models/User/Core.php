<?php

namespace RZP\Models\User;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
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

    public function confirm(Entity $user)
    {
        $user->setConfirmTokenNull();

        $this->repo->saveOrFail($user);

        return $user;
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

        return $user;
    }

    public function get(Entity $user)
    {

    }

    protected function attach(Entity $user, array $input)
    {
        $currentTimestamp = Carbon::now('Asia/Kolkata')->getTimestamp();

        $mappingParams = [
             'role'       => $input[Entity::ROLE],
             'created_at' => $currentTimestamp,
             'updated_at' => $currentTimestamp
        ];

        $merchantId = $input[Entity::MERCHANT_ID];

        $this->repo->sync($user, 'merchants', [$merchantId => $mappingParams]);
    }

    protected function detach(Entity $user, array $input)
    {
        $this->repo->detach($user, 'merchants', $input[Entity::MERCHANT_ID]);
    }

    protected function update(Entity $user, array $input)
    {
        $currentTimestamp = Carbon::now('Asia/Kolkata')->getTimestamp();

        $mappingParams = [
            'role'       => $input[Entity::ROLE],
            'updated_at' => $currentTimestamp
        ];

        $merchantId = $input[Entity::MERCHANT_ID];

        $this->repo->sync($user, 'merchants', [$merchantId => $mappingParams]);
    }
}
