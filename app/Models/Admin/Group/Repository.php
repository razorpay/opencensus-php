<?php

namespace RZP\Models\Admin\Group;

use RZP\Models\Admin\Base;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Org;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicCollection;

class Repository extends Base\Repository
{
    protected $entity = 'group';

    protected $merchantIdRequiredForMultipleFetch = false;

    // TODO Define the proxyfetch and admin fetch params

    protected $proxyFetchParamRules = [
        Entity::ORG_ID  => 'sometimes|string',
        Entity::NAME    => 'sometimes|string',
    ];

    public function validateOrgHasNoSuchGroup(Entity $group, Org\Entity $org)
    {
        $grpExists = $this->newQuery()
                          ->orgId($org->getId())
                          ->where(Entity::NAME, '=', $group->getName())
                          ->exists();

        if ($grpExists === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The group with the name already exists');
        }
    }

    /**
     * Get all hierarchical parent groups of given set of groups.
     * Returns final collection which includes the whole upward tree for
     * given set of groups.
     *
     * If $withOriginal=true, returns the passed $groups merged.
     *
     * @param PublicCollection $groups
     * @param bool|boolean     $withOriginal
     *
     * @return PublicCollection
     */
    public function getParentsRecursively(
        PublicCollection $groups,
        bool $withOriginal = false): PublicCollection
    {
        $finalResult = $withOriginal ? $groups : new PublicCollection;

        $parentGroups = $this->findImmediateParentsOfGroups($groups);

        while ($parentGroups->count() > 0)
        {
            $parentGroups->unique()
                         ->each(
                            function ($group, $id) use ($finalResult)
                            {
                                $finalResult->push($group);
                            });

            $parentGroups = $this->findImmediateParentsOfGroups($parentGroups);
        }

        return $finalResult->unique();
    }

    /**
     * Finds immediate parents of given set of groups.
     * Usage eager load to avoid multiple queries.
     *
     * @param PublicCollection $groups
     *
     * @return PublicCollection
     */
    public function findImmediateParentsOfGroups(
        PublicCollection $groups): PublicCollection
    {
        $groups->load(Entity::PARENTS);

        $parents = $groups->pluck(Entity::PARENTS)->collapse()->all();

        return new PublicCollection($parents);
    }
}
