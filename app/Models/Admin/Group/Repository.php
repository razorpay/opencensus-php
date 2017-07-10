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

    public function getParentsRecursively(
        PublicCollection $groups,
        bool $withOriginal = false): PublicCollection
    {
        $finalResult = $withOriginal ? $groups : new PublicCollection;

        $parentGruops = $this->findImmdiateParentsOfGroups($groups);

        while ($parentGruops->count() > 0)
        {
            $parentGruops->each(function ($group, $id) use ($finalResult)
            {
                $finalResult->push($group);
            });

            $parentGruops = $this->findImmdiateParentsOfGroups($parentGruops);
        }

        return $finalResult;
    }

    public function findImmdiateParentsOfGroups(PublicCollection $groups): PublicCollection
    {
        $groups->load(Entity::PARENTS);

        $parents = $groups->pluck(Entity::PARENTS)->collapse()->all();

        return (new PublicCollection($parents));
    }
}
