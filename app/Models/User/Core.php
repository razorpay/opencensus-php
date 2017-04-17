<?php

namespace RZP\Models\User;

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

    public function changePassword(Entity $user, string $password)
    {
        $user->setPassword($password);

        $this->repo->saveOrFail($user);

        return $user;
    }

    public function login(array $input)
    {
        $user = $this->repo->user->getByEmail($input[Entity::EMAIL]);

        $isPasswordEqual = (new BcryptHasher)->check($input[Entity::PASSWORD], $user->getPassword());

        if ($isPasswordEqual === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED);
        }

        return $user;
    }
}
