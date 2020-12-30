<?php

namespace RZP\Models\Application;

use RZP\Models\Base;

class Core extends Base\Core
{

    public function create(array $input): Entity
    {
        $appEntity = (new Entity)->build($input);

        $this->repo->saveOrFail($appEntity);

        return $appEntity;
    }

    public function update(Entity $app, array $input): Entity
    {
        $app->edit($input);

        $this->repo->saveOrFail($app);

        return $app;
    }
}
