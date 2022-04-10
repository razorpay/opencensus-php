<?php

namespace RZP\Models\PaymentLink\NocodeCustomUrl;

use RZP\Models\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Repository extends Base\Repository
{
    const ATLEAST_ONE_FILTER_INPUT = "Atleast one filter must be passed";

    protected $entity = 'nocode_custom_url';

    /**
     * @param array $input
     * @param bool  $withTrashed
     *
     * @return \RZP\Models\PaymentLink\NocodeCustomUrl\Entity|null
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function findByAttributes(array $input, bool $withTrashed=false): ?Entity
    {
        $entries = $this->fetchByAttributes($input, $withTrashed);

        return $this->getFirstEntity($entries);
    }

    /**
     * @param array $input
     * @param bool  $withTrashed
     *
     * @return \RZP\Models\Base\PublicCollection
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function fetchByAttributes(array $input, bool $withTrashed = false): Base\PublicCollection
    {
        $input = $this->filterAllowedKeys($input);

        if (empty($input) === true)
        {
            throw new BadRequestValidationFailureException(self::ATLEAST_ONE_FILTER_INPUT);
        }

        $query = $this->newQuery();

        foreach (Entity::ALLOWED_QUERY_KEYS as $key)
        {
            $query->when(array_key_exists($key, $input), function ($query) use ($key, $input) {
                return $query->where($key, $input[$key]);
            });
        }

        if ($withTrashed === true)
        {
            $query->withTrashed();
        }

        return $query->get();
    }

    /**
     * @param \RZP\Models\Base\PublicCollection $entries
     *
     * @return \RZP\Models\PaymentLink\NocodeCustomUrl\Entity|null
     */
    private function getFirstEntity(Base\PublicCollection $entries): ?Entity
    {
        return $entries->isEmpty() === true ? null : $entries->first();
    }

    /**
     * @param array $input
     *
     * @return array
     */
    private function filterAllowedKeys(array $input): array
    {
        $filteredKeys = array_intersect(Entity::ALLOWED_QUERY_KEYS, array_keys($input));

        $final = [];

        foreach ($filteredKeys as $key)
        {
            $final[$key] = $input[$key];
        }

        return $final;
    }
}
