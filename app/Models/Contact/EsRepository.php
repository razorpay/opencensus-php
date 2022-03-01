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
        Entity::MERCHANT_ID,
        Entity::NAME,
        Entity::EMAIL,
        Entity::CONTACT,
        Entity::ACTIVE,
        Entity::TYPE,
        Entity::CREATED_AT,
    ];

    protected $queryFields = [
        Entity::NAME,
        Entity::EMAIL,
        Entity::CONTACT,
        Entity::CONTACT_PS,
        Entity::EMAIL_PS,
    ];

    public function buildQueryForActive(array &$query, bool $value)
    {
        $this->addTermFilter($query, Entity::ACTIVE, $value);
    }

    public function buildQueryForType(array &$query, string $value)
    {
        $this->addTermFilter($query, Entity::TYPE, $value);
    }

    public function buildQueryForEmail(array &$query, string $value)
    {
        if (empty($value) == true) {
            return;
        }

        $this->addTermFilter($query, Entity::EMAIL_RAW, $value);
    }

    public function buildQueryForContactPs(array &$query, string $value)
    {
        if (empty($value) == true) {
            return;
        }

        $this->addTermFilter($query, Entity::CONTACT_NUMBER_PARTIAL_SEARCH, $value);
    }

    public function buildQueryForEmailPs(array &$query, string $value)
    {
        if (empty($value) == true) {
            return;
        }

        $this->addTermFilter($query, Entity::CONTACT_EMAIL_PARTIAL_SEARCH, strtolower($value));
    }

}
