<?php

namespace RZP\Models\FundAccount\DualWrite;

use App;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\FundAccount\Entity;
use function DeepCopy\deep_copy;

class FundAccount extends Base
{
    protected $columnsToUnset = [];

    public function dualWriteRxFundAccount($input)
    {
        $this->dualWriteAccount($input);

        $fundAccount = $this->getAPIFundAccountFromInput($input);

        /** @var Entity $apiFundAccount */
        $apiFundAccount = $this->repo->fund_account->find($fundAccount->getId());

        if (empty($apiFundAccount) === false)
        {
            $fundAccount = $apiFundAccount->setRawAttributes($fundAccount->getAttributes());
        }

        $fundAccount->setIgnoreRelationsForRxDualWrite();

        $this->repo->fund_account->saveOrFail($fundAccount);

        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $this->trace->info(
            TraceCode::RX_FUND_ACCOUNT_DUAL_WRITE_COMPLETE,
            [
                'fund_account_id' => $input[Entity::ID],
                'timestamp' => $timestamp
            ]
        );

        return $fundAccount;
    }

    public function dualWriteAccount(&$input)
    {
        if (isset($input[Entity::BANK_ACCOUNT]))
        {
            $this->saveAPIBankAccountFromInput($input);
        }
        else if (isset($input[Entity::VPA]))
        {
            $this->saveAPIVPAFromInput($input);
        }
        else if (isset($input[Entity::WALLET_ACCOUNT]))
        {
            $this->saveAPIWalletAccountFromInput($input);
        }
        else if (isset($input[Entity::CARD]))
        {
            $this->saveAPICardFromInput($input);
        }
        else
        {
            $this->trace->info(
                TraceCode::RX_FUND_ACCOUNT_DUAL_WRITE_INVALID_ACCOUNT_TYPE,
                [
                    'fund_account_id' => $input[Entity::ID],
                    'account_type' => $input[Entity::ACCOUNT_TYPE]
                ]
            );
        }
    }

    public function getAPIFundAccountFromInput($input, bool $sync = false)
    {
        // converts the stdClass object into associative array.
        $this->attributes = $input;

        $this->processModifications();

        $fundAccount = new Entity;

        $fundAccount->setRawAttributes($this->attributes, $sync);

        // Explicitly setting the connection.
        $fundAccount->setConnection($this->mode);

        // This will ensure that updated_at columns are not overridden by saveOrFail.
        $fundAccount->timestamps = false;

        return $fundAccount;
    }

    public function saveAPIBankAccountFromInput(&$input, bool $sync = false)
    {
        // converts the stdClass object into associative array.
        $this->attributes = $input[Entity::BANK_ACCOUNT];

        $this->processModifications();

        $bankAccount = new \RZP\Models\BankAccount\Entity;

        $bankAccount->setRawAttributes($this->attributes, $sync);

        // Explicitly setting the connection.
        $bankAccount->setConnection($this->mode);

        // This will ensure that updated_at columns are not overridden by saveOrFail.
        $bankAccount->timestamps = false;

        $this->repo->bank_account->saveOrFail($bankAccount);

        unset($input[Entity::BANK_ACCOUNT]);
    }

    public function saveAPIVPAFromInput(&$input, bool $sync = false)
    {
        // converts the stdClass object into associative array.
        $this->attributes = $input[Entity::VPA];

        $this->processModifications();

        $vpa = new \RZP\Models\Vpa\Entity;

        $vpa->setRawAttributes($this->attributes, $sync);

        // Explicitly setting the connection.
        $vpa->setConnection($this->mode);

        // This will ensure that updated_at columns are not overridden by saveOrFail.
        $vpa->timestamps = false;

        $this->repo->vpa->saveOrFail($vpa);

        unset($input[Entity::VPA]);
    }

    public function saveAPIWalletAccountFromInput(&$input, bool $sync = false)
    {
        // converts the stdClass object into associative array.
        $this->attributes = $input[Entity::WALLET_ACCOUNT];

        $this->processModifications();

        $walletAccount = new \RZP\Models\WalletAccount\Entity;

        $walletAccount->setRawAttributes($this->attributes, $sync);

        // Explicitly setting the connection.
        $walletAccount->setConnection($this->mode);

        // This will ensure that updated_at columns are not overridden by saveOrFail.
        $walletAccount->timestamps = false;

        $this->repo->wallet_account->saveOrFail($walletAccount);

        unset($input[Entity::WALLET_ACCOUNT]);
    }

    public function saveAPICardFromInput(&$input, bool $sync = false)
    {
        // converts the stdClass object into associative array.
        $this->attributes = $input[Entity::CARD];

        $this->processModifications();

        $card = new \RZP\Models\Card\Entity;

        $card->setRawAttributes($this->attributes, $sync);

        // Explicitly setting the connection.
        $card->setConnection($this->mode);

        // This will ensure that updated_at columns are not overridden by saveOrFail.
        $card->timestamps = false;

        $this->repo->card->saveOrFail($card);

        unset($input[Entity::CARD]);
    }
}
