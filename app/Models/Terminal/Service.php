<?php

namespace RZP\Models\Terminal;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Terminal;

class Service extends Base\Service
{
    public function createTerminal($id, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $terminal = (new Terminal\Core)->create($input, $merchant);

        return $terminal->toArrayPublic();
    }

    public function copyTerminal($mid, $tid, $input)
    {
        $terminal = $this->repo->terminal->findByIdAndMerchantId($tid, $mid);

        $terminals = (new Terminal\Core)->copy($input, $terminal);

        return $terminals;
    }

    public function getTerminals(string $mid, array $input)
    {
        $subMerchantFlag = false;

        if (isset($input['sub_merchant']) === true)
        {
            $subMerchantFlag = (bool) $input['sub_merchant'];
        }

        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $terminals = $this->repo->terminal->getByMerchantId($mid);

        return $terminals->toArrayPublic($subMerchantFlag);
    }

    public function getTerminal($mid, $tid)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $terminal = $this->repo->terminal->getByIdAndMerchantId($mid, $tid);

        return $terminal->toArrayPublic();
    }

    public function deleteTerminal($mid, $tid)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $terminal = $this->repo->terminal->getByIdAndMerchantId($mid, $tid);

        $terminal = $this->repo->deleteOrFail($terminal);

        if ($terminal === null)
            return [];

        return $terminal->toArrayPublic();
    }

    public function deleteTerminal2($id)
    {
        $terminal = $this->repo->terminal->findOrFailPublic($id);

        $terminal = $this->repo->deleteOrFail($terminal);

        if ($terminal === null)
            return [];

        return $terminal->toArrayPublic();
    }

    public function modifyTerminal($mid, $tid, $input)
    {
        $terminal = $this->repo->terminal->getByIdAndMerchantId($mid, $tid);

        $terminal = (new Terminal\Core)->edit($terminal, $input);

        return $terminal->toArrayPublic();
    }

    public function editTerminal($tid, $input)
    {
        $terminal = $this->repo->terminal->findOrFail($tid);

        $terminal = (new Terminal\Core)->edit($terminal, $input);

        return $terminal->toArrayPublic();
    }

    public function restoreTerminal($id)
    {
        $terminal = $this->repo->terminal->getById($id);

        if ($terminal->isDeleted() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Terminal provided is not deleted');
        }

        (new Terminal\Core)->validateExistingTerminal($terminal);

        $terminal->restore();

        return $terminal->toArrayPublic();
    }

    public function removeMerchantFromTerminal(string $id, string $merchantId)
    {
        $terminal = $this->repo->terminal->getById($id);

        $terminal = (new Terminal\Core)->removeMerchantFromTerminal($terminal, $merchantId);

        return $terminal->toArrayPublic();
    }

    public function reassignMerchantForTerminal(string $id, string $mid)
    {
        $terminal = $this->repo->terminal->getById($id);

        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $terminal = (new Terminal\Core)->reassignMerchantForTerminal($terminal, $merchant);

        return $terminal->toArrayPublic();
    }

    public function addMerchantToTerminal(string $id, string $mid)
    {
        $terminal = $this->repo->terminal->getById($id);

        $terminal = (new Terminal\Core)->addMerchantToTerminal($terminal, $mid);

        return $terminal->toArrayPublic();
    }

    public function toggleTerminal($id, $input)
    {
        $terminal = $this->repo->terminal->getById($id);

        $toggle = (bool) $input['toggle'];

        (new Terminal\Core)->toggle($terminal, $toggle);

        return $terminal->toArrayPublic();
    }

    public function checkTerminalEncryptedValue($id, $input)
    {
        $terminal = $this->repo->terminal->findOrFail($id);

        $flag = true;

        if (isset($input['secret']))
        {
            $flag = $terminal->matchEncryptedAttribute(
                        Terminal\Entity::GATEWAY_SECURE_SECRET, $input['secret']);
        }
        else if (isset($input['password']))
        {
            $flag = $terminal->matchEncryptedAttribute(
                        Terminal\Entity::GATEWAY_TERMINAL_PASSWORD, $input['password']);
        }
        else if (isset($input['access_code']))
        {
            $flag = $terminal->matchEncryptedAttribute(
                        Terminal\Entity::GATEWAY_ACCESS_CODE, $input['access_code']);
        }

        return ['match' => $flag];
    }
}
