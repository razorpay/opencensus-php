<?php

namespace RZP\Models\BankingAccount;

use Mail;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
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
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_CREATE,
            [
                'input' => $input,
            ]);

        $account = $this->core->createBankingAccount($input, $this->merchant);

        $this->notifyUpdate('', [Entity::STATUS => Status::CREATED], $account);

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

        $previousStatus = $bankingAccount->getStatus();

        $channel = $bankingAccount->getChannel();

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_EDIT,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $channel,
                'input'   => $input,
            ]);

        (new Validator)->setStrictFalse()->validateInput(Validator::INTERNAL_EDIT, $input);

        $account = $this->core->updateBankingAccount($bankingAccount, $input);

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

        (new Validator)->validateInput(Validator::SERVICEABLE_PINCODE, $input);

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
            'Banking Account logic undefined for channel',
            null,
            [
                'input'     => $input,
                'channel'   => $channel
            ]);
    }

    protected function notifyUpdate(string $previousStatus, array $input, Entity $bankingAccount)
    {
        if ($this->isStatusChanged($previousStatus, $input[Entity::STATUS]) === true)
        {
            $mail = new NotifyStatusUpdate($input, $bankingAccount->merchant);

            Mail::queue($mail);
        }
    }

    protected function isStatusChanged(string $previousStatus, string $newStatus): bool
    {
        return $previousStatus !== $newStatus;
    }
}
