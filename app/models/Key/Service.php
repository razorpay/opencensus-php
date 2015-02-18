<?php

namespace Models\Key;

class Service extends Base\Service
{
    public function fetch($id)
    {
        $key = $this->repo->findOrFailPublic($id);

        return $key->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $keys = $this->repo->fetch($input);

        return $keys->toArrayPublic();
    }
}
