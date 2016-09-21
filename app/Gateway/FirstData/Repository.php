<?php

namespace RZP\Gateway\FirstData;

use RZP\Exception;
use RZP\Gateway\FirstData;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'FirstData';

    public function retrieveCapturedByPaymentId($id)
    {
        $repo = $this->repo;

        return $repo::where(Entity::PAYMENT_ID, '=', $id)
                  ->where(Entity::ACTION, '=', Base\Action::CAPTURE)
                  ->where(Entity::STATUS, '=', FirstData\Status::APPROVED)
                  ->first();
    }
}
