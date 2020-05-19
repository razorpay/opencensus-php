<?php

namespace RZP\Models\Terminal;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\TerminalsServiceMigrateJob;
use RZP\Models\TerminalOnboardingDetail;


class Service extends Base\Service
{
    use Migrate;

    public function createTerminal($id, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $terminal = (new Terminal\Core)->create($input, $merchant);

        return $terminal;
    }

    public function createTerminalWithId($merchantId, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $terminal = (new Terminal\Core)->createWithId($input, $merchant);

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

    // This is used when merchant dashboard fetches terminals via proxy auth
    public function proxyGetTerminals(string $mid, array $input)
    {
        $params = $input;

        $params[Entity::MERCHANT_ID] = $mid;

        $terminals = $this->repo->terminal->getByParams($params);

        // If no terminal exist for wallet_paypal, fetch from terminals service
        if ( ($terminals->count() === 0) and
            ( (isset($input['gateway']) === true))  and ($input['gateway'] === Payment\Gateway::WALLET_PAYPAL) )
        {
            $data =  $this ->app['terminals_service']->getTerminalsByMerchantIdAndGateway($mid, Payment\Gateway::WALLET_PAYPAL);

            $arrayPublic = $this->terminalsServiceDataToArrayPublic($data);

            return $arrayPublic;
        }

        return $terminals->toArrayPublic();
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

    public function updateTerminalsBulk(array $input)
    {
        $app = App::getFacadeRoot();

        $this->trace->info(
            TraceCode::TERMINAL_BULK_UPDATE_REQUEST,
            $input
        );

        $validator = (new Validator());

        $validator->validateInput('updateTerminalsBulk', $input);

        // Although core will run individual validations for gateway, the terminal belongs to, currently we want to allow only, tatus update using bulkupdate api
        // so adding this custom validation to allow only status update, this can be updated to allow more attributes to be updated
        $validator->validateInput('updateTerminalsBulkAttributes', $input['attributes']);

        $enabled = $input['attributes']['enabled'];

        unset($input['attributes']['enabled']);

        if (($input['attributes']['status'] !== Terminal\Status::ACTIVATED) and ($enabled === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TERMINAL_STATUS_SHOULD_BE_ACTIVATED_TO_ENABLE);
        }

        $terminalIds = $input['terminal_ids'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($terminalIds as $terminalId)
        {
            try
            {
                $terminal = $this->repo->terminal->findOrFailPublic($terminalId);

                // We are not allowing created terminals to be updated because created terminal is yet to be sent to gateway
                if ($terminal->getStatus() === Terminal\Status::CREATED)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_CREATED_TERMINAL_CANNOT_BE_BULK_UPDATED);
                }

                $this->core()->edit($terminal, $input['attributes']);

                $this->core()->toggle($terminal, $enabled);

                $terminalOnboardingDetail = $terminal->terminalOnboardingDetail;

                if ( ($terminalOnboardingDetail !== null) and isset($input['attributes'][Entity::STATUS]) )
                {
                    if ($input['attributes'][Entity::STATUS] === Status::FAILED)
                    {
                        $terminalOnboardingDetail->setStatus(TerminalOnboardingDetail\Status::FAILED);

                        $app['events']->fire('api.terminal.failed', ['main' => $terminal]);
                    }
                    else if ($input['attributes'][Entity::STATUS] === Status::ACTIVATED)
                    {
                        $terminalOnboardingDetail->setStatus(TerminalOnboardingDetail\Status::ACTIVATED);

                        $app['events']->fire('api.terminal.activated', ['main' => $terminal]);
                    }

                    $terminalOnboardingDetail->save();
                }

                $successCount++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex,
                Trace::ERROR,
                TraceCode::TERMINAL_BULK_UPDATE_FAILED,
                [
                    'terminal_id'   =>  $terminal->getId()
                ]);

                $failedCount++;

                $failedIds[] = $terminalId;
            }
        }

        $response = [
            'total'     => count($terminalIds),
            'success'   => $successCount,
            'failed'    => $failedCount,
            'failedIds' => $failedIds,
        ];

        $this->trace->info(
            TraceCode::TERMINAL_BULK_UPDATE_RESPONSE,
            $response
        );

        return $response;
    }

    public function terminalsMigrateCron(array $input)
    {
        $succesCount = 0;

        $failureCount = 0;

        (new Terminal\Validator)->validateInput('migrate_terminals_cron', $input);

        if (isset($input["ids"]) === true)
        {
            $ids = $input["ids"];

            $terminals = $this->repo->terminal->getByTerminalIds($ids);
        }
        else
        {
            $terminals = $this->repo->terminal->fetchForSyncToTerminalsService($input);
        }

        foreach ($terminals as $terminal)
        {
            try
            {
                $this->createTerminalMigrateJob($terminal);

                $terminal->setSyncStatus(SyncStatus::SYNC_IN_PROGRESS);

                $this->repo->terminal->saveOrFail($terminal, ['shouldSync' => false]);

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

    public function fetchTerminalById(string $id): array
    {
        $terminal = $this->repo->terminal->getByIdNonDeleted($id);

        $data = $terminal->toArrayPublic();

        return $data;
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

            try
            {
                $client->deleteTerminalById($terminal->getId());
            }
            catch (\Exception $exception)
            {
                $exceptionData = $exception->getData();

                if ($exceptionData === null)
                {
                    throw $exception;
                }

                $error = $exceptionData['response']['error'];

                $statusCode = (int)($exceptionData['status_code']);

                if (($statusCode === 400) and
                    ($error['internal_error_code'] === ErrorCode::BAD_REQUEST_ERROR) and
                    ($error['description']) === 'Terminal doesn\'t exist with this Id')
                {
                    $this->app['trace']->info(TraceCode::TERMINALS_SERVICE_TERMINAL_ALREADY_DELETED,
                        [
                            Entity::ID => $terminal->getId(),
                        ]);
                }
                else
                {
                    throw $exception;
                }
            }

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

    protected  function terminalsServiceDataToArrayPublic($terminalData)
    {
        $items = [];

        foreach($terminalData as $terminal)
        {
            $item = [
                Terminal\Entity::ID            => $terminal[Terminal\Entity::ID],
                Terminal\Entity::ENTITY        => 'terminal',
                Terminal\Entity::STATUS        => $terminal[Terminal\Entity::STATUS ],
                Terminal\Entity::ENABLED       => $terminal[Terminal\Entity::ENABLED ],
                Terminal\Entity::MPAN          => $terminal[Terminal\Entity::MPAN],
                Terminal\Entity::NOTES         => $terminal[Terminal\Entity::NOTES],
                Terminal\Entity::CREATED_AT    => $terminal[Terminal\Entity::CREATED_AT],
            ];

            array_push($items, $item);
        }

        $arrayPublic = [
            Terminal\Entity::ENTITY => 'collection',
            'count'      => sizeof($terminalData),
            'items'      =>  $items
        ];

        return $arrayPublic;
    }

}
