<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        // TODO Save logo in the S3 and store the url
        $input[Entity::LOGO_URL] = '';

        $org = (new Entity)->build($input);

        $this->repo->saveOrFail($org);

        return $org;
    }
}
