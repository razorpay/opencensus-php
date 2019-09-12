<?php

namespace RZP\Services\FTS;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\NodalBeneficiary\Entity;
use RZP\Models\NodalBeneficiary\Core as NodalCore;
use RZP\Models\BankAccount\Core as BankAccountCore;

class RegisterAccount extends Base
{

    /**
     * Method to handle request to be sent to fts for
     * registering fund account
     *
     * @param array $ids
     * @param string $channel
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function registerFundAccount(string $channel, array $ids): array
    {
        $input = $this->makeRequestUsingIds($ids);

        $uri = parent::FUND_ACCOUNT_REGISTER_URI;

        if (empty($channel) === false)
        {
            $uri .= ('/'. $channel);
        }

        $response = $this->createAndSendRequest(
            $uri,
            'POST', $input);

        $this->updateNodalBeneficiaryStatus($response, $channel);

        return $response;
    }

    /**
     * Method to make request body
     *
     * @param array $ids
     * @return array
     */
    public function makeRequestUsingIds(array $ids)
    {
        return [
            'fund_account_ids' => $ids,
        ];
    }

    /**
     * Method to update registration status using response
     * from FTS
     *
     * @param $input
     * @param $channel
     * @return array
     */
    public function updateNodalBeneficiaryStatus($input, $channel)
    {
        $failureCount = 0;

        foreach ($input as $ftsAccountId => $value)
        {
            try
            {
                $bankAccount = (new BankAccountCore)->getBankAccountByFtsFundAccountId($ftsAccountId);

                $nodalBeneficiary = [
                    Entity::CHANNEL             => $channel,
                    Entity::MERCHANT_ID         => $bankAccount->merchant->getId(),
                    Entity::BANK_ACCOUNT_ID     => $bankAccount->getId(),
                    Entity::BENEFICIARY_CODE    => $bankAccount->getBeneficiaryCode(),
                    Entity::REGISTRATION_STATUS => $value['status'],
                ];

                (new NodalCore)->createWithBankAccount($nodalBeneficiary);
            }
            catch (\Throwable $e)
            {
                $failureCount++;

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::FTS_BENEFICIARY_CREATE_OR_UPDATE_FAILED,
                    [
                        'error'          => $e->getMessage(),
                        'fts_account_id' => $ftsAccountId,
                    ]);
            }
        }

        return [
            'channel'       => $channel,
            'total_count'   => count($input),
            'failure_count' => $failureCount,
        ];
    }

}
