<?php

namespace RZP\Models\Terminal;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Jobs\TerminalsServiceMigrateJob;

class Service extends Base\Service
{
    use Migrate;

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

        if (Migrate::shouldRunComparison() === true)
        {
            $this->runGetTerminalsForMerchantComparison($terminals, $merchant);
        }

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

        $validator = (new Terminal\Validator)->validateInput('migrate_terminals_cron', $input);

        $terminals = $this->repo->terminal->fetchForSyncToTerminalsService($input);

        foreach ($terminals as $terminal)
        {
            try
            {
                $this->createTerminalMigrateJob($terminal);

                $terminal->setSyncStatus(SyncStatus::SYNC_IN_PROGRESS);

                $this->repo->terminal->saveOrFail($terminal, [],SyncStatus::SYNC_IN_PROGRESS);

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
     * All logic will reside here for create and update
     * @param Entity $terminal
     */
    public function migrateTerminalCreateOrUpdate(string $terminalId) : Entity
    {
        $client = $this->app['terminals_service'];

        $terminal = $this->repo->terminal->getById($terminalId);


        $terminal = $this->repo->transaction(function () use ($terminal, $client) {


            $this->repo->terminal->lockForUpdateAndReload($terminal);

            if ($terminal->isSyncStatusSuccess() === true)
            {
                $data = [
                    Entity::TERMINAL_ID         => $terminal->getId(),
                ];

                $this->trace->info(TraceCode::TERMINALS_SERVICE_TERMINAL_ALREADY_SYNCED, $data);

                return $terminal;
            }

            $migrateTerminalResponse = $client->migrateTerminal($terminal);

            $fetchTerminalResponse = $client->fetchTerminalById($terminal->getId());

            if ($this->isMigrateTerminalSuccess($terminal, $fetchTerminalResponse) === true)
            {
                $this->processMigrateTerminalSuccess($terminal);

                return $terminal;
            }
            else
            {
                $this->processMigrateTerminalFailure($terminal);
            }
        });

        return $terminal;

    }

    public function migrateTerminalDelete(string $terminalId)
    {
        $client = $this->app['terminals_service'];

        $terminal = $this->repo->terminal->getById($terminalId);

        $terminal = $this->repo->transaction(function () use ($terminal, $client) {

        $this->repo->terminal->lockForUpdateAndReload($terminal);

        $client->deleteTerminalById($terminal->getId());

        $data = [];

        try
        {
            $data = $client->fetchTerminalById($terminal->getId());

            if ($data !== [])
            {
                throw new Exception\IntegrationException(
                    'delete failed on terminals service side
                    got non empty response when fetching a deleted terminal
                    . should not have reached here',
                    ErrorCode::SERVER_ERROR_TERMINALS_SERVICE_INTEGRATION_ERROR);
            }
        }
        catch (\Exception $exception)
        {
            // assert on message and rethrow if not correct
            if (($data === []) and
                ($exception->getCode() === ErrorCode::SERVER_ERROR_TERMINALS_SERVICE_INTEGRATION_ERROR))
            {

            }
            else
            {
                throw $exception;
            }
        }

    });
    }

    public function migrateTerminalAddMerchant(Terminal\Entity $terminal, Merchant\Entity $merchant)
    {
        $client = $this->app['terminals_service'];

        $client->addMerchantToTerminal($terminal, $merchant);

        $merchant_terminal_fetched = $client->fetchMerchantTerminalById($terminal->getId(), $merchant->getId());

        $original = [
            Terminal\Entity::TERMINAL_ID    => $terminal->getId(),
            Merchant\Entity::MERCHANT_ID    => $merchant->getId(),
        ];

        $data = [
            'original' => $original,
            'fetched'  => $merchant_terminal_fetched,
        ];

        if ($merchant_terminal_fetched === [])
        {

             throw new Exception\IntegrationException('merchant_terminal does not exist',
                 ErrorCode::SERVER_ERROR_TERMINALS_SERVICE_INTEGRATION_ERROR,
                 $data);
        }

        if (array_diff_assoc($original, $merchant_terminal_fetched) !== [])
        {
            throw new Exception\IntegrationException(
                'Mismatch in values while fetching from merchant_terminal table',
                ErrorCode::SERVER_ERROR_TERMINALS_SERVICE_INTEGRATION_ERROR,
                $data);
        }
    }

    public function migrateTerminalRemoveMerchant(Terminal\Entity $terminal, Merchant\Entity $merchant)
    {
        $client = $this->app['terminals_service'];

        $client->removeMerchantFromTerminal($terminal, $merchant);

        $data = [];

        try
        {
            $data = $client->fetchMerchantTerminalById($terminal->getId(), $merchant->getId());

            if ($data !== [])
            {
                throw new Exception\IntegrationException(
                    'delete failed on terminals service side
                    got non empty response when fetching a deleted terminal
                    . should not have reached here',
                    ErrorCode::SERVER_ERROR_TERMINALS_SERVICE_INTEGRATION_ERROR);
            }
        }
        catch (\Exception $exception)
        {
            // assert on message and rethrow if not correct
            if (($data === []) and
                ($exception->getCode() === ErrorCode::SERVER_ERROR_TERMINALS_SERVICE_INTEGRATION_ERROR))
            {

            }
            else
            {
                throw $exception;
            }
        }
    }

    public function runGetTerminalsForMerchantComparison($terminals, Merchant\Entity $merchant)
    {
        try
        {
            $fetchedTerminals = $this->app['terminals_service']->getTerminalsByMerchantId($merchant->getId());

            $this->compareFetchedTerminals($terminals, $fetchedTerminals);
        }
        catch (\Exception $exception)
        {
        }
    }

    protected function compareFetchedTerminals($terminals, $fetchedTerminals)
    {
        if ($this->compareFetchedTerminalIds($terminals, $fetchedTerminals) === false)
        {
            return;
        }

        foreach ($fetchedTerminals as $fetchedTerminal)
        {
            $terminal = $terminals->find($fetchedTerminal[Terminal\Entity::ID]);

            $this->compareFetchedTerminal($terminal, $fetchedTerminal);
        }
    }

    protected function createTerminalMigrateJob(Terminal\Entity $terminal)
    {
        try
        {
            TerminalsServiceMigrateJob::dispatch($this->mode, $terminal->getId());
        }
        catch (\Exception $exception)
        {
            $data = [
                Entity::TERMINAL_ID => $terminal->getId(),
                'message'           => $exception->getMessage(),
                'code'              => $exception->getCode(),
            ];

            $this->trace->error(TraceCode::TERMINALS_SERVICE_CREATE_MIGRATE_JOB_FAILURE, $data);
        }
    }
}
