<?php

namespace Models\Key;

use Models\Base;

class Service extends Base\Service
{
    public function fetch($id)
    {
        $key = $this->repo->key->findOrFailPublic($id);

        return $key->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $keys = $this->repo->key->fetch($input);

        return $keys->toArrayPublic();
    }
}
