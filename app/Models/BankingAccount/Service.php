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

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_CREATE,
            [
                'channel'            => $channel,
                'input'              => $input,
            ]);

        $account = $this->core->createBankingAccount($input, $this->merchant);

        return $account->toArrayPublic();
    }

    /**
     * This function to be used only for admin or internal routes since
     * we are not fetching banking_account by merchant_id.
     *
     * @param string $id
     * @param array  $input
     *
     * @return array
     */
    public function update(string $id, array $input): array
    {
        /** @var Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($id);

        $channel = $bankingAccount->getChannel();

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_EDIT,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $channel,
                'input'   => $input,
            ]);

        $account = $this->core->updateBankingAccount($bankingAccount, $input);

        return $account->toArrayPublic();
    }

    public function addOrRemoveServiceablePincodes(array $input, $channel)
    {
        $input[Entity::CHANNEL] = $channel;

        (new Validator)->validateInput('serviceable_pincode', $input);

        $coreMethod = $input[Entity::ACTION] . 'ServiceablePincodes';

        $this->core->$coreMethod($input[Entity::PINCODES], $channel);

        return ['success' => true];
    }

    public function fetchMultiple()
    {
        return $this->merchant->bankingAccounts;
    }

    public function processAccountInfoWebhook(string $channel, array $input)
    {
        $this->trace->info(
            TraceCode::BANK_ACCOUNT_INFO_WEBHOOK_REQUEST,
            [
                'input'         => $input,
                'gateway'       => $channel,
            ]);

        Channel::validateChannel($channel);

        $response = $this->core->processAccountInfoWebhook($channel, $input);

        return $response;
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
            [
                'input'     => $input,
                'channel'   => $channel
            ]);
    }
}
