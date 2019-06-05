<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Base;
use RZP\Models\BankAccount;
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

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_CREATE,
            [
                'channel'            => $channel,
                'input'              => $input,
            ]);

        switch ($channel)
        {
            case Channel::RBL:
                $account = $this->core->createRblBankingAccount($input, $this->merchant);

                break;

            default:
                $this->throwUnhandledChannelException($channel, $input);

                return null;
        }

        return $account->toArrayPublic();
    }

    public function update(string $id, array $input): array
    {
        /** @var Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicIdAndMerchant($id, $this->merchant);

        $channel = $bankingAccount->getChannel();

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_EDIT,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $channel,
                'input'   => $input,
            ]);

        switch ($channel)
        {
            case Channel::RBL:
                $account = $this->core->updateRblBankingAccount($bankingAccount, $input);

                break;

            default:
                $this->throwUnhandledChannelException($channel, $input);

                return null;
        }

        return $account->toArrayPublic();
    }

    public function fetchBankingDetailsForMerchant()
    {
        $data = $this->merchant->bankingAccounts->callOnEveryItem('toArrayPublic');

        $data = array_map(array($this, 'transform'), $data);

        return $data;
    }

    public function transform($data)
    {
        $data[BankAccount\Entity::BANK_NAME] = $data[Entity::CHANNEL];

        $data['ifsc'] = $data[Entity::ACCOUNT_IFSC];

        unset($data[Entity::CHANNEL]);

        unset($data[Entity::ACCOUNT_IFSC]);

        return $data;
    }

    /**
     * @param string $channel
     * @param array  $input
     *
     * @throws LogicException
     */
    protected function throwUnhandledChannelException(string $channel, array $input)
    {
        throw new LogicException(
            'Banking Account logic undefined for channel: ' . $channel,
            null,
            $input);
    }
}
