<?php

namespace RZP\Models\Terminal;

use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Pricing;
use RZP\Constants\Procurer;
use RZP\Constants\Environment;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Feature\Constants;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Mpan\Entity as MpanEntity;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Gateway\Terminal\Service as GatewayTerminalService;
use RZP\Models\Workflow\Action;
use RZP\Models\Gateway\Terminal\Constants as TerminalConstants;
use RZP\Models\Admin\Permission\Name as Permission;

class Core extends Base\Core
{
    const TEST_MODE_ACCOUNT_NUMBER_SERIES_PREFIX = '232323';

    public function create($input, $merchant, bool $shouldSync = true)
    {
        $this->trace->info(
            TraceCode::TERMINAL_CREATE_REQUEST,
            [
                'input'         => $this->removeSecretFieldsForTrace($input),
                'merchant_id'   => $merchant->getId(),
            ]);

        $this->validateAndTokenizeMpansIfPresentInInput($input);

        $this->validateGatewayAllowed($input); // we do not want to allow the assigning of specific terminals, eg paysecure from admin dashboard

        $this->validateAcquirerByCountry($merchant, $input);

        $input['merchant_id'] = $merchant->getKey();

        $input['org_id'] = $merchant->getOrgId();

        $this->validateBuyPricing($input);

        $terminal = (new Entity)->build($input);

        $terminal->merchant()->associate($merchant);

        $terminal->org()->associate($merchant->org);

        $this->validateExistingTerminal($terminal);

        $this->validateDirectSettlementMapping($terminal);

        $this->validateNonDSRestriction($merchant, $terminal);

        $this->repo->saveOrFail($terminal, ['shouldSync' => $shouldSync]);

        return $terminal;
    }

