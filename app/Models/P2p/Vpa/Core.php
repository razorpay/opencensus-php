<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\BankAccount;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    public function checkLocalAvailability(string $username): bool
    {
        $vpa = $this->repo->fetchByUsername($username);

        return ($vpa instanceof Entity);
    }

    public function create(array $input): Entity
    {
        $vpa = $this->repo->newP2pEntity();

        $vpa->build($input);

        $this->handleDefaultVpa($vpa);

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }

    public function assignBankAccount(Entity $vpa, BankAccount\Entity $bankAccount): Entity
    {
        $vpa->associateBankAccount($bankAccount);

        $vpa->setBeneficiaryName($bankAccount->getBeneficiaryName());

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }

    /**
     * Handling the default vpa within each gateway
     * Note:
     * 1. Currently we are only considering one handle per gateway for this.
     * 2. There can only be one default vpa withing each gateway.
     * 3. Gateway may not set or utilize default property, even though we will internally make it default.
     * 4. Logic is simply assumed IAW basic flows of VPA, this might be changed according to different gateways.
     *
     * @param Entity $vpa
     * @return $this
     */
    private function handleDefaultVpa(Entity $vpa)
    {
        $vpas = $this->fetchAll([]);

        // If there is no VPA for device and handle, or if there is no
        // default vpa we will mark this default
        if (($vpas->count() === 0) or
            ($vpas->where(Entity::DEFAULT, true)->count() === 0))
        {
            return $vpa->setDefault(true);
        }

        // Now since there is already one default vpa, we will check
        // if this vpa is default or not, if yes we will remove default from existing one
        if ($vpa->isDefault() === true)
        {
            $default = $vpas->where(Entity::DEFAULT, true)->first();

            $default->setDefault(false);

            $this->repo->saveOrFail($default);
        }

        // In the end given vpa is not explicitly marked default and
        // there is already one default, we will just return that
        return $vpa->setDefault(false);
    }
}
