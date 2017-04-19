<?php

namespace RZP\Models\User;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Action;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $user = (new Entity)->build($input);

        $this->repo->saveOrFail($user);

        return $user;
    }

    /**
     * Edit user entity
     *
     * @param \RZP\Models\User\Entity $user
     * @param array $input
     * @return \RZP\Models\User\Entity
     */
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
}
