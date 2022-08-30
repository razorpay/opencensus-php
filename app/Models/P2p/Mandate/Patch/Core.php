<?php

namespace RZP\Models\P2p\Mandate\Patch;

use \RZP\Models\P2p\Mandate\Entity as MandateEntity;

/**
 * Class Core
 *
 * @package RZP\Models\P2p\Mandate\Patch
 */
class Core extends \RZP\Models\P2p\Base\Core
{
    /**
     * This is the function to update the patch entity
     * @param Entity $patch
     * @param array  $input
     *
     * @return Entity
     */
    public function update(Entity $patch, array $input): Entity
    {
        $patch->edit($input);

        $this->repo->saveOrFail($patch);

        return $patch;
    }

    /**
     * This is the method to update the patch according to mandate status
     * @param \RZP\Models\P2p\Mandate\Entity $mandate
     * @param Entity                         $patch
     *
     * @return Entity
     */
    public function updatePatchStatus(MandateEntity $mandate, Entity $patch , bool $active): Entity
    {
        $patch->setStatus($mandate->getInternalStatus());

        $patch->setActive($active);

        return $this->update($patch, []);
    }

    /**
     * This is the method to find all by input
     * @param array $input
     *
     * @return mixed
     */
    public function findAll(array $input)
    {
        return $this->repo->findAll($input);
    }

    /**
     * @param string $input
     * @param bool   $active
     * This is the method to find mandate id and activeness
     * @return mixed
     */
    public function findPatchByMandateIdAndActive(string $input, bool $active)
    {
        return $this->repo->findPatchByMandateIdAndActive($input , $active);
    }
}
