<?php

namespace RZP\Models\Terminal;

use App;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Error\PublicErrorDescription;
use RZP\Exception;
use ReflectionClass;
use RZP\Models\Base;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Batch;
use RZP\Constants\Mode;
use RZP\Models\Currency\Currency;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Base\RuntimeManager;
use RZP\Error\PublicErrorCode;
use RZP\Constants\Environment;
use RZP\Exception\BaseException;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Jobs\TerminalsServiceMigrateJob;
use RZP\Models\Gateway\Terminal\Constants;
use RZP\Models\Mpan\Constants as MpanConstants;
use RZP\Models\Batch\Processor\TerminalCreation;
use RZP\Models\Batch\Processor\TerminalEdit;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Terminal\Constants as TerminalConstants;
use RZP\Models\Admin\Permission\Name as Permission;



class Service extends Base\Service
{
    use Migrate;

    const  ERROR_PARAMS = [
        "Terminal doesn't exist with this Id",
        "BAD_REQUEST_ACCESS_DENIED"
    ];



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

        $data = $terminals->toArrayAdmin($subMerchantFlag);

        // proxy code
        $mode  = $this->mode ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($mid, "ROUTE_PROXY_TS_MERCHANT_TERMINAL_FETCH", $mode);

        if ($variantFlag === 'proxy'){

            $content = ["merchant_ids" => [$merchant->getId()]];

            $content["sub_merchant"] = $subMerchantFlag;

            $razorxResponse = $this->app->razorx->getTreatment($mid, RazorxTreatment::REMOVE_GET_TERMINALS_PROXY_INVALID_FILTERS, $mode);

            if($razorxResponse === 'control') {
                $content["statuses"] = ["activated", "deactivated"];
            }

            $content["deleted"] = true;

            $path = "v1/merchants/terminals";

            $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

            $dataToCompare = $data["items"];

            foreach ($response as $index => $value)
            {
                    $response[$index]["id"] = "term_" . $response[$index]["id"];
            }

            if ($this->compareArrayOfTerminalArrays($dataToCompare, $response) === false)
            {
                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_MERCHANT_TERMINAL_FETCH_COMPARISON_FAILED, $content);

            }

            $resData = [];
            $resData["entity"] = "collection";
            $resData["count"] = count($response);
            $resData["items"] = $response;

