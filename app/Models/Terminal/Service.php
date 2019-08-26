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

        return $terminal;
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

        return $terminals->toArrayAdmin($subMerchantFlag);
    }

    public function getTerminal($mid, $tid)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $terminal = $this->repo->terminal->getByIdAndMerchantId($mid, $tid);

        return $terminal->toArrayAdmin();
    }

    public function deleteTerminal($mid, $tid)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $terminal = $this->repo->terminal->getByIdAndMerchantId($mid, $tid);

        $terminal = $this->repo->deleteOrFail($terminal);

        if ($terminal === null)
            return [];

        return $terminal->toArrayAdmin();
    }

    public function deleteTerminal2($id)
    {
        $terminal = $this->repo->terminal->findOrFailPublic($id);

        $terminal = $this->repo->deleteOrFail($terminal);

        if ($terminal === null)
            return [];

        return $terminal->toArrayAdmin();
    }

    public function modifyTerminal($mid, $tid, $input)
    {
        $terminal = $this->repo->terminal->getByIdAndMerchantId($mid, $tid);

        $terminal = (new Terminal\Core)->edit($terminal, $input);

        return $terminal->toArrayAdmin();
    }

    public function editTerminal($tid, $input)
    {
        $terminal = $this->repo->terminal->findOrFail($tid);

        $terminal = (new Terminal\Core)->edit($terminal, $input);

        return $terminal->toArrayAdmin();
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

        return $terminal->toArrayAdmin();
    }

    public function removeMerchantFromTerminal(string $id, string $merchantId)
    {
        $terminal = $this->repo->terminal->getById($id);

        $terminal = (new Terminal\Core)->removeMerchantFromTerminal($terminal, $merchantId);

        return $terminal->toArrayAdmin();
    }

    public function reassignMerchantForTerminal(string $id, array $input)
    {
        $terminal = $this->repo->terminal->getById($id);

        $terminal->getValidator()->validateInput('reassign', $input);

        $mid = $input[Entity::MERCHANT_ID];

        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $terminal = (new Terminal\Core)->reassignMerchantForTerminal($terminal, $merchant);

        return $terminal->toArrayAdmin();
    }

    public function addMerchantToTerminal(string $id, string $mid)
    {
        $terminal = $this->repo->terminal->getById($id);

        $terminal = (new Terminal\Core)->addMerchantToTerminal($terminal, $mid);

        return $terminal->toArrayAdmin();
    }

    public function toggleTerminal($id, $input)
    {
        $terminal = $this->repo->terminal->getById($id);

        $toggle = (bool) $input['toggle'];

        (new Terminal\Core)->toggle($terminal, $toggle);

        return $terminal->toArrayAdmin();
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
        else if (isset($input['password2']))
        {
            $flag = $terminal->matchEncryptedAttribute(
                Terminal\Entity::GATEWAY_TERMINAL_PASSWORD2, $input['password2']);
        }
        else if (isset($input['secret2']))
        {
            $flag = $terminal->matchEncryptedAttribute(
                Terminal\Entity::GATEWAY_SECURE_SECRET2, $input['secret2']);
        }

        return ['match' => $flag];
    }

    public function getBanks(string $id): array
    {
        $terminal = $this->repo->terminal->getById($id);

        $banks = $this->core()->getBanksForTerminal($terminal);

        return $banks;
    }

    public function setBanks(string $id, array $input): array
    {
        $terminal = $this->repo->terminal->getById($id);

        $banksToEnable = $input[Entity::ENABLED_BANKS] ?? [];

        $banks = $this->core()->setBanksForTerminal($terminal, $banksToEnable);

        return $banks;
    }


    /**
     * update enabled_banks for multiple terminal
     *
     * @param  array $input
     *
     * @return array
     * @throws \Exception
     */
    public function updateTerminalsBank($input)
    {
        (new Terminal\Validator())->validateInput('update_terminals_bank', $input);

        $returnData = [];

        $action = $input['action'];

        try
        {
            $ids = $input['terminal_ids'];

            $terminals = $this->repo->terminal->findMany($ids);

            $bank = $input['bank'];

            foreach ($terminals as $terminal)
            {
                $terminalId = $terminal->getId();

                $enabledBanks = $this->core()->getBanksForTerminal($terminal)["enabled"];

                $oldBanksList = array_keys($enabledBanks);

                $newBanksList = $this->getNewBankList($oldBanksList, $bank, $action);

                //update database only if required
                if (count($oldBanksList) != count($newBanksList))
                {
                    try
                    {
                        $updatedEnabledBanks = $this->core()->setBanksForTerminal($terminal, $newBanksList);

                        $returnData[$terminalId] = $updatedEnabledBanks["enabled"];
                    }
                    catch ( Exception\BadRequestValidationFailureException $e)
                    {
                        $returnData[$terminalId] = $e->getMessage();
                    }
                }
                else
                {
                    $returnData[$terminalId] = $enabledBanks;
                }
            }
            foreach ($ids as $id)
            {
                if (array_key_exists($id, $returnData) === false)
                {
                    $returnData[$id] = "Terminal doesn't exist";
                }
            }

            $returnData["success"] = true;

            return $returnData;
        }
        catch (\Exception $e)
        {
            throw $e;
        }
    }


    /**
     * Add/Remove bank from the oldEnabledBankList adn return the newList.
     *
     * @param  array    $oldList
     * @param  string    $bank
     * @param  string    $action
     *
     * @return array
     */
    protected function getNewBankList(array $oldList, string $bank, string $action): array
    {
        $index = array_search($bank, $oldList);

        if ($index === false and $action === 'add')
        {
            array_push($oldList, $bank);
        }
        else if ($index !== false and $action === 'remove')
        {
            unset($oldList[$index]);
        }
        return array_values($oldList);
    }
}
