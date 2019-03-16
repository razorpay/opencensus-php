<?php

namespace RZP\Models\Partner\Commission;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;

class Service extends Base\Service
{
    /**
     * Get per transaction commission list for a merchant
     *
     * @param array $input
     *
     * @return array
     */
    public function list(array $input): array
    {
        $commissions = $this->core()->list($this->merchant, $input);

        return $commissions->toArrayPublic();
    }

    public function fetch(string $id, $input): array
    {
        $input[Entity::ID] = $id;

        $commissions = $this->core()->list($this->merchant, $input);

        if (count($commissions) === 0)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }
        else
        {
            return $commissions[0]->toArrayPublic();
        }

    }
}
