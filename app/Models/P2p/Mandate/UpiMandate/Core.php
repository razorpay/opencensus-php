<?php

namespace RZP\Models\P2p\Mandate\UpiMandate;

/**
 * Class Core
 *
 * @package RZP\Models\P2p\Mandate\UpiMandate
 */
class Core extends \RZP\Models\P2p\Base\Core
{
    public function update(Entity $upi, array $input): Entity
    {
        $upi->edit($input);

        $this->repo->saveOrFail($upi);

        return $upi;
    }

    public function findAll(array $input)
    {
        return $this->repo->findAll($input);
    }
}
