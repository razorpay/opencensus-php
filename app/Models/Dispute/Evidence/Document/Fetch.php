<?php


namespace RZP\Models\Dispute\Evidence\Document;

use RZP\Base\Fetch as BaseFetch;
use RZP\Models\Dispute\Evidence\Document;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Document\Entity::DISPUTE_ID => 'sometimes|size:14',
        ],
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            Document\Entity::DISPUTE_ID,
        ],
    ];
}