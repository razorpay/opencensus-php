<?php

namespace RZP\Models\Workflow\Action\Comment;

use RZP\Exception;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $admin = $this->app['basicauth']->getAdmin();

        $input[Entity::ADMIN_ID] = $admin->getId();

        $comment = new Entity;

        $comment->generateId();

        $comment->build($input);

        $comment->saveOrFail();

        return $comment;
    }
}
