<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
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

    public function addOrRemoveServiceablePincodes(array $input, $channel)
    {
        $input[Entity::CHANNEL] = $channel;

        (new Validator)->validateInput('serviceable_pincode', $input);

        $coreMethod = $input[Entity::ACTION] . 'ServiceablePincodes';

        switch ($channel)
        {
            case Channel::RBL:
                $coreMethod = $coreMethod . 'ForRbl';

                $this->core->$coreMethod($input[Entity::PINCODES]);

                break;

            default:
                $this->throwUnhandledChannelException($channel, $input);

                return null;
        }

        return ['success' => true];
    }

    public function fetchMultiple()
    {
        return $this->merchant->bankingAccounts;
    }

    public function processBankAccountInfoNotification(string $channel, array $input)
    {
        $this->trace->info(
            TraceCode::BANK_ACCOUNT_INFO_WEBHOOK_REQUEST,
            [
                'input'         => $input,
                'gateway'       => $channel,
            ]);

        switch ($channel)
        {
            case Channel::RBL:

            try
                {
                    $this->core->processRblBankAccountInfoNotification($input);

                    $response = $this->core->prepareRblNotificationResponse(Status::SUCCESS, $input);
                }
                catch (\Exception $e)
                {
                    $response = $this->core->prepareRblNotificationResponse(Status::FAILURE, $input);
                }

                return $response;

            default:
                $this->throwUnhandledChannelException($channel, $input);
        }
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