            return $resData;
        }

        return $data;
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

        $params[Entity::STATUS] = Status::ACTIVATED;

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

    protected function validateTerminalBeforeDeletion($merchant, $terminal)
    {
        $env = $this->app['env'];

        if($env !== Environment::PRODUCTION)
        {
            return;
        }

        $variant  = $this->app->razorx->getTreatment($merchant->getId(),
            RazorxTreatment::SKIP_NON_DS_CHECK,
            $this->mode);

        if($variant === 'on')
        {
            return;
        }

        if($merchant->isFeatureEnabled(FeatureConstants::ONLY_DS) === false)
        {
            return;
        }

        $type = $terminal->getType();

        $check = array_intersect($type, [Type::DIRECT_SETTLEMENT_WITHOUT_REFUND,
            Type::DIRECT_SETTLEMENT_WITH_REFUND]);

        if(count($check) === 0)
        {
            return;
        }

        $result = $this->countAllTerminalsOfMerchantAndCheckForTypeArray($terminal->getMerchantId());

        $dsCount = $result['ds_terminals'];

        $nonDsCount = $result['non_ds_terminals'];

        if($dsCount === 1)
        {
            throw new Exception\BadRequestValidationFailureException('Terminal Cannot Be Deleted');
        }
    }

    public function countAllTerminalsOfMerchantAndCheckForTypeArray($merchantId)
    {
        $params = [Entity::MERCHANT_ID => $merchantId];

        $type = [
            Type::DIRECT_SETTLEMENT_WITHOUT_REFUND,
            Type::DIRECT_SETTLEMENT_WITH_REFUND
        ];

        $existingTerminals = $this->repo->terminal->getNonFailedNonDeactivatedByParams($params, false);

        $dsCount = 0;

        $nonDsCount = 0;

        foreach ($existingTerminals as $terminal)
        {
            $types = $terminal->getType();

            if ($terminal->getStatus() != Status::ACTIVATED)
            {
                continue;
            }

            if($terminal->isEnabled() === false)
            {
                continue;
            }

            if(in_array($type[0],$types) === true || in_array($type[1], $types) === true)
            {
                $dsCount++;
            }
            else
            {
                $nonDsCount++;
            }
        }

        return [
            'ds_terminals' => $dsCount,
            'non_ds_terminals' => $nonDsCount
        ];
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

        $this->validateTerminalBeforeDeletion($merchant, $terminal);

        $this->app['workflow']
             ->setEntityAndId($terminal->getEntity(), $terminal->getId())
             ->handle($terminal, (new \stdClass));

        $terminal = $this->repo->deleteOrFail($terminal);

        if ($terminal === null)
            return [];

        return $terminal->toArrayAdmin();
    }

    public function validateDeleteTerminalv3($mid, $tid)
    {
        $this->trace->info(
            TraceCode::TERMINAL_DELETE,
            [
                'merchant_id'       => $mid,
                'terminal_id'       => $tid,
            ]);

        printf($tid);

        $path = "v3/terminals/".$tid;
        $this->app['terminals_service']->proxyTerminalService('', "POST", $path);

    }

    public function deleteTerminalv3($mid, $tid)
    {
        $this->app['workflow']
            ->setEntityAndId('terminal', $tid)
            ->handle(['terminal_id'=>$tid], []);

        $path = "v3/terminals/".$tid;

        $this->app['terminals_service']->proxyTerminalService('', "DELETE", $path);
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

        $this->validateTerminalBeforeDeletion($terminal->merchant, $terminal);

        $terminalArray = $terminal->toArrayAdmin();

        if ( (new Org\Service)->validateEntityOrgId($terminalArray) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }
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

        $terminalArray = $terminal->toArrayAdmin();

        if((new Org\Service)->validateEntityOrgId($terminalArray) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }
        $terminal = (new Terminal\Core)->edit($terminal, $input);

        return $terminal->toArrayAdmin();
    }

    public function getEditableFields()
    {
        $response = [];

        $validatorClass = new ReflectionClass(Validator::class);

        $props = $validatorClass->getStaticProperties();

        foreach ($props as $key => $value)
        {
            if ( str_ends_with($key, 'EditTerminalRules') === true )
            {
                $gatewayCamelCase = str_ireplace('EditTerminalRules', '', $key); // replaces EditTerminalRules from property key to empty string

                $gateway = strtolower(preg_replace("/[A-Z]/", '_' . "$0", $gatewayCamelCase));

                $response[$gateway] = array_keys($value);
            }
        }

        return $response;
    }

    public function bulkAssignPricingPlans($input)
    {
        (new Terminal\Validator())->validateInput('assign_plan', ["input" => $input]);

        $returnData = new PublicCollection();

        foreach ($input as $item)
        {
            try
            {
                $terminal = $this->editTerminal($item[Entity::TERMINAL_ID], [Entity::PLAN_NAME => $item[Entity::PLAN_NAME]]);

                $returnData->push([
                    Entity::TERMINAL_ID => $terminal[Entity::ID],
                    Constants::BATCH_SUCCESS => true,
                    Constants::IDEMPOTENCY_KEY => $item[Constants::IDEMPOTENCY_KEY]
                ]);
            }
            catch (\Throwable $e)
            {
                $returnData->push([
                    Constants::IDEMPOTENCY_KEY => $item[Constants::IDEMPOTENCY_KEY],
                    Constants::BATCH_SUCCESS   => false,
                    Constants::BATCH_ERROR     => [
                        Constants::BATCH_ERROR_DESCRIPTION  => $e->getMessage(),
                        Constants::BATCH_ERROR_CODE         => $e->getCode(),
                    ],
                ]);
            }
        }

        return $returnData->toArrayWithItems();
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


        $this->app['workflow']
            ->setEntityAndId($terminal->getEntity(), $terminal->getId())
            ->handle((new \stdClass), $terminal);

        $r = $this->repo->transaction(function () use ($id, $terminal) {

            $shouldSync = true;

            $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

            $variantFlag = $this->app->razorx->getTreatment($id, "TERMINAL_RESTORE_PROXY", $mode);

            $terminal->restore();

            $tsTerminal = null;

            if ($variantFlag === "restore_terminal")
            {
                $shouldSync = false;

                $path = "/terminal/" . $id . "/restore";

                $res = $this->app['terminals_service']->proxyTerminalService('', "PUT", $path);

                $tsTerminal = self::getEntityFromTerminalServiceResponse($res);

                $terminal->setSyncStatus(SyncStatus::SYNC_SUCCESS);
            }
            else
            {
                $tsTerminal = $terminal;
            }

            $this->repo->saveOrFail($terminal, ['shouldSync' => $shouldSync]);

            return $tsTerminal;
        });

        return $r->toArrayAdmin();
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

        $syncInstruments = false;
        if( isset($input[Constants::SYNC_INSTRUMENTS]) )
        {
            $syncInstruments = $input[Constants::SYNC_INSTRUMENTS];
            unset($input[Constants::SYNC_INSTRUMENTS]);
        }

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

        $terminalArray = $terminal->toArrayAdmin();

        if ( (new Org\Service)->validateEntityOrgId($terminalArray) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        $this->app['workflow']
             ->setEntityAndId($terminal->getEntity(), $terminal->getId())
             ->handle($original, $dirty);

        if( (new Terminal\Core)->getSyncInstrumentsFlagFromWorkflow($terminal,Permission::TOGGLE_TERMINAL) )
        {
            $syncInstruments = true;
        }

        $terminal = (new Terminal\Core)->toggle($terminal, $toggle, [Constants::SYNC_INSTRUMENTS => $syncInstruments]);

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

    public function getWallets(string $id): array
    {
        Entity::verifyIdAndSilentlyStripSign($id);

        $terminal = $this->repo->terminal->getById($id);

        $banks = $this->core()->getWalletsForTerminal($terminal);

        return $banks;
    }

    public function setBanks(string $id, array $input): array
    {
        Entity::verifyIdAndSilentlyStripSign($id);

        $terminal = $this->repo->terminal->getById($id);

        $banksToEnable = $input[Entity::ENABLED_BANKS] ?? [];

        $syncInstruments = false;
        if( isset($input[Constants::SYNC_INSTRUMENTS]) )
        {
            $syncInstruments = $input[Constants::SYNC_INSTRUMENTS];
            unset($input[Constants::SYNC_INSTRUMENTS]);
        }

        $option = [
            'sync_with_terminals_service' => true,
            'bulk_update' => false,
            Constants::SYNC_INSTRUMENTS => $syncInstruments
        ];

        $banks = $this->core()->setBanksForTerminal($terminal, $banksToEnable, $option);

        return $banks;
    }

    public function setWallets(string $id, array $input): array
    {
        Entity::verifyIdAndSilentlyStripSign($id);

        $terminal = $this->repo->terminal->getById($id);

        $walletsToEnable = $input[Entity::ENABLED_WALLETS] ?? [];

        $option = [
            'sync_with_terminals_service' => true,
            'bulk_update' => false,
        ];

        $banks = $this->core()->setWalletsForTerminal($terminal, $walletsToEnable, $option);

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

        $banks = [];

        if (isset($input[Entity::BANK]) === true)
        {
            array_push($banks, $input[Entity::BANK]);
        }

        if (isset($input['banks']) === true)
        {
            $banks = $input['banks'];
        }

        try
        {
            $ids = $input['terminal_ids'];

            $terminals = $this->repo->terminal->findMany($ids);

            $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

            $variantFlag = $this->app->razorx->getTreatment($ids[0], "TERMINAL_EDIT_BANKS_BULK_PROXY", $mode);

            foreach ($terminals as $terminal)
            {
                $terminalId = $terminal->getId();

                $oldBanksList = [];

                try
                {
                    $enabledBanks = $this->core()->getBanksForTerminal($terminal)["enabled"];

                    $oldBanksList = array_keys($enabledBanks);
                }
                catch (\Throwable $t)
                {
                    $returnData[$terminalId] = $t->getMessage();

                    continue;
                }

                $newBanksList = $this->getNewBankList($oldBanksList, $banks, $action);

                //update database only if required
                if (count($oldBanksList) != count($newBanksList))
                {
                    try
                    {
                        $option = [
                            'sync_with_terminals_service' => $variantFlag !== 'on',
                            'bulk_update' => true,
                        ];

                        $updatedEnabledBanks = $this->core()->setBanksForTerminal($terminal, $newBanksList, $option);

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

            if ($variantFlag === "on")
            {
                $input["banks"] = $banks;

                $path = "v1/terminals/banks";

                $response = $this->app['terminals_service']->proxyTerminalService($input, "PUT", $path);

                return $response;
            }

            $returnData["success"] = true;

            return $returnData;
        }
        catch (\Exception $e)
        {
            throw $e;
        }
    }

    public function fillEnabledWallets(array $input)
    {
        $count = 100;
        $success = 0;
        $failed = 0;
        $total = 0;

        $this->trace->info(
            TraceCode::TERMINAL_UPDATE_ENABLED_WALLET_REQUEST,
            $input
        );

        if (isset($input['count']))
        {
            $count = $input['count'];
        }

        // fetch methods from slave, debit_emi_provider value as null
        $terminals = $this->repo->useSlave(function() use ($count)
        {
            return $this->repo->terminal->getTerminalsWithNullEnabledWallets($count);;

        });

        foreach($terminals as $terminal)
        {
            try
            {
                $total++;

                $gateway = $terminal->getGateway();

                $supportedWallets = Payment\Gateway::getSupportedWalletsForGateway($gateway);

                $terminal->setEnabledWallets($supportedWallets);

                $this->repo->saveOrFail($terminal);

                $success++;
            }
            catch(\Throwable $ex)
            {
                $this->trace->traceException($ex,
                    Trace::ERROR,
                    TraceCode::TERMINAL_UPDATE_ENAABLED_WALLET_FAILED,
                    [
                        'terminal_id'   =>  $terminal->getId()
                    ]);

                $failed++;
            }
        }

        $res = ["count" => $count, "total" => $total, "success" => $success, "failed" => $failed];

        $this->trace->info(
            TraceCode::TERMINAL_UPDATE_ENABLED_WALLET_RESULT,
            $res
        );
        return $res;

    }

    public function updateTerminalsBulk(array $input)
    {
        $app = App::getFacadeRoot();

        $this->trace->info(
            TraceCode::TERMINAL_BULK_UPDATE_REQUEST,
            $input
        );

        $this->increaseAllowedSystemLimits();

        $validator = (new Validator());

        $validator->validateInput('updateTerminalsBulk', $input);

        // Although core will run individual validations for gateway, the terminal belongs to, currently we want to allow only, tatus update using bulkupdate api
        // so adding this custom validation to allow only status update, this can be updated to allow more attributes to be updated
        $validator->validateInput('updateTerminalsBulkAttributes', $input['attributes']);

        $enabledStatus = $input['attributes']['enabled'];

        $enabled = $this->setEnabledToValidValues($enabledStatus);

        unset($input['attributes']['enabled']);

        // TODO: make this usable by all gateways
        if (($enabled === true) and
            (in_array($input['attributes']['status'], Terminal\Status::POSSIBLE_STATUS_FOR_WORLDLINE_ENABLED_TERMINAL) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TERMINAL_STATUS_SHOULD_BE_ACTIVATED_OR_PENDING_TO_ENABLE);
        }

        $terminalIds = $input['terminal_ids'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($terminalIds as $terminalId)
        {
            try
            {
                $terminal = $this->repo->terminal->findOrFailPublic($terminalId);

                $this->core()->edit($terminal, $input['attributes']);

                $this->core()->toggle($terminal, $enabled);

                // dispatch terminal.activated or terminal.failed webhook, if required
                if (isset($input['attributes'][Entity::STATUS]) === true)
                {
                    if ($input['attributes'][Entity::STATUS] === Status::FAILED)
                    {
                        $app['events']->dispatch('api.terminal.failed', ['main' => $terminal]);
                    }
                    else if ($input['attributes'][Entity::STATUS] === Status::ACTIVATED)
                    {
                        $app['events']->dispatch('api.terminal.activated', ['main' => $terminal]);
                    }
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

    public function postTerminalsBulk($input)
    {
        $response = new Base\PublicCollection;

        foreach ($input as $row)
        {
            $rowOutput = $this->processTerminalCreationBulkRow($row);

            $response->add($rowOutput);
        }

        return $response;
    }

    public function processTerminalCreationBulkRow(array $row)
    {
        $result = [
            Constants::IDEMPOTENCY_KEY        => $row[Constants::IDEMPOTENCY_KEY],
            Constants::BATCH_SUCCESS          => false,
            Constants::BATCH_HTTP_STATUS_CODE => 500,
            Constants::TERMINAL_ID            => '',
            Constants::BATCH_ERROR => [
                Constants::BATCH_ERROR_CODE        => '',
                Constants::BATCH_ERROR_DESCRIPTION => '',
            ],
        ];

        $result = array_merge($result, $row);

        try
        {
            (new TerminalCreation())->processEntry($row);

            $result[Constants::BATCH_SUCCESS] = true;
            $result[Constants::TERMINAL_ID]  =  $row[Constants::TERMINAL_ID];
            $result[Constants::BATCH_HTTP_STATUS_CODE] = 201;
        }
        catch(BaseException $exception)
        {
            $result[Constants::BATCH_ERROR] = [
                Constants::BATCH_ERROR_DESCRIPTION => $exception->getMessage(),
                Constants::BATCH_ERROR_CODE => $exception->getPublicError(),
            ];

            $result[Constants::BATCH_HTTP_STATUS_CODE] = $exception->getCode();
        }
        catch (\Throwable $throwable)
        {
            $result[Constants::BATCH_ERROR] = [
                Constants::BATCH_ERROR_DESCRIPTION => $throwable->getMessage(),
                Constants::BATCH_ERROR_CODE => PublicErrorCode::SERVER_ERROR,
            ];

            $result[Constants::BATCH_HTTP_STATUS_CODE] = $throwable->getCode();
        }

        $this->redactSensitiveHeadersFromResult($result);

        return $result;
    }

    protected function redactSensitiveHeadersFromResult(array & $result)
    {
        foreach($result as $key => $value)
        {
            if ((in_array($key, Batch\Header::HEADER_MAP[Batch\Type::TERMINAL_CREATION][Batch\Header::SENSITIVE_HEADERS], true) == true)
                and (empty($value) == false ))
            {
                $result[$key] = 'redacted';
            }
        }
    }

    public function terminalsMigrateCron(array $input)
    {
        $succesCount = 0;

        $failureCount = 0;

        (new Terminal\Validator)->validateInput('migrate_terminals_cron', $input);

        $mode = 'sqs';

        if (isset($input['mode']) === true){
            $mode = $input['mode'];
        }

        if (isset($input["ids"]) === true)
        {
            $ids = $input["ids"];

            $terminals = $this->repo->terminal->getByTerminalIds($ids, false);
        }
        else
        {
            $terminals = $this->repo->terminal->fetchForSyncToTerminalsService($input);
        }

        foreach ($terminals as $terminal)
        {
            try
            {
                if ($mode !== 'sync')
                {
                    $this->createTerminalMigrateJob($terminal);

                    $terminal->setSyncStatus(SyncStatus::SYNC_IN_PROGRESS);

                    $this->repo->terminal->saveOrFail($terminal, ['shouldSync' => false]);
                }
                else
                {
                    $terminal->setSyncStatus(SyncStatus::SYNC_SUCCESS);

                    $this->repo->terminal->saveOrFail($terminal, ['shouldSync' => true]);
                }

                $succesCount += 1;
            }
            catch (\Throwable $throwable)
            {
                $failureCount += 1;
            }
        }

        return ['successCount' => $succesCount, 'failureCount' => $failureCount];
    }

    public function hitachiTerminalsCurrencyUpdateCron(int $limit)
    {
        $successCount = 0;
        $failedCount = 0;
        $total = 0;
        $failedIds = [];

        $terminals = $this->repo->terminal->getHitachiTerminalsForCurrencyOrStatusUpdate($limit);

        foreach ($terminals as $terminal)
        {
            $total++;
            $this->trace->info(
                TraceCode::HITACHI_TERMINAL_CURRENCY_UPDATE_START,
                [
                    'terminal_id' => $terminal->getId(),
                ]);
            try
            {
                if (in_array(Currency::INR, $terminal->getCurrency()) === false)
                {
                    $input = [Entity::STATUS => Status::DEACTIVATED];
                }
                else
                {
                    $input = [Entity::CURRENCY => Currency::SUPPORTED_CURRENCIES];
                }

                $this->editTerminal($terminal->getId(), $input);

                $successCount++;
            }
            catch (\Throwable $ex)
            {
                $failedIds[] = $terminal->getId();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::HITACHI_TERMINAL_CURRENCY_UPDATE_EXCEPTION, ['terminal_id' => $terminal->getId()]);
                $failedCount++;
            }
        }

        return ["success"=> $successCount, "failed"=> $failedCount,  "total" => $total, "failedIds" => $failedIds];
    }

    /**
     * Add/Remove bank from the oldEnabledBankList adn return the newList.
     *
     * @param  array    $oldList
     * @param  array    $banks
     * @param  string    $action
     *
     * @return array
     */
    protected function getNewBankList(array $oldList, array $banks, string $action): array
    {
        foreach ($banks as $bank)
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
    public function migrateTerminalCreateOrUpdate(string $terminalId, array $options = array()) : Entity
    {
        $client = $this->app['terminals_service'];

        $terminal = $this->repo->terminal->getById($terminalId, true, false);

        $terminal = $this->repo->transaction(function () use ($terminal, $client, $options) {

            $this->repo->terminal->lockForUpdateAndReload($terminal);

            $migrateTerminalResponse = $client->migrateTerminal($terminal, $options);

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

            try{
                $this->repo->terminal->lockForUpdateAndReload($terminal);
            }catch (DbQueryException $e){
                $this->trace->traceException($e, Trace::ERROR, TraceCode::DB_QUERY_EXCEPTION);
                //We only need to delete terminals on API service if its present on both TS and API service
                if(!$terminal->isTerminalOnlyOnTerminalsService()){
                    throw $e;
                }
            }

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

                $statusCode = (int)($exceptionData['status_code']);

                if (($statusCode === 400) and
                    ($exception->getCode() === ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_ERROR) and
                    ($exception->getMessage() === 'Terminal doesn\'t exist with this Id'))
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
                    ($exception->getCode() === ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_ERROR) and
                    ( in_array($exception->getMessage() ,self::ERROR_PARAMS) === true))
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

        try
        {
            $client->addMerchantToTerminal($terminal, $merchant);
        }
        catch (\Exception $exception)
        {
            $exceptionData = $exception->getData();

            if ($exceptionData === null)
            {
                throw $exception;
            }

            $statusCode = (int)($exceptionData['status_code']);

            if (($statusCode === 400) and
                ($exception->getCode() === ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_ERROR) and
                ($exception->getMessage() === "Terminal Submerchant already exist"))
            {
                $this->app['trace']->info(TraceCode::TERMINALS_SERVICE_TERMINAL_SUBMERCHANT_ALREADY_EXISTS,
                    [
                        Entity::TERMINAL_ID => $terminal->getId(),
                        Entity::MERCHANT_ID => $merchant->getId(),
                    ]);
            }
            else
            {
                throw $exception;
            }
        }

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

        try
        {
            $client->removeMerchantFromTerminal($terminal, $merchant);
        }
        catch (\Exception $exception)
        {
            $exceptionData = $exception->getData();

            if ($exceptionData === null)
            {
                throw $exception;
            }

            $statusCode = (int)($exceptionData['status_code']);

            if (($statusCode === 400) and
                ($exception->getCode() === ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_ERROR) and
                ($exception->getMessage() === "Terminal Submerchant relation doesn't exist"))
            {
                $this->app['trace']->info(TraceCode::TERMINALS_SERVICE_TERMINAL_SUBMERCHANT_ALREADY_DELETED,
                    [
                        Entity::TERMINAL_ID => $terminal->getId(),
                        Entity::MERCHANT_ID => $merchant->getId(),
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
                ($exception->getCode() === ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_ERROR))
            {

            }
            else
            {
                throw $exception;
            }
        }
    }

    // existing means those which were already stored without tokenization
    public function tokenizeExistingMpans($input)
    {
        $this->trace->info(
            TraceCode::TERMINAL_TOKENIZE_EXISTING_MPANS_REQUEST,
            $input
        );

        $validator = new Validator();

        $validator->validateInput('tokenize_existing_mpans', $input);

        $response = [
            MpanConstants::TOKENIZATION_SUCCESS_COUNT         => 0,
            MpanConstants::TOKENIZATION_FAILED_COUNT          => 0,
            MpanConstants::TOKENIZATION_SUCCESS_TERMINAL_IDS  => [],
            Mpanconstants::TOKENIZATION_FAILED_TERMINAL_IDS   => [],
        ];

        $count = $input['count'] ?? 100;

        $terminalIds = $input['terminal_ids'] ?? [];

        $terminals = $this->repo->terminal->fetchTerminalsForTokenization($count, $terminalIds);

        foreach($terminals as $terminal)
        {
            try
            {
                foreach([Entity::MC_MPAN, Entity::VISA_MPAN, Entity::RUPAY_MPAN] as $network)
                {
                    // adding same mpans as input params, actual tokenization will happen in core edit function
                    $editInput[$network] = isset($terminal[$network]) ? $terminal[$network] : '';
                }

                (new Core)->edit($terminal, $editInput);

                $response[MpanConstants::TOKENIZATION_SUCCESS_COUNT]++;
                $response[MpanConstants::TOKENIZATION_SUCCESS_TERMINAL_IDS][] = $terminal->getId();
            }
            catch(\Throwable $ex)
            {
                $this->trace->traceException($ex,
                    Trace::ERROR,
                    TraceCode::MPAN_TOKENIZATION_FAILED,
                    [
                        'terminal_id'   =>  $terminal->getId()
                    ]);

                $response[MpanConstants::TOKENIZATION_FAILED_COUNT]++;
                $response[MpanConstants::TOKENIZATION_FAILED_TERMINAL_IDS][] = $terminal->getId();
            }
        }

        $this->trace->info(
            TraceCode::TERMINAL_TOKENIZE_EXISTING_MPANS_RESPONSE,
            $response
        );

        return $response;
    }

    public function runGetTerminalsForMerchantComparison($terminals, Merchant\Entity $merchant, bool $submerchantFlag)
    {
        try
        {
            $content = ["merchant_ids" => [$merchant->getId()]];

            $content["sub_merchant"] = $submerchantFlag;

            $content["status"] = Status::ACTIVATED;

            $path = "v1/merchants/terminals";

            $fetchedTerminals = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

            $this->compareFetchedTerminals($terminals, $fetchedTerminals, $submerchantFlag);
        }
        catch (\Exception $exception)
        {
        }
    }

    protected function compareFetchedTerminals($terminals, $fetchedTerminals, $submerchantFlag)
    {
        if ($this->compareFetchedTerminalIds($terminals, $fetchedTerminals) === false)
        {
            return;
        }
        foreach ($fetchedTerminals as $fetchedTerminal)
        {
            $terminal = $terminals->find($fetchedTerminal[Terminal\Entity::ID]);

            $this->compareFetchedTerminal($terminal, $fetchedTerminal, $submerchantFlag);
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

    public function addMswipeTerminals($merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $terminals = $this->repo->useSlave(function () use ($merchant)
        {
            return (new Terminal\Repository())->getTerminalsForMerchantAndSharedMerchant($merchant);
        });

        $this->checkAndAddMswipeTerminals($terminals, $merchant);
    }

    public function checkAndAddMswipeTerminals($terminals, $merchant) :bool
    {

        try {
            $mswipeTerminalIds = ['C7EW8LggSH7FnY', 'CXjvHPZlPnqWBX', 'CNqL80h9pI0hsI', 'CHYaN0FnjkG5ni',
                'CWybuzsFqa9KDz'];

            if ($merchant->isUseMswipeTerminalsEnabled() === false)
            {
                return false;
            }

            $terminalIds = $this->getTerminalIds($terminals);

            $diff = array_diff($mswipeTerminalIds, $terminalIds);

            if (count($diff) === 0) {
                return false;
            }

            foreach ($mswipeTerminalIds as $mswipeTerminalId) {
                if (in_array($mswipeTerminalId, $terminalIds) === true) {
                    continue;
                }
                $this->addMerchantToTerminal($mswipeTerminalId, $merchant->getId());
            }

            // disabling cache for this merchant for terminal fetch as merchant has been added as submerchant to other
            // terminals, new fetch result will have these extra terminals in result.
            $cacheTag = Entity::getCacheTag($merchant->getId());

            (new Entity)->flushCache($cacheTag);

            return true;
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::PAYMENTS_MWSIPE_TERMINAL_ASSIGNEMENT_ERROR,
                [
                    'error'     => $e->getMessage(),
                ]);
        }

        return false;
    }

    public function getMerchantTerminalsForGateway($merchantId, $orgId, $gateway):array
    {
        $input= [
            'org_id' => $orgId,
            'gateway' => $gateway,
            'merchant_ids' => [$merchantId],
        ];

        $path = "v1/merchants/terminals";

        $response = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::POST,
            $path
        );

        $terminalsRedactedResponse = [];

        foreach ($response as $terminal)
        {
            array_push($terminalsRedactedResponse, [
                'terminal_id'          => $terminal['terminal_id'],
                'gateway_terminal_id'  => $terminal['gateway_terminal_id'],
                'gateway_merchant_id'  => $terminal['gateway_merchant_id'],
            ]);
        }

        return $terminalsRedactedResponse;
    }

    protected function getTerminalIds($terminals)
    {
        $terminalIds = [];

        foreach ($terminals as $terminal)
        {
            $terminalIds[] = $terminal->getId();
        }

        return $terminalIds;

    }
    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setTimeLimit(300);
    }

    protected function setEnabledToValidValues($enabledStatus)
    {
        if($enabledStatus == 1 || $enabledStatus == true) // "1", 1, true, "true"
        {
            return true;
        }
        return false;
    }

    public function triggerInstrumentRulesEventBulk($input)
    {
        $merchantIds = $input['merchant_ids'];

        if(count($merchantIds) > 50)
        {
            throw new Exception\BadRequestException(
                ErrorCode:: BAD_REQUEST_INPUT_VALIDATION_FAILURE, null, "The number of input merchant_ids should be less than 50");
        }

        $this->trace->info(
            TraceCode::INSTRUMENT_EVENT_RULES_TRIGGER , [
            'merchant_ids' => $merchantIds,
        ]);

        $failedIds = [];
        $successIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $data = $this->consumeInstrumentRulesEvent($merchantId, true);

                if(empty($data))
                {
                    throw new Exception\LogicException(null,null, [
                            'merchant_id'   => $merchantId,
                    ]);
                }

                $successIds[] = $merchantId;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex, Trace::ERROR,
                    TraceCode::INSTRUMENT_EVENT_RULES_TRIGGER_EXCEPTION,
                    [
                        'merchant_id'   =>  $merchantId
                    ]);

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'failed_ids' => $failedIds,
            'success_ids' => $successIds,
        ];

        return $response;
    }

    public function logRouteName(string $terminalId)
    {
        $shouldLogRetrievalEvent = false;

        if($shouldLogRetrievalEvent === true) {
            $app = App::getFacadeRoot();

            $ba = $app['basicauth'];

            $routeName =  $app['request.ctx']->getRoute();

            $this->trace->info(TraceCode::TERMINALS_RETRIEVAL_EVENT, [
                'terminal_id' => $terminalId,
                'route_name'  => $routeName,
            ]);
        }
    }

    public function consumeInstrumentRulesEvent(string $merchantId, bool $forceTrigger = false): array
    {
        $start = millitime();

        $eventData = [
            Merchant\Detail\Entity::MERCHANT_ID          => '',
            Merchant\Entity::ORG_ID                      => '',
            Merchant\Entity::CATEGORY                    => '',
            Merchant\Entity::CATEGORY2                   => '',
            Merchant\Entity::WEBSITE                     => '',
            Merchant\Detail\Entity::BUSINESS_TYPE        => '',
            Merchant\Detail\Entity::ACTIVATION_STATUS    => '',
            Merchant\Detail\Entity::BUSINESS_SUBCATEGORY => '',
        ];

        $response = [];

        try
        {
            $routeName = $this->app['api.route']->getCurrentRouteName()?? '';

            if(in_array($routeName, TerminalConstants::SKIP_INSTRUMENT_EVENT_RULES_TRIGGER_ROUTES) == true)
            {
                $this->trace->info(TraceCode::INSTRUMENT_EVENT_RULES_TRIGGER_SKIPPED, [
                    'merchant_id' => $merchantId,
                ]);

                return [];
            }

            $skipEventRulesTrigger = (new \RZP\Models\Merchant\Methods\Core)->validateRuleBasedFeatureFlagForMerchant($merchantId);

            //if 'rule_based_enablement' feature is enabled, consuming of events has to be skipped
            if ($skipEventRulesTrigger)
            {
                $this->trace->info(TraceCode::INSTRUMENT_EVENT_RULES_TRIGGER_SKIPPED, [
                    'merchant_id' => $merchantId,
                ]);

                return [];
            }

            $this->trace->info(TraceCode::INSTRUMENT_EVENT_RULES_TRIGGER, ['merchant_id' => $merchantId]);

            /**
             * @var Merchant\Entity $merchant
             */
            $merchant = $this->repo->merchant->findOrFailPublicWithRelations($merchantId, ['merchantDetail']);
            if ($merchant != null && $merchant->merchantDetail != null)
            {

                $eventData[Merchant\Detail\Entity::MERCHANT_ID] = $merchant->getId();
                $eventData[Merchant\Entity::ORG_ID] = $merchant->getOrgId();
                $eventData[Merchant\Entity::CATEGORY] = $merchant->getCategory();
                $eventData[Merchant\Entity::CATEGORY2] = $merchant->getCategory2();

                $merchantDetail = $merchant->merchantDetail;

                $eventData[Merchant\Detail\Entity::BUSINESS_TYPE] = $merchantDetail->getBusinessType();
                $eventData[Merchant\Detail\Entity::ACTIVATION_STATUS] = $merchant->getAccountStatus();
                $eventData[Merchant\Entity::WEBSITE] = $merchantDetail->getWebsite();
                $eventData[Merchant\Detail\Entity::BUSINESS_SUBCATEGORY] = $merchantDetail->getBusinessSubcategory();
                try
                {
                    (new Terminal\Validator)->validateInput('instrument_rule_eval', $eventData);
                }
                catch (\Throwable $e)
                {
                    $this->trace->error(TraceCode::INSTRUMENT_EVENT_RULES_TRIGGER_SKIPPED,
                        [
                            'merchant_id' => $merchantId,
                            'error' => $e->getMessage(),
                        ]);
                    return $response;
                }
            }

            $durationDataGenerate = millitime() - $start;

            $input = array("instrument_rules_event_data"=> $eventData,"force_trigger"=> $forceTrigger);

            $response = $this->app['terminals_service']->consumeInstrumentRulesEvaluationEvent($input);

            $durationEventPush = millitime() - $durationDataGenerate;

            $this->trace->info(TraceCode::INSTRUMENT_EVENT_RULES_METRICS,
                [
                    'input' => $input,
                    'data_generate_time' => $durationDataGenerate,
                    'event_push_time'   => $durationEventPush,
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::INSTRUMENT_EVENT_RULES_TRIGGER_EXCEPTION, ['merchant_id' => $merchantId]);
        }

        return $response;
    }
}
