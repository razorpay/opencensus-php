<?php

namespace RZP\Models\Merchant\Merchant1ccComments;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function createAndSaveComments(Merchant\Entity $merchant, $input)
    {
        $input[Entity::MERCHANT_ID] = $merchant->getId();

        $comment = (new Entity)->build($input);

        $comment->generateId();

        $this->repo->merchant_1cc_comments->saveOrFail($comment);

        return $comment;
    }
}
