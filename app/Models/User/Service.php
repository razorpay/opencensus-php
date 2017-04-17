<?php

namespace RZP\Models\User;

use Carbon\Carbon;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function create(array $input): array
    {
        $user = (new Core)->create($input);

        return $user->toArrayPublic();
    }

    public function edit(string $id, array $input): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->edit($user, $input);

        return $user->toArrayPublic();
    }

    public function mappingAction(string $id, array $input)
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user->getValidator()->validateInput('action', $input);

        $function = $input['action'];

        return $this->$function($user, $input);
    }

    public function attach(Entity $user, array $input): array
    {
        $currentTimestamp = Carbon::now('Asia/Kolkata')->getTimestamp();

        $mappingParams = [
             'role'       => $input[Entity::ROLE],
             'created_at' => $currentTimestamp,
             'updated_at' => $currentTimestamp
        ];

        $merchantId = $input[Entity::MERCHANT_ID];

        $this->repo->sync($user, 'merchants', [$merchantId => $mappingParams]);

        return $user->toArrayPublic();
    }

    public function detach(Entity $user, array $input): array
    {
        $this->repo->detach($user, 'merchants', $input[Entity::MERCHANT_ID]);

        return $user->toArrayPublic();
    }

    public function update(Entity $user, array $input): array
    {
        $currentTimestamp = Carbon::now('Asia/Kolkata')->getTimestamp();

        $mappingParams = [
            'role'       => $input[Entity::ROLE],
            'updated_at' => $currentTimestamp
        ];

        $merchantId = $input[Entity::MERCHANT_ID];

        $this->repo->sync($user, 'merchants', [$merchantId => $mappingParams]);

        return $user->toArrayPublic();
    }

    public function confirm(string $id): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->confirm($user);

        return $user->toArrayPublic();
    }

    public function changePassword(string $id, array $input): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->changePassword($user, $input[Entity::PASSWORD]);

        return $user->toArrayPublic();
    }

    public function login(array $input): array
    {
        (new Entity)->getValidator()->validateInput('login', $input);

        $user = (new Core)->login($input);

        return $user->toArrayPublic();
    }
}
