<?php

namespace RZP\Models\User;

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

    public function confirm(string $id): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->confirm($user);

        return $user->toArrayPublic();
    }

    public function confirmUserByData(array $input): array
    {
        $user = (new Core)->confirmUserByData($input);

        return $user->toArrayPublic();
    }

    public function changePassword(string $id, array $input): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->changePassword($user, $input);

        return $user->toArrayPublic();
    }

    public function updateUserMerchantMapping(string $id, array $input): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->updateUserMerchantMapping($user, $input);

        return $user->toArrayPublic();
    }

    public function login(array $input): array
    {
        return (new Core)->login($input);
    }

    public function get(string $id): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $response = (new Core)->get($user);

        return $response;
    }
}
