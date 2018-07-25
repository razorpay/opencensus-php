<?php

namespace RZP\Models\Terminal;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function create($input, $merchant)
    {
        $this->trace->info(
            TraceCode::TERMINAL_CREATE_REQUEST,
            [
                'input'         => $this->removeSecretFieldsForTrace($input),
                'merchant_id'   => $merchant->getId(),
            ]);

        $input['merchant_id'] = $merchant->getKey();

        $terminal = (new Entity)->build($input);

        $terminal->merchant()->associate($merchant);

        $this->validateExistingTerminal($terminal);

        $this->repo->saveOrFail($terminal);

        return $terminal;
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

        $terminal->merchant()->associate($merchant);

        $this->repo->saveOrFail($terminal);

        return $terminal;
    }

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

    public function edit($terminal, $input)
    {
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
                    'input' => $this->removeSecretFieldsForTrace($input),
                ]);

            $terminal->edit($input);

            $this->validateExistingTerminal($terminal);

            $this->repo->saveOrFail($terminal);
        }

        return $terminal;
    }

    public function toggle($terminal, $toggle)
    {
        $isEnabled = $terminal->isEnabled();

        $terminalStatusTrace = ($toggle) ? TraceCode::TERMINAL_ENABLE : TraceCode::TERMINAL_DISABLE;

        $this->trace->info(
            $terminalStatusTrace,
            ['terminal_id' => $terminal->getId(), 'isEnabled' => $isEnabled]);

        $terminal->setEnabled($toggle);

        $this->repo->saveOrFail($terminal);

        return $terminal;
    }

    public function validateExistingTerminal($terminal)
    {
        $params = [Entity::MERCHANT_ID => $terminal->getMerchantId()];

        $existingTerminals = $this->repo->terminal->fetch($params);

        //
        // Checks that existing terminals don't
        // have same gateway field as the new one
        //
        $terminal->getValidator()->validateExistingTerminalsCount($existingTerminals);

        $this->validateExistingTerminalGatewayMerchantId($terminal);

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

    protected function validateExistingTerminalGatewayMerchantId($terminal)
    {
        // Check no record with same 'gateway_merchant_id' exists
        $params = [Entity::GATEWAY_MERCHANT_ID => $terminal->getGatewayMerchantId()];

        $existingTerminals = $this->repo->terminal->fetch($params);

        // This check if this terminal is same as what
        // we are trying to edit
        if ($existingTerminals->count() === 1)
        {
            $existingTerminal = $existingTerminals[0];

            if ($existingTerminal->getGatewayMerchantId() === $terminal->getGatewayMerchantId())
            {
                return;
            }
        }

        if ($existingTerminals->count() !== 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FIELD_ALREADY_EXISTS,
                Entity::GATEWAY_MERCHANT_ID);
        }
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

        if (empty($terminal->getVpa()) === false)
        {
            $params =  [Entity::VPA => $terminal->getVpa()];

            $this->checkIfExists($params, $terminal, Entity::VPA);
        }
    }

    protected function checkIfExists($params, Entity $terminal, string $field)
    {
        $existingTerminals = $this->repo->terminal->fetch($params);

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
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FIELD_ALREADY_EXISTS,
                $field);
        }
    }

    protected function removeSecretFieldsForTrace(array $input)
    {
        $terminalHiddenFields = (new Entity)->getHidden();

        foreach ($terminalHiddenFields as $hidden)
        {
            unset($input[$hidden]);
        }

        return $input;
    }
}
