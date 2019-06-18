<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Mail\BankingAccount\NotifyStatusUpdate;

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

        $previousStatus = $bankingAccount->getStatus();

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

        $this->notifyUpdate($previousStatus, $input, $bankingAccount);

        return $account->toArrayPublic();
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

    protected function notifyUpdate(string $previousStatus, array $input, Entity $bankingAccount)
    {
        if (($this->isStatusChanged($previousStatus, $input[Entity::STATUS]) === true) and
            ($this->notifyStatusChange($previousStatus, $input[Entity::STATUS])) === true)
        {
            $mail = new NotifyStatusUpdate($input, $bankingAccount->merchant());

            Mail::queue($mail);
        }
    }

    protected function isStatusChanged(string $previousStatus, string $newStatus): bool
    {
        return $previousStatus !== $newStatus;
    }

    protected function notifyStatusChange(string $previousStatus, string $newStatus): bool
    {
        switch ($newStatus)
        {
            case Status::INITIATED:
                $result = in_array(
                    $previousStatus,
                    [Status::PROCESSED, Status::PROCESSING, Status::CANCELLED,],
                    true) === false;

                break;

            case Status::PROCESSING:
                $result = $previousStatus === Status::INITIATED;

                break;

            case Status::PROCESSED:
                $result = $previousStatus === Status::PROCESSING;

                break;

            case Status::CANCELLED:
                $result = $previousStatus === Status::PROCESSING;

                break;

            default:
                $result = false;
        }

        return $result;
    }
}
