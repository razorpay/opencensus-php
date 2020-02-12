<?php

namespace RZP\Models\Terminal;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Jobs\TerminalsServiceMigrateJob;
use RZP\Services\TerminalsService as TerminalsServiceClient;

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
        Entity::verifyIdAndSilentlyStripSign($tid);

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

        Entity::verifyIdAndSilentlyStripSign($tid);

        $terminal = $this->repo->terminal->getByIdAndMerchantId($mid, $tid);

        return $terminal->toArrayAdmin();
    }

    public function deleteTerminal($mid, $tid)
    {
        $this->trace->info(
            TraceCode::TERMINAL_DELETE,
            [
                'merchant_id'       => $mid,
                'terminal_id'       => $tid,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        Entity::verifyIdAndSilentlyStripSign($tid);

        $terminal = $this->repo->terminal->getByIdAndMerchantId($mid, $tid);

        $this->app['workflow']
            ->setEntityAndId($terminal->getEntity(), $terminal->getId())
            ->handle($terminal, (new \stdClass));

        $terminal = $this->repo->deleteOrFail($terminal);

        if ($terminal === null)
            return [];

        return $terminal->toArrayAdmin();
    }

    public function deleteTerminal2($id)
    {
        $this->trace->info(
            TraceCode::TERMINAL_DELETE2,
            [
                'terminal_id' => $id,
            ]);

        Entity::verifyIdAndSilentlyStripSign($id);

        $terminal = $this->repo->terminal->findOrFailPublic($id);

        $this->app['workflow']
            ->setEntityAndId($terminal->getEntity(), $terminal->getId())
            ->handle($terminal, (new \stdClass));

        $terminal = $this->repo->deleteOrFail($terminal);

        if ($terminal === null)
            return [];

        return $terminal->toArrayAdmin();
    }

    public function modifyTerminal($mid, $tid, $input)
    {
        Entity::verifyIdAndSilentlyStripSign($tid);

        $terminal = $this->repo->terminal->getByIdAndMerchantId($mid, $tid);

        $terminal = (new Terminal\Core)->edit($terminal, $input);

        return $terminal->toArrayAdmin();
    }

    public function editTerminal($tid, $input)
    {
        Entity::verifyIdAndSilentlyStripSign($tid);

        $terminal = $this->repo->terminal->findOrFail($tid);

        $terminal = (new Terminal\Core)->edit($terminal, $input);

        return $terminal->toArrayAdmin();
    }

    public function restoreTerminal($id)
    {
        Entity::verifyIdAndSilentlyStripSign($id);

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
        Entity::verifyIdAndSilentlyStripSign($id);

        $terminal = $this->repo->terminal->getById($id);

        $terminal = (new Terminal\Core)->removeMerchantFromTerminal($terminal, $merchantId);

        return $terminal->toArrayAdmin();
    }

    public function reassignMerchantForTerminal(string $id, array $input)
    {
        Entity::verifyIdAndSilentlyStripSign($id);

        $terminal = $this->repo->terminal->getById($id);

        $terminal->getValidator()->validateInput('reassign', $input);

        $mid = $input[Entity::MERCHANT_ID];

        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $terminal = (new Terminal\Core)->reassignMerchantForTerminal($terminal, $merchant);

        return $terminal->toArrayAdmin();
    }

    public function addMerchantToTerminal(string $id, string $mid)
    {
        Entity::verifyIdAndSilentlyStripSign($id);

        $terminal = $this->repo->terminal->getById($id);

        $terminal = (new Terminal\Core)->addMerchantToTerminal($terminal, $mid);

        return $terminal->toArrayAdmin();
    }

    public function toggleTerminal($id, $input)
    {
        Entity::verifyIdAndSilentlyStripSign($id);

        $terminal = $this->repo->terminal->getById($id);

        $toggle = (bool) $input['toggle'];

        $terminalStatusTrace = ($toggle) ? TraceCode::TERMINAL_ENABLE : TraceCode::TERMINAL_DISABLE;

        $this->trace->info(
            $terminalStatusTrace,
            [
                'terminal_id' => $terminal->getId(),
                'input'       => $input,
            ]);

        $enabled = $terminal->isEnabled();

        // Workflow
        list($original, $dirty) = [
            ['terminal_enable' => $enabled],
            ['terminal_enable' => !$enabled],
        ];

        $this->app['workflow']
            ->setEntityAndId($terminal->getEntity(), $terminal->getId())
            ->handle($original, $dirty);

        (new Terminal\Core)->toggle($terminal, $toggle);

        return $terminal->toArrayAdmin();
    }

    public function checkTerminalEncryptedValue($id, $input)
    {
        Entity::verifyIdAndSilentlyStripSign($id);

        (new Terminal\Validator())->validateInput('terminalCheckSecret', $input);

        $terminal = $this->repo->terminal->findOrFail($id);

        $secretFields = [
            Terminal\Entity::GATEWAY_TERMINAL_PASSWORD,
            Terminal\Entity::GATEWAY_TERMINAL_PASSWORD2,
            Terminal\Entity::GATEWAY_SECURE_SECRET,
            Terminal\Entity::GATEWAY_SECURE_SECRET2,
        ];

        $output = [];

        foreach ($secretFields as $secretField)
        {
            if (isset($input[$secretField]) === true)
            {
                $output[$secretField] = $terminal->matchEncryptedAttribute(
                    $secretField, $input[$secretField]);
            }
        }

        return $output;
    }

    public function getBanks(string $id): array
    {
        Entity::verifyIdAndSilentlyStripSign($id);

        $terminal = $this->repo->terminal->getById($id);

        $banks = $this->core()->getBanksForTerminal($terminal);

        return $banks;
    }

    public function setBanks(string $id, array $input): array
    {
        Entity::verifyIdAndSilentlyStripSign($id);

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

    public function terminalsMigrateCron(array $input)
    {
        $succesCount = 0;

        $failureCount = 0;


        $terminals = $this->repo->terminal->fetch(['count' => 10]);

        foreach ($terminals as $terminal)
        {
            try
            {
                $this->createTerminalMigrateJob($terminal);

                $succesCount += 1;
            }
            catch (\Throwable $throwable)
            {
                $failureCount += 1;
            }
        }

        return ['successCount' => $succesCount, 'failureCount' => $failureCount];
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

    /**
     * This function is the entrypoint for migrating a terminal to Terminals service.
     * All logic will reside here.
     * @param Entity $terminal
     */
    public function migrateTerminal(string $terminalId)
    {
        $this->repo->transaction(function () use ($terminalId) {
            $client = new TerminalsServiceClient($this->app);

            $terminal = $this->repo->terminal->getById($terminalId);

            $this->repo->terminal->lockForUpdateAndReload($terminal);

            $migrateTerminalResponse = $client->migrateTerminal($terminal);

            $client->fetchTerminalById($terminalId);

            if ($this->isMigrateTerminalSuccess($terminal, $migrateTerminalResponse) === true)
            {
                $this->processMigrateTerminalSuccsess($terminal);
            }
            else
            {
                $this->processMigrateTerminalFailure($terminal);
            }
        });

    }


    protected function createTerminalMigrateJob(Terminal\Entity $terminal)
    {
        $data = [
            Entity::TERMINAL_ID => $terminal->getId(),
        ];

        try
        {
            TerminalsServiceMigrateJob::dispatch($this->mode, $terminal->getId());
        }
        catch (\Exception $exception)
        {
            $data['message'] = $exception->getMessage();

            $data['code']    = $exception->getCode();

            $this->trace->error(TraceCode::TERMINALS_SERVICE_CREATE_MIGRATE_JOB_FAILURE, $data);
        }
    }

    protected function isMigrateTerminalSuccess(Entity $terminal, array $migrateTerminalResponse)
    {
        return false; // TODO add logic here
    }

    protected function processMigrateTerminalSuccsess(Entity $terminal)
    {
        #TODO
    }

    protected function processMigrateTerminalFailure(Entity $terminal)
    {
        #TODO
    }
}
