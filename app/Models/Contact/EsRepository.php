<?php

namespace RZP\Models\Contact;

use RZP\Models\Base;

/**
 * Class EsRepository
 *
 * @package RZP\Models\Contact
 */
class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Entity::ID,
        Entity::NAME,
        Entity::EMAIL,
        Entity::CREATED_AT,
    ];
}

