<?php

namespace RZP\Models\Merchant;

use RZP\Models\Base;
use RZP\Constants\Table;

class EsRepository extends Base\EsRepository
{
    protected static $table = Table::MERCHANT;

    protected $fields = [
        Entity::ID,
        Entity::NAME,
        Entity::EMAIL,
    ];

    protected $queryFields = [
        Entity::NAME,
        Entity::EMAIL,
    ];

    public function setFieldMappings()
    {
        $this->fieldMappings = [
            Entity::NOTES => [
                'type' => 'object',
            ],
        ];
    }
}
