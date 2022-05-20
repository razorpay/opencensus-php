<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\P2p\Base;
use RZP\Constants\Environment;
use RZP\Models\P2p\BankAccount;
use RZP\Models\P2p\Client\Config;
use RZP\Models\Base\PublicCollection;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    /**
     * @return Entity
     */
    public function getDefaultVpa()
    {
        return $this->repo->newP2pQuery()
                          ->where(Entity::DEFAULT, true)
                          ->first();
    }

    public function setDefaultVpa(Entity $vpa)
    {
        $vpa->setDefault(true);

        $this->handleDefaultVpa($vpa);

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }

    /**
     * @return Entity
     */
    public function getAttachedVpa(BankAccount\Entity $bankAccount)
    {
        return $this->repo->newP2pQuery()
                          ->where(Entity::BANK_ACCOUNT_ID, $bankAccount->getId())
                          ->first();
    }

    public function checkUsernameBlocked(string $username): bool
    {
        // If last 10 characters are same as users phone number
        $phoneNumber = substr($this->context()->getDevice()->getContact(), -10);

        if ($username === $phoneNumber)
        {
            return false;
        }

        // Username should not be other phone
        if ((strlen($username) > 9) and
            (is_numeric($username) === true))
        {
            return true;
        }

        return false;
    }

    public function checkLocalAvailability(string $username): bool
    {
        $vpa = $this->repo->findByUsernameHandle($username, $this->context()->handleCode(), true);

        if ($vpa instanceof Entity)
        {
            // Deleted VPA can available for the same device
            if (($vpa->trashed() === true) and
                ($vpa->getDeviceId() === $this->context()->getDevice()->getId()))
            {
                return false;
            }

            // VPA is locally available
            return true;
        }

        return false;
    }

    public function checkForMaxVpaLimit(): bool
    {
        if (($this->isProductionAndLive() === true) or
            ($this->isUnitTest() === true))
        {
            $vpas = $this->repo->newP2pQuery()->withTrashed()->get();

            /**
             * We will fetch the max vpa from p2p client entity which
             * was seeded per merchant and handle.
             */
            $maxLimit = $this->context()->getClient()->getConfigValue(Config::MAX_VPA);

            return ($vpas->count() >= $maxLimit);
        }

        return false;
    }

    public function fetchByUsernameHandle(array $input, bool $trashed = false)
    {
        return $this->repo->fetchByUsernameHandle($input[Entity::USERNAME], $input[Entity::HANDLE], $trashed);
    }

    public function findByUsernameHandle(array $input, bool $trashed = false)
    {
        return $this->repo->findByUsernameHandle($input[Entity::USERNAME], $input[Entity::HANDLE], $trashed);
    }

    public function createOrUpdate(array $input): Entity
    {
        $vpa = $this->repo->findByUsernameHandle($input[Entity::USERNAME], $input[Entity::HANDLE], true);

        if ($vpa instanceof Entity)
        {
            // Deleted VPA can available for the same device
            if (($vpa->getDeviceId() === $this->context()->getDevice()->getId()) and
                ($vpa->trashed() === true))
            {
                $vpa->bankAccount()->dissociate();

                $vpa->setDefault(false);

                $this->handleDefaultVpa($vpa);

                $vpa->restore();

                return $vpa;
            }

            throw $this->logicException('Vpa is already taken by another device');
        }

        return $this->create($input);
    }

    public function create(array $input): Entity
    {
        $vpa = $this->repo->newP2pEntity();

        $vpa->build($input);

        $this->handleDefaultVpa($vpa);

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }

    public function handleBeneficiary(array $input)
    {
        $vpa = $this->repo->findByUsernameHandle($input[Entity::USERNAME], $input[Entity::HANDLE], true);

        if ($vpa instanceof Entity)
        {
            // Beneficiary should never be trashed
            if ($vpa->isBeneficiary() === true)
            {
                $vpa->setVerified($input[Entity::VERIFIED] ?? false);

                $vpa->saveOrFail();

                return $vpa;
            }

            // It's onus VPA and not trashed
            if ($vpa->trashed() === false)
            {
                return $vpa;
            }

            // Very rare scenario where onus VPA is validated, but it is trashed
            throw $this->logicException('Deleted VPA should not be validated', $vpa->only([
                Entity::USERNAME,
                Entity::HANDLE,
                Entity::ID,
            ]));
        }
        else
        {
            return $this->createBeneficiary($input);
        }
    }

    /**
     * @param array $input
     * @return Entity
     */
    public function createBeneficiary(array $input): Entity
    {
        $vpa = $this->repo->getEntityObject();

        $vpa->buildBeneficiary($input);

        $vpa->setHandle($input[BankAccount\Entity::HANDLE]);
        $vpa->setActive(false);
        $vpa->setDefault(false);
        $vpa->setPermissions(Permissions::getDefaultBitmask(Permissions::BENEFICIARY));

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }

    public function assignBankAccount(Entity $vpa, BankAccount\Entity $bankAccount): Entity
    {
        $vpa->restore();

        $vpa->associateBankAccount($bankAccount);

        $vpa->setBeneficiaryName($bankAccount->getBeneficiaryName());

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }

    public function removeBankAccount(Entity $vpa): Entity
    {
        $vpa->dissociateBankAccount();

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }

    public function suggestUsername(BankAccount\Entity $bankAccount)
    {
        // Last 10 character of phone number
        $username = substr($bankAccount->device->getContact(), -10);

        // We need to use vpa suffix for username from client
        // This will be based on merchant config set in the client entity
        $vpaSuffix = $this->context()->getClient()->getConfigValue(Config::VPA_SUFFIX);

        if (isset($vpaSuffix) === true)
        {
            $username = $username.$vpaSuffix;
        }

        // For production and
        if (($this->mode() === Mode::LIVE) and
            ($this->environment() === Environment::PRODUCTION))
        {
            return $username;
        }

        $prefix = $this->environment();

        // TODO: Remove the random part in milestone 3
        $username = $prefix . '.' . $username . '.' . strtolower(random_alpha_string(4));

        return $username;
    }

    public function delete(Entity $vpa)
    {
        $this->repo->deleteOrFail($vpa);
    }

    public function deregister()
    {
        $success = $this->repo->newP2pQuery()
                        ->withTrashed()
                        ->update([
                            Entity::BANK_ACCOUNT_ID => null,
                        ]);

        if (empty($success) === true)
        {
            throw $this->logicException('Failed to unlink the VPAs');
        }
    }

    public function restoreVpas(array $input)
    {
        $vpas = $this->repo->newP2pQuery()->withTrashed()
                                          ->oldest()
                                          ->get();
        $restored = [];

        $preDefault = null;

        // This VPA will considered default
        $toDefault  = $input['default'] ?? null;

        // These VPAs will be left deleted
        $deleted  = $input['deleted'] ?? [];

        foreach ($vpas as $vpa)
        {
            // If there are many default the latest one will be picked.
            if ((empty($toDefault) === false) and
                ($vpa->getPublicId() === $toDefault))
            {
                $toDefault = $vpa;
            }
            else if ($vpa->isDefault())
            {
                $preDefault = $vpa;
            }

            // The VPA which are deleted and not in the list will be restored and marked inactive
            if ($vpa->trashed() and
                (in_array($vpa->getPublicId(), $deleted, true) === false))
            {
                $restored[] = $vpa->getId();
            }
        }

        if ($toDefault instanceof Entity)
        {
            // If we got external request to make vpa default
            $default = $toDefault;
        }
        else if ($preDefault instanceof Entity)
        {
            // Else if we found preDefault we will make this default
            $default = $preDefault;
        }
        else
        {
            throw $this->logicException('There should be one default');
        }

        $this->repo->transaction(function() use ($restored, $default)
        {
            // First we will restore the VPAs
            $this->repo->newP2pQuery()->whereIn(Entity::ID, $restored)
                                      ->withTrashed()
                                      ->update([
                                          Entity::BANK_ACCOUNT_ID => null,
                                          Entity::DELETED_AT      => null,
                                      ]);

            // Now make all VPAs default false except for the default VPA
            $this->repo->newP2pQuery()->whereKeyNot($default->getId())
                                      ->withTrashed()
                                      ->update([
                                          Entity::DEFAULT         => false,
                                      ]);

            // We will restore the default VPA
            $toUpdate = [
                Entity::DEFAULT     => true,
                Entity::DELETED_AT  => null,
            ];
            // If it was deleted before, we will make it inactive
            if ($default->trashed() === true)
            {
                $toUpdate[Entity::BANK_ACCOUNT_ID] = null;
            }

            // Now make sure default vpa is saved in database
            $this->repo->newP2pQuery()->whereKey($default->getId())
                                      ->withTrashed()
                                      ->update($toUpdate);
        });

        return $this->fetchAll([]);
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

            return $vpa;
        }

        // In the end given vpa is not explicitly marked default and
        // there is already one default, we will just return that
        return $vpa->setDefault(false);
    }
}
