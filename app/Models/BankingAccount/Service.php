<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input): array
    {
        (new Validator)->setStrictFalse()->validateInput('pre_create', $input);

        $channel = $input[Entity::CHANNEL];

        switch ($channel)
        {
            case Channel::RBL:
                $account = $this->core->createRblBankingAccount($input, $this->merchant);

                break;

            default:
                throw new LogicException(
                    'Banking Account logic undefined for channel: ' . $channel,
                    null,
                    $input);
        }

        return $account->toArrayPublic();
    }

    public function update(string $id, array $input)
    {
        $bankingAccount = $this->repo->banking_account->findOrFailPublic($id);

        $channel = $bankingAccount->getChannel();

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_EDIT,
            [
                'channel'           => $channel,
                'edit_input'        => $input,
                'banking_account'   => $bankingAccount->toArray(),
            ]);

        switch ($channel)
        {
            case Channel::RBL:
                $account = $this->core->updateRblBankingAccount($bankingAccount, $input);

                return $account;

            default:
                throw new LogicException(
                    'Banking Account logic undefined for channel: ' . $channel,
                    null,
                    $input);
        }
    }
}