    public function createWithId($input, $merchant)
    {
        $this->trace->info(
            TraceCode::TERMINAL_CREATE_REQUEST,
            [
                'input'         => $this->removeSecretFieldsForTrace($input),
                'merchant_id'   => $merchant->getId(),
            ]);

        $this->validateAndTokenizeMpansIfPresentInInput($input);

        $input[Entity::MERCHANT_ID] = $merchant->getKey();

        if ((isset($input['id']) === false) or (strlen($input['id']) !== 14))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_TERMINAL_ID);
        }

        $id = $input["id"];

        unset($input["id"]);

        $this->validateBuyPricing($input);

        $terminal = (new Entity)->build($input);

        $terminal->merchant()->associate($merchant);

        if ($terminal->org()->doesntExist() === true)  // if modifier has not associated any org with this terminal
        {                                               // associate the merchant's org
            $terminal->org()->associate($merchant->org);
        }

        if ($terminal->getGateway() != "upi_icici") {
            $this->validateExistingTerminal($terminal);
        }

        $this->validateDirectSettlementMapping($terminal);

        $terminal->setId($id);

        $this->repo->saveOrFail($terminal);

        return $terminal;
    }

    protected function validateDirectSettlementMapping($terminal)
    {
        if ($terminal->isDirectSettlement() === false)
        {
            return;
        }

        $gateway = $terminal->getGateway();

        if (array_key_exists($gateway, Payment\Gateway::DIRECT_SETTLEMENT_GATEWAYS) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TERMINAL_NO_GATEWAY_MAPPING_FOR_DIRECTSETTLEMENT);
        }
    }

    protected function validateNonDSRestriction($merchant, $terminal)
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

        if (in_array($terminal->getGateway(), Payment\Gateway::TOKENISATION_GATEWAYS) === true)
        {
            return;
        }

        if($merchant->isFeatureEnabled(Constants::ONLY_DS) === true)
        {
            $type = $terminal->getType();

            $check = array_intersect($type, [Type::DIRECT_SETTLEMENT_WITHOUT_REFUND,
                Type::DIRECT_SETTLEMENT_WITH_REFUND]);

            if(count($check) === 0)
            {
                throw new Exception\BadRequestValidationFailureException('Invalid Terminal Configuration');
            }
        }
    }

    protected function validateBuyPricing(& $input)
    {
        if (isset($input[Entity::PLAN_ID]))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PLAN_ID_IS_NOT_REQUIRED);
        }

        $planName = $input[Entity::PLAN_NAME] ?? null;
        unset($input[Entity::PLAN_NAME]);

        if (isset($planName) === true)
        {
            $plan = $this->repo->pricing
                         ->onlyBuyPricing()
                         ->getPlanByName($planName);

            if ($plan->count() === 0)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_BUY_PRICING_PLAN_WITH_NAME_DOES_NOT_EXIST);
            }

            // Validating plan before assigning to terminal.
            (new Pricing\Validator())->validBuyPricingRules($plan->toArray());

            $input[Entity::PLAN_ID] = $plan->getId();
        }
    }

    public function removeMerchantFromTerminal(Entity $terminal, string $merchantId)
    {
        $this->trace->info(
            TraceCode::TERMINAL_REMOVE_FROM_MERCHANT,
            [
                'terminal_id' => $terminal->getId(),
                'merchant_id' => $merchantId,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->app['workflow']
            ->setEntityAndId($terminal->getEntity(), $terminal->getId())
            ->handle(["merchant_id" => $merchantId], []);

        $this->repo->terminal->removeMerchantFromTerminal($terminal, $merchant);

        return $terminal;
    }

    public function addMerchantToTerminal(Entity $terminal, string $merchantId)
    {
        $subMerchants = $terminal->merchants();

        $subMerchantsIds = $subMerchants->pluck(Merchant\Entity::ID)->all();

        if (in_array($merchantId, $subMerchantsIds, true) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUB_MERCHANT_ALREADY_ASSIGNED_TO_TERMINAL);
        }

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->validateNonDSRestriction($merchant, $terminal);

        $this->repo->terminal->addMerchantToTerminal($terminal, $merchant);

        $this->trace->info(
            TraceCode::TERMINAL_ADD_MERCHANT,
            [
                'terminal_id'      => $terminal->getId(),
                'merchant_id'      => $merchantId,
                'merchant_id_list' => $subMerchantsIds,
            ]);

        return $terminal;
    }

    public function reassignMerchantForTerminal(Entity $terminal, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::TERMINAL_REASSIGN_MERCHANT,
            [
                'terminal_id'           => $terminal->getId(),
                'current_merchant_id'   => $terminal->getMerchantId(),
                'merchant_id'           => $merchant->getId(),
            ]);

        if (($terminal->isShared() === true) and
            ($terminal->isEnabled() === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SHARED_TERMINAL_MERCHANT_CANNOT_BE_CHANGED);
        }

        if($terminal->isDirectSettlement() === true)
        {
            $this->validateDsActivated($terminal);
        }

        $this->validateNonDSRestriction($merchant,$terminal);

       $this->app['workflow']
            ->setEntityAndId($terminal->getEntity(), $terminal->getId())
            ->handle([Entity::MERCHANT_ID => $terminal->getMerchantId()],[Entity::MERCHANT_ID => $merchant->getId()]);

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($terminal->getId(), "TERMINAL_REASSIGN_MERCHANT_PROXY", $mode);

        $shouldSync = true;

        $tsTerminal = $terminal;

        if ($variantFlag === "reassign_merchant")
        {
            $shouldSync = false;

            $terminalId = $terminal->getId();

            $merchantId = $merchant->getId();

            $input = ["merchant_id" => $merchantId];

            $path = "/terminal/" . $terminalId . "/reassign_merchant";

            $res = $this->app['terminals_service']->proxyTerminalService($input, "PATCH", $path);

            $tsTerminal = Terminal\Service::getEntityFromTerminalServiceResponse($res);

            $terminal->setSyncStatus(SyncStatus::SYNC_SUCCESS);
        }

        $terminal->merchant()->associate($merchant);

        $this->repo->saveOrFail($terminal, ['shouldSync' => $shouldSync]);

        if ($variantFlag === "reassign_merchant")
        {
            return $tsTerminal;
        }

        return $terminal;
    }

    // not getting used anywhere
    public function copy($input, $terminal)
    {
        $this->trace->info(
            TraceCode::TERMINAL_COPY,
            [
                'input'         => $this->removeSecretFieldsForTrace($input),
                'terminal_id'   => $terminal->getId(),
            ]);

        if ($terminal->isShared() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SHARED_TERMINAL_CANNOT_BE_COPIED);
        }

        $merchantIds = $input['merchant_ids'];

        $response = [];

        unset($terminal['used_count']);

        foreach ($merchantIds as $merchantId)
        {
            $newTerminal = $terminal->replicate();
            $newTerminal['merchant_id'] = $merchantId;

            $this->repo->saveOrFail($newTerminal);

            $response[] = [
                'terminal' => $newTerminal->getId(),
                'merchant' => $merchantId
            ];
        }

        return $response;
    }

    protected function validateDsActivated($terminal)
    {
        if($terminal->merchant->isFeatureEnabled(Constants::ONLY_DS) === true)
        {
            $count = (new Service())->countAllTerminalsOfMerchantAndCheckForTypeArray($terminal->merchant->getId());

            if($count['ds_terminals'] === 1)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FEATURE_NOT_ALLOWED_FOR_MERCHANT);
            }
        }
    }

    protected function validateDsTerminalEdit($oldTerminal, $newTerminal)
    {
        //Activated to some else state
        if (($oldTerminal->getStatus() === Status::ACTIVATED) and ($newTerminal->getStatus() !== Status::ACTIVATED))
        {
            $this->validateDsActivated($newTerminal);
        }

    }

    public function edit(Entity $terminal, array $input)
    {
        $this->validateAndTokenizeMpansIfPresentInInput($input);

        if ((isset($input['restore'])) and
            ($input['restore'] === '1'))
        {
            $this->validateExistingTerminal($terminal);

            $terminal->restoreOrFail();
        }
        else
        {
            $this->trace->info(
                TraceCode::TERMINAL_EDIT,
                [
                    'terminal_id' => $terminal->getId(),
                    'input'       => $this->removeSecretFieldsForTrace($input),
                ]);

            $syncInstruments = false;
            if( isset($input[TerminalConstants::SYNC_INSTRUMENTS]) )
            {
                $syncInstruments = $input[TerminalConstants::SYNC_INSTRUMENTS];
                unset($input[TerminalConstants::SYNC_INSTRUMENTS]);
            }

            $this->validateBuyPricing($input);

            $oldTerminal = $terminal->replicate();

            $terminal->edit($input);

            // we want to skip the validation for tokenizing mpans, this code can be removed after all the terminal mpans are tokenized by cron
            if (((count($input) !== 3)
                or (isset($input[Entity::MC_MPAN]) === false)
                or (isset($input[Entity::VISA_MPAN]) === false)
                or (isset($input[Entity::RUPAY_MPAN]) === false))
                and ((count($input)) !== 1
                or (isset($input[Entity::PLAN_ID]) === false)))
            {
                $this->validateExistingTerminal($terminal);
            }

            $this->validateDirectSettlementMapping($terminal);

            $shouldSync = true;

            $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

            $mId = $terminal->getMerchantId();

            $this->validateNonDSRestriction($terminal->merchant, $terminal);

            $this->validateDsTerminalEdit($oldTerminal, $terminal);

            if($oldTerminal->isEnabled() === true && $oldTerminal->getStatus() === Status::ACTIVATED) {
                $this->app['workflow']
                    ->setEntityAndId($terminal->getEntity(), $terminal->getId())
                    ->handle(["terminal_edit"=> []], ["terminal_edit" => $this->redactSecretsOnWorkflow($input)]);
            }

            if(isset($input[TerminalConstants::TERMINAL_EDIT_GOD_MODE]) &&
                ($input[TerminalConstants::TERMINAL_EDIT_GOD_MODE] === true || $input[TerminalConstants::TERMINAL_EDIT_GOD_MODE] == '1') )
            {
                if($this->getSyncInstrumentsFlagFromWorkflow($terminal,Permission::EDIT_TERMINAL_GOD_MODE))
                {
                    $syncInstruments = true;
                }

            }
            else if ($this->getSyncInstrumentsFlagFromWorkflow($terminal,Permission::EDIT_TERMINAL))
            {
                $syncInstruments = true;
            }


            $variantFlag = $this->app->razorx->getTreatment($mId, "TERMINAL_EDIT_PROXY", $mode);

            // if the $variantFlag is on, it will first edit on terminal service and then on api with shouldSync on creation as false,
            // otherwise shouldSync will be true and syncing will happen at the time of creation itself.
            if ($variantFlag === "on" || in_array($terminal->getGateway(), Gateway::TOKENISATION_GATEWAYS))
            {
                $shouldSync = false;

                $terminalId = $terminal->getId();

                $path = "v1/terminals/" .  $terminalId;

                $response = $this->app['terminals_service']->proxyTerminalService($input, "PATCH", $path);

                $tsTerminal = Terminal\Service::getEntityFromTerminalServiceResponse($response);

                //Tokenisation type terminals are created on the Termial service not on the API service, so skipping this check
                if(!in_array($tsTerminal->getGateway(), Gateway::TOKENISATION_GATEWAYS)){
                    $terminal->setSyncStatus(SyncStatus::SYNC_SUCCESS);

                    $this->repo->saveOrFail($terminal, ['shouldSync' => $shouldSync, TerminalConstants::SYNC_INSTRUMENTS => $syncInstruments]);

                    // compare terminal data on both service
                    if (Terminal\Service::compareTerminalEntity($terminal, $tsTerminal) === false)
                    {
                        $data = ['terminal_id' => $terminalId];

                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_EDIT_MISMATCH, $data);
                    }
                }

                return $tsTerminal;
            }
            else
            {
                $this->repo->saveOrFail($terminal, ['shouldSync' => $shouldSync, TerminalConstants::SYNC_INSTRUMENTS => $syncInstruments]);
            }
        }

        return $terminal;
    }

    public function getSyncInstrumentsFlagFromWorkflow($terminal, $permission)
    {
        if ($this->app['api.route']->isWorkflowExecuteOrApproveCall() === true)
        {
            $tags = (new Action\Core())->getCurrentWorkflowTags($terminal->getId(), $terminal->getEntity(), $permission);

            if( in_array(TerminalConstants::SYNC_INSTRUMENTS_WORKFLOWS_TAG, $tags->toArray(), true) )
            {
                return true;
            }

        }

        return false;
    }

    public function toggle($terminal, $toggle, array $options = array())
    {
        $isEnabled = $terminal->isEnabled();

        $terminalStatusTrace = ($toggle) ? TraceCode::TERMINAL_ENABLE : TraceCode::TERMINAL_DISABLE;

        if($toggle === false)
        {
            $this->validateDsActivated($terminal);
        }

        $this->trace->info(
            $terminalStatusTrace,
            [
                'terminal_id' => $terminal->getId(),
                'isEnabled'   => $isEnabled
            ]);

        $terminal->setEnabled($toggle);

        $shouldSync = true;

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $mId = $terminal->getMerchantId();

        $variantFlag = $this->app->razorx->getTreatment($mId, "TERMINAL_EDIT_PROXY", $mode);

        if ($variantFlag === "on")
        {
            $shouldSync = false;

            $path = "v1/terminals/" .  $terminal->getId();

            $input = [
                Entity::ENABLED => $toggle,
                Entity::STATUS => $terminal->getStatus(),
            ];

            $response = $this->app['terminals_service']->proxyTerminalService($input, "PATCH", $path);

            $tsTerminal = Terminal\Service::getEntityFromTerminalServiceResponse($response);

            $terminal->setSyncStatus(SyncStatus::SYNC_SUCCESS);

            $this->repo->saveOrFail($terminal, ['shouldSync' => $shouldSync]);

            return $tsTerminal;
        }
        else
        {
            $options['shouldSync'] = $shouldSync;

            $this->repo->saveOrFail($terminal, $options);
        }

        return $terminal;
    }

    public function disableTerminal(Entity $terminal)
    {
        if (in_array($terminal->getStatus(), [Status::PENDING, Status::ACTIVATED]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ONLY_PENDING_OR_ACTIVATED_TERMINALS_CAN_BE_DISABLED);
        }

        (new GatewayTerminalService)->callGatewayForTerminalEnableOrDisable($terminal, 'disable_terminal');

        $terminal->setStatus(Status::DEACTIVATED);

        $terminal = $this->toggle($terminal, false);

        return $terminal;
    }

    public function enableActivatedOrDeactivatedTerminal(Entity $terminal)
    {
        if(in_array($terminal->getStatus(), [Status::DEACTIVATED, Status::ACTIVATED])) {

            $terminal->setStatus(Status::ACTIVATED);

            $terminal = $this->toggle($terminal, true);
        } else {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_ACTIVATED_OR_DEACTIVATED_TERMINAL_CAN_BE_BULK_ENABLED);
        }

        return $terminal;
    }

    public function validateExistingTerminal($terminal)
    {
        // If procurer is merchant, them merchant can procure terminals having same attributes. E.g. merchant can have two same paytm terminals
        if ($terminal->getProcurer() === Procurer::MERCHANT)
        {
            return;
        }

        $params = [Entity::MERCHANT_ID => $terminal->getMerchantId()];

        $existingTerminals = $this->repo->terminal->getNonFailedNonDeactivatedByParams($params, false);

        $gateway = $terminal->getGateway();

        //
        // Checks that existing terminals don't
        // have same gateway field as the new one
        //
        $terminal->getValidator()->validateExistingTerminalsCount($existingTerminals);

        $this->validateExistingTerminalGatewayMerchantId($terminal, $gateway);

        $this->validateExistingMpan($terminal);
    }

    public function createTerminalsInTestMode($merchant)
    {
        $this->createRandomTerminalInTestMode($merchant, 'hdfc');

        $this->createRandomTerminalInTestMode($merchant, 'atom');
    }

    public function createRandomTerminalInTestMode($merchant, $gateway)
    {
        $input = [
            'merchant_id' => $merchant->getId(),
            'gateway'     => $gateway,
            'card'        => '1',
            'gateway_merchant_id' => str_random(),
            'gateway_terminal_id' => str_random(),
            'gateway_terminal_password' => str_random()
        ];

        $input['merchant_id'] = $merchant->getKey();

        $terminal = (new Entity)->build($input);

        $this->validateExistingTerminal($terminal);

        $terminal->setConnection(Mode::TEST);

        $this->repo->saveOrFail($terminal);

        return $terminal;
    }

    /**
     * This function is primarily used for terminal selection (filters and sorters)
     *
     * @param Entity                $terminal
     * @param Payment\Entity        $payment
     *
     * @param Base\PublicCollection $gatewayTokens
     *
     * @return bool
     * @throws Exception\LogicException
     */
    public function hasApplicableGatewayTokens(
        Entity $terminal,
        Payment\Entity $payment,
        Base\PublicCollection $gatewayTokens)
    {
        //
        // This function should be called only for second recurring payments!
        //
        if ($payment->isSecondRecurring() === false)
        {
            throw new Exception\LogicException(
                'Invalid function call!',
                ErrorCode::SERVER_ERROR_INVALID_FUNCTION_CALL,
                [
                    'payment_id'    => $payment->getId(),
                    'terminal_id'   => $terminal->getId(),
                ]);
        }

        //
        // For second recurring payment, ensure that we select a terminal
        // of the same gateway as for the first recurring payment and also
        // of the same merchant (shared, direct)
        //
        $validGatewayTokens = $gatewayTokens->filter(
                                function($gatewayToken) use ($terminal)
                                {
                                    return (($gatewayToken->getGateway() === $terminal->getGateway()) and
                                            ($gatewayToken->terminal->getMerchantId() === $terminal->getMerchantId()));
                                });

        $validGatewayTokensCount = $validGatewayTokens->count();

        //
        // We check if we have one valid gateway_token for the
        // terminal being selected. If yes, we return back true.
        // If we don't have even one valid gateway_token for the
        // terminal being selected, we return back false.
        //
        // The check is again 1 exactly because for a given gateway,
        // there should not be more than one terminal. We don't support
        // more than 1 set of terminals for a merchant (direct/shared).
        // If it's greater than 1, there's something wrong and should fail.
        //
        if ($validGatewayTokensCount === 1)
        {
            return true;
        }
        else
        {
            if ($validGatewayTokensCount > 0)
            {
                $this->trace->warning(
                    TraceCode::GATEWAY_TOKEN_TOO_MANY_PRESENT,
                    [
                        'count'             => $validGatewayTokensCount,
                        'gateway_tokens'    => $validGatewayTokens->toArray(),
                        'terminal_id'       => $terminal->getId(),
                        'payment_id'        => $payment->getId(),
                    ]);
            }

            return false;
        }
    }

    public function getBanksForTerminal(Entity $terminal): array
    {
        $gateway = $terminal->getGateway();

        if ((($terminal->isNetbankingEnabled() === false) and ($terminal->isPayLaterEnabled() === false) and ($terminal->isCardlessEmiEnabled() === false)) or
            (((in_array($gateway, Gateway::$methodMap[Method::NETBANKING], true) === false)) and
            (Payment\Processor\PayLater::isMultilenderProvider($terminal->getGatewayAcquirer()) === false) and
            (Payment\Processor\CardlessEmi::isMultilenderProvider($terminal->getGatewayAcquirer()) === false)))
        {
            throw new Exception\BadRequestValidationFailureException('Banks available only for netbanking gateways and some paylater/cardless_emi providers');
        }

        $enabledBanksList = (array) $terminal->getEnabledBanks();

        if ($terminal->isNetbankingEnabled())
        {
            $corporate = $terminal->getCorporate();
            $tpv       = $terminal->getTpv();

            $supportedBanks = Netbanking::getSupportedBanksForGateway($gateway, $corporate, $tpv);
        }

        if ($terminal->isPayLaterEnabled())
        {
            $supportedBanks = Payment\Processor\PayLater::getSupportedBanksForMultilenderProvider($terminal->getGatewayAcquirer());
        }

        if ($terminal->isCardlessEmiEnabled())
        {
            $supportedBanks = Payment\Processor\CardlessEmi::getSupportedBanksForMultilenderProvider($terminal->getGatewayAcquirer());
        }

        $disabledBanksList = array_values(array_diff($supportedBanks, $enabledBanksList));

        $enabledBanks  = Netbanking::getNames($enabledBanksList);
        $disabledBanks = Netbanking::getNames($disabledBanksList);

        if ($terminal->isCardlessEmiEnabled())
        {
            $enabledBanks  = CardlessEmi::getDisplayName($enabledBanksList);
            $disabledBanks = CardlessEmi::getDisplayName($disabledBanksList);
        }

        return [
            'enabled'  => $enabledBanks,
            'disabled' => $disabledBanks,
        ];
    }

    public function getWalletsForTerminal(Entity $terminal): array
    {
        $gateway = $terminal->getGateway();

        if (in_array($gateway, Payment\Gateway::getAllWalletSupportingGateways(), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Wallet available only for wallet gateways');
        }

        $enabledWalletList = (array) $terminal->getEnabledWallets();

        $supportedWallets = Payment\Gateway::getSupportedWalletsForGateway($gateway);

        $disabledWalletList = array_values(array_diff($supportedWallets, $enabledWalletList));

        $enabledWallets  = $enabledWalletList;
        $disabledWallets = $disabledWalletList;


        return [
            'enabled'  => $enabledWallets,
            'disabled' => $disabledWallets,
        ];
    }

    public function setBanksForTerminal(Entity $terminal, $banksToEnable, $option): array
    {
        $gateway = $terminal->getGateway();

        if ((($terminal->isNetbankingEnabled() === false) and ($terminal->isPayLaterEnabled() === false) and ($terminal->isCardlessEmiEnabled() === false)) or
            (((in_array($gateway, Gateway::$methodMap[Method::NETBANKING], true) === false)) and
            (Payment\Processor\PayLater::isMultilenderProvider($terminal->getGatewayAcquirer()) === false) and
            (Payment\Processor\CardlessEmi::isMultilenderProvider($terminal->getGatewayAcquirer()) === false)))
        {
            throw new Exception\BadRequestValidationFailureException('Banks available only for netbanking gateways and some paylater/cardless_emi providers');
        }

        if (is_array($banksToEnable) === false)
        {
            throw new Exception\BadRequestValidationFailureException('enabled_banks should be an array');
        }

        if ($terminal->isNetbankingEnabled())
        {
            $corporate = $terminal->getCorporate();
            $tpv       = $terminal->getTpv();

            $supportedBanks = Netbanking::getSupportedBanksForGateway($gateway, $corporate, $tpv);
        }

        if ($terminal->isPayLaterEnabled())
        {
            $supportedBanks = Payment\Processor\PayLater::getSupportedBanksForMultilenderProvider($terminal->getGatewayAcquirer());
        }

        if ($terminal->isCardlessEmiEnabled())
        {
            $supportedBanks = Payment\Processor\CardlessEmi::getSupportedBanksForMultilenderProvider($terminal->getGatewayAcquirer());
        }

        if (empty(array_diff($banksToEnable, $supportedBanks)) === false)
        {
            throw new Exception\BadRequestValidationFailureException('banks not supported by gateway');
        }

        $this->app['workflow']
            ->setEntityAndId($terminal->getEntity(), $terminal->getId())
            ->handle([Entity::ENABLED_BANKS => $terminal->getEnabledBanks()], [Entity::ENABLED_BANKS => $banksToEnable]);

        $syncInstruments = false;
        if( (new Terminal\Core)->getSyncInstrumentsFlagFromWorkflow($terminal,Permission::ASSIGN_MERCHANT_BANKS) )
        {
            $syncInstruments = $option[TerminalConstants::SYNC_INSTRUMENTS];
        }

        $terminal->setEnabledBanks($banksToEnable);

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $mId = $terminal->getMerchantId();

        $variantFlag = $this->app->razorx->getTreatment($mId, "TERMINAL_EDIT_PROXY", $mode);

        if ($variantFlag === "on" and $option['bulk_update'] === false)
        {
            $path = "v1/terminals/".$terminal->getId()."/banks";

            $input = [
                Entity::ENABLED_BANKS => $banksToEnable
            ];

            $response = $this->app['terminals_service']->proxyTerminalService($input, "PATCH", $path);

            $terminal->setSyncStatus(SyncStatus::SYNC_SUCCESS);

            $this->repo->saveOrFail($terminal, ['shouldSync' => false]);

            return $response;
        }
        else
        {
            $this->repo->saveOrFail($terminal, ['shouldSync' => $option['sync_with_terminals_service'], TerminalConstants::SYNC_INSTRUMENTS => $syncInstruments]);
        }

        return $this->getBanksForTerminal($terminal);
    }


    public function setWalletsForTerminal(Entity $terminal, $walletsToEnable, $option): array
    {
        $gateway = $terminal->getGateway();

        if (in_array($gateway, Payment\Gateway::getAllWalletSupportingGateways(), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Wallet available only for wallet gateways');
        }

        if (is_array($walletsToEnable) === false)
        {
            throw new Exception\BadRequestValidationFailureException('enabled_wallets should be an array');
        }

        $supportedWallets = Payment\Gateway::getSupportedWalletsForGateway($gateway);

        if (empty(array_diff($walletsToEnable, $supportedWallets)) === false)
        {
            throw new Exception\BadRequestValidationFailureException('wallet not supported by gateway');
        }

        $this->app['workflow']
            ->setEntityAndId($terminal->getEntity(), $terminal->getId())
            ->handle([Entity::ENABLED_WALLETS => $terminal->getEnabledWallets()], [Entity::ENABLED_WALLETS => $walletsToEnable]);

        $terminal->setEnabledWallets($walletsToEnable);

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $mId = $terminal->getMerchantId();

        $variantFlag = $this->app->razorx->getTreatment($mId, "TERMINAL_EDIT_PROXY", $mode);

        if ($variantFlag === "on" and $option['bulk_update'] === false)
        {
            $path = "v1/terminals/".$terminal->getId()."/wallets";

            $input = [
                Entity::ENABLED_BANKS => $walletsToEnable
            ];

            $response = $this->app['terminals_service']->proxyTerminalService($input, "PATCH", $path);

            $terminal->setSyncStatus(SyncStatus::SYNC_SUCCESS);

            $this->repo->saveOrFail($terminal, ['shouldSync' => false]);

            return $response;
        }
        else
        {
            $this->repo->saveOrFail($terminal, ['shouldSync' => $option['sync_with_terminals_service']]);
        }

        return $this->getWalletsForTerminal($terminal);
    }

    public function processMerchantMccUpdate(Merchant\Entity $merchant, array $input)
    {
        $oldCategory = $merchant->getOriginalAttributesAgainstDirty()[Merchant\Entity::CATEGORY] ?? '';

        $newCategory = $input[Merchant\Entity::CATEGORY] ?? '';

        if ($oldCategory === $newCategory)
        {
            return;
        }

        $this->processMerchantMccUpdateForHitachiTerminals($merchant, $oldCategory);
        $this->processMerchantMccUpdateForFulcrumTerminals($merchant, $oldCategory);
    }

    /**
     * Returns the list of networks on which the given merchantId is onboarded for tokenisation
     * Onboarded Network Codes are returned => Example - ['VISA','MC','RUPAY']
     *
     * @param string $merchantId
     * @return array
     */
    public function getMerchantTokenisationOnboardedNetworks(string $merchantId): array
    {
        $onboardedNetworks = $this->getMerchantTokenisationOnboardedNetworksFromRedis($merchantId);

        if (isset($onboardedNetworks) === true)
        {
            return $onboardedNetworks;
        }

        $onboardedNetworks = $this->app['terminals_service']->fetchMerchantTokenisationOnboardedNetworks($merchantId);

        if (isset($onboardedNetworks) === false)
        {
            return [];
        }

        $this->setMerchantTokenisationOnboardedNetworksInRedis($merchantId, $onboardedNetworks);

        return $onboardedNetworks;
    }

    protected function getMerchantTokenisationOnboardedNetworksRedisKey($merchantId): string
    {
        return $merchantId . '_tokenisation_onboarded_networks';
    }

    protected function setMerchantTokenisationOnboardedNetworksInRedis($merchantId, $onboardedNetworks): void
    {
        $redisKey = $this->getMerchantTokenisationOnboardedNetworksRedisKey($merchantId);

        $ttl = 60 * 60; // 1 hour

        $this->app['cache']->put($redisKey, json_encode($onboardedNetworks), $ttl);
    }

    protected function getMerchantTokenisationOnboardedNetworksFromRedis($merchantId): ?array
    {
        try
        {
            $redisKey = $this->getMerchantTokenisationOnboardedNetworksRedisKey($merchantId);

            return json_decode($this->app['cache']->get($redisKey), true);
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::GET_ONBOARDED_NETWORKS_ERROR, [
                'merchantId' => $merchantId,
            ]);

            return null;
        }
    }

    protected function validateExistingTerminalGatewayMerchantId(Entity $terminal, $gateway)
    {
        // Check no record with same 'gateway_merchant_id' exists
        $params = [
            Entity::GATEWAY                 => $terminal->getGateway(),
            Entity::GATEWAY_MERCHANT_ID     => $terminal->getGatewayMerchantId(),
            Entity::GATEWAY_MERCHANT_ID2    => $terminal->getGatewayMerchantId2(),
        ];

        if (in_array($gateway, Gateway::MULTIPLE_TERMINALS_FOR_SAME_GATEWAY_MERCHANT_GATEWAYS) === true)
        {
            $params[Entity::GATEWAY_TERMINAL_ID] = $terminal->getGatewayTerminalId();
        }

        $this->checkIfExists($params, $terminal);
    }

    protected function validateExistingMpan(Entity $terminal)
    {
        $bharatQrNetworks = Payment\Gateway::getBharatQrCardNetworks();

        foreach ($bharatQrNetworks as $bharatQrNetwork)
        {
            $mpanAttr = strtolower($bharatQrNetwork) . '_mpan';

            if (empty($terminal[$mpanAttr]) === false)
            {
                $params =  [$mpanAttr => $terminal[$mpanAttr]];

                $this->checkIfExists($params, $terminal, $mpanAttr);
            }
        }

        // omnichannel terminals will have duplicate vpa, as existing upi terminal with vpa are whitelisted on provider(google)
        if ((empty($terminal->getVpa()) === false) and
            ($terminal->isOmnichannelEnabled() === false))
        {
            $params =  [Entity::VPA => $terminal->getVpa()];

            $this->checkIfExists($params, $terminal, Entity::VPA);
        }
    }

    protected function checkIfExists($params, Entity $terminal, string $field = null)
    {
        $existingTerminals = $this->repo->terminal->getNonFailedNonDeactivatedByParams($params, false);

        // This check if this terminal is same as what
        // we are trying to edit
        if ($existingTerminals->count() === 1)
        {
            $existingTerminal = $existingTerminals[0];

            if ($existingTerminal->getId() === $terminal->getId())
            {
                return;
            }
        }

        //
        // This condition in need in two cases.
        // Add terminal and edit terminal.
        //
        // In case we are adding terminal assume we
        // are trying to add master card mpan. If already
        // terminal exists with the same mpan it will go to
        // first condition where count is 1. Since id of new terminal
        // is not generated yet it will be null. So the function
        // won't return from equal id condition. And It will
        // reach here. If the count is not equal to 0 it
        // will throw exception.
        //
        // In case we are editing terminal, and we are trying to
        // set the master card mpan to something for which terminal
        // already exists the count will be again 1 when we fetch from
        // repository. Now the id of terminal which we fetched and id
        // of terminal which we are trying to edit will be different.
        // so again it wouldn't return from the condition and will
        // reach here and it will throw exception.
        //
        // In case we are trying to edit the lets say visa mpan.
        // Now when we are checking for master card mpan repo will
        // return same terminal which we are trying to edit. so it will
        // return from id equality check.
        //
        if ($existingTerminals->count() !== 0)
        {
            $description = PublicErrorDescription::BAD_REQUEST_TERMINAL_WITH_SAME_FIELD_ALREADY_EXISTS . $existingTerminals->pluck(Entity::ID)->first();

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TERMINAL_WITH_SAME_FIELD_ALREADY_EXISTS,
                $field,
                null,
                $description
            );
        }
    }

    protected function removeSecretFieldsForTrace(array $input)
    {
        $terminalHiddenFields = (new Entity)->getHidden();

        foreach ($terminalHiddenFields as $hidden)
        {
            unset($input[$hidden]);
        }

        foreach ([Entity::MC_MPAN, Entity::VISA_MPAN, Entity::RUPAY_MPAN] as $networkMpan)
        {
            if (isset($input[$networkMpan]) === true)
            {
                $input[$networkMpan] = (new MpanEntity)->getMaskedMpan($input[$networkMpan]);
            }
        }

        return $input;
    }

    public static function getBankAccountSeriesPrefixForX(Merchant\Entity $merchant, string $mode): string
    {
        if ($mode === Mode::TEST)
        {
            return self::TEST_MODE_ACCOUNT_NUMBER_SERIES_PREFIX;
        }

        $merchantId = $merchant->getId();

        $config = (new Admin\Service)->getConfigKey(
            ['key' => Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX]);

        if (empty($config) === true)
        {
            // In case of redis failure, usr ICICI Pool VA series prefix
            return '456456';
        }

        if (array_key_exists($merchantId, $config) === true)
        {
            return $config[strval($merchantId)];
        }

        // If merchant's config is not set, use shared merchant's config
        return $config[Merchant\Account::SHARED_ACCOUNT];
    }

    protected function processMerchantMccUpdateForTerminals($merchant, $oldCategory, $gateway, $traceCode) {
        $fetchParams = [
            Entity::GATEWAY  => $gateway,
            Entity::CATEGORY => $oldCategory,
            Entity::ENABLED  => '1',
            Entity::STATUS   => Status::ACTIVATED,
        ];

        if (strlen($oldCategory) === 4)
        {
            $directTerminalsToBeDisabled = $this->repo->terminal->fetch($fetchParams, $merchant->getId());

            $this->repo->transaction(function() use (& $directTerminalsToBeDisabled, $traceCode) {
                foreach ($directTerminalsToBeDisabled as $directTerminal)
                {
                    $directTerminal->setStatus(Status::DEACTIVATED);

                    $directTerminal->setEnabled(false);

                    $this->app['trace']->info($traceCode, [
                        Entity::ID         => $directTerminal->getId(),
                        Entity::STATUS     => Status::DEACTIVATED,
                        Entity::ENABLED    => false,
                    ]);

                    $this->repo->terminal->saveOrFail($directTerminal);
                }
            });

        }
    }

    protected function processMerchantMccUpdateForFulcrumTerminals($merchant, $oldCategory)
    {
        $this->processMerchantMccUpdateForTerminals($merchant, $oldCategory, Payment\Gateway::FULCRUM, TraceCode::FULCRUM_TERMINAL_EDIT_ON_MCC_EDIT);
    }

    protected function processMerchantMccUpdateForHitachiTerminals($merchant, $oldCategory)
    {
        $this->processMerchantMccUpdateForTerminals($merchant, $oldCategory, Payment\Gateway::HITACHI, TraceCode::HITACHI_TERMINAL_EDIT_ON_MCC_EDIT);
    }

    protected function validateAndTokenizeMpansIfPresentInInput(array &$input)
    {
        // copying mpans into below array, so that they can be passed into validateInput separate from other input params
        $mpanInput = [];

        foreach([Entity::MC_MPAN, Entity::VISA_MPAN, Entity::RUPAY_MPAN] as $network)
        {
            if (isset($input[$network]) === true)
            {
                $mpanInput[$network] = $input[$network];
            }
        }

        (new Validator())->validateInput('mpans_before_tokenization', $mpanInput);

        // tokenization could have be done in the above loop only, but we want to avoid the call to cardvault if the validations were to fail
        foreach([Entity::MC_MPAN, Entity::VISA_MPAN, Entity::RUPAY_MPAN] as $network)
        {
            if (empty($input[$network]) === false)
            {
                $tokenizedMpan = $this->app['mpan.cardVault']->tokenize(['secret' => $input[$network]]);

                $input[$network] = $tokenizedMpan;
            }
        }
    }
    protected function validateGatewayAllowed(array $input)
    {
        $gatewayInput = [
            'gateway' => $input['gateway']
        ];

        (new Validator())->validateInput('gateway_input', $gatewayInput);
    }

    protected function validateAcquirerByCountry($merchant, array $input)
    {
        if(!isset($input['gateway_acquirer']))
        {
            return;
        }

        if (in_array($input['gateway_acquirer'], Gateway::GATEWAY_ACQUIRER_COUNTRY_MAP[strtolower($merchant->getCountry())]) === false)
        {
            if ($merchant->getCountry() === "IN")
            {
                $this->trace->info(TraceCode::ACQUIRER_NOT_PRESENT_IN_COUNTRY_MAP, ["acquirer" => $input['gateway_acquirer']]);

                return ;
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ACQUIRER_FOR_COUNTRY
            );
        }
    }

    protected function redactSecretsOnWorkflow(array $redactedInput) {
        $terminalHiddenFields = (new Entity)->getHidden();

        $fieldsRedacted = false;

        foreach ($terminalHiddenFields as $hidden)
        {
            if(isset($redactedInput[$hidden]) === true) {
                unset($redactedInput[$hidden]);
                $fieldsRedacted = true;
            }
        }

        if($fieldsRedacted) {
            $redactedInput["secrets"] = "Secrets are changed, Please check with maker";
        }

        return $redactedInput;
    }
}
