<?php

namespace RZP\Models\Comment;

use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Admin\Admin;
use RZP\Models\Workflow\Action;

class Core extends Base\Core
{
    /**
     * @param array         $input
     * @param Action\Entity $action
     * @param Admin\Entity  $admin
     *
     * @return Entity $comment
     */
    public function createForWorkflowAction(
        array $input,
        Action\Entity $action,
        Admin\Entity $admin): Entity
    {
        $comment = $this->create($input);

        $comment->admin()->associate($admin);

        $comment->entity()->associate($action);

        try
        {
            $this->repo->saveOrFail($comment);
        }
        catch (\Exception $ex)
        {
            if ($ex->getCode() === ErrorCode::SERVER_ERROR_DB_QUERY_FAILED)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
            }
            else
            {
                throw $ex;
            }
        }

        return $comment;
    }

    /**
     * @param array $input
     *
     * @return Entity
     */
    public function create(array $input): Entity
    {
        $comment = new Entity;

        $comment->build($input);

        return $comment;
    }
}
