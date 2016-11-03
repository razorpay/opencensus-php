<?php

namespace RZP\Models\Admin\Organization;

use Carbon\Carbon;

use RZP\Constants\Table;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Payment\Verify;
use RZP\Models\Transaction;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'organization';

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = array(
        Entity::EMAIL                 => 'sometimes',
        Entity::STATUS                => 'sometimes|string',
        Entity::AUTH                  => 'sometimes|string|max:500',
        Entity::ALLOWED_EMAIL_DOMAINS => 'sometimes|string|max:500',
    );

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::EMAIL                 => 'sometimes',
        Entity::STATUS                => 'sometimes|string',
        Entity::AUTH                  => 'sometimes|string|max:500',
        Entity::ALLOWED_EMAIL_DOMAINS => 'sometimes|string|max:500',
    );
}
