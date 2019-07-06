<?php

namespace RZP\Models\BankingAccount;

use Mail;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
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

        $this->notifyUpdate('', [Entity::STATUS => Status::CREATED], $account);

        return $account->toArrayPublic();
    }

    public function update(string $id, array $input): array
    {
        /** @var Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($id);

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
  
    public function storeCredentials(string $id, array $input)
    {
        $bankingAccount = $this->repo->banking_account->findByIdAndMerchant($id, $this->merchant);

        $channel = $bankingAccount->getChannel();

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_SAVE_MERCHANT_CREDENTIALS_REQUEST,
            [
                'id'        => $id,
                'channel'   => $channel
            ]);

        switch ($channel)
        {
            case Channel::RBL:
                // Here 3 API calls are being made, if any of the call fails or for some reason we are
                // not able to persist the response, we will ask the merchant to enter his credentials
                // again and make the 3 calls. Later we can separate these calls and have some retry logic
                // at our end.

                try
                {
                    $this->core->createMerchantTokenForRbl($bankingAccount, $input);

                    $fundAccountId = $this->core->createOrFetchFtsFundAccountForMerchant($bankingAccount);

                    $this->core->createMerchantSourceAccountForRbl($bankingAccount, $fundAccountId);

                    $this->core->updateAccountToProcessed($bankingAccount);

                    $success = true;
                }
                catch (\Throwable $ex)
                {
                    $success = false;

                    $this->trace->traceException($ex, Trace::CRITICAL);
                }

                break;

            default:
                $this->throwUnhandledChannelException($channel, $input);

        }

        return ['success' => $success];
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
            $mail = new NotifyStatusUpdate($input, $bankingAccount->merchant);

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
            case Status::CREATED:
                $result = in_array(
                    $previousStatus,
                    [Status::PROCESSED, Status::PROCESSING, Status::CANCELLED, Status::INITIATED, Status::CREATED],
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
                $result = true;
        }

        return $result;
    }
}
