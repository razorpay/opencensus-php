<?php

namespace RZP\Models\NodalBeneficiary;

use Config;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Jobs\FTS\CreateAccount;
use RZP\Models\FundAccount\Type;
use RZP\Models\Settlement\Metric as Metric;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundTransfer\Attempt\Type as ProductType;

class Core extends Base\Core
{
    /**
     * Create Nodal Beneficiary with bank account.
     *
     * @param array $input
     * @return Entity
     */

    // fetchNodalBeneficiaryCode input
    const BENEFICIARY_NAME           = 'beneficiary_name';
    const BENEFICIARY_IFSC_CODE      = 'beneficiary_ifsc_code';
    const BENEFICIARY_ACCOUNT_NUMBER = 'beneficiary_account_number';
    const CHANNEL                    = 'channel';
    const TYPE_MERCHANT              = 'merchant';
    const TYPE_CONTACT               = 'contact';
    const SOURCE_ACCOUNT_NUMBER      = 'source_account_number';

    // fetchNodalBeneficiaryCode output
    const BENEFICIARY_CODE           = 'beneficiary_code';

    const PRIMARY_YESBANK_NODAL_ACC = 'nodal.yesbank.primary';
    const BANKING_YESBANK_NODAL_ACC = 'nodal.yesbank.banking';



    public function createWithBankAccount(array $input): Entity
    {
        $merchantId = $input[Entity::MERCHANT_ID];

        $bankAccountId = $input[Entity::BANK_ACCOUNT_ID];

        unset($input[Entity::MERCHANT_ID]);

        unset($input[Entity::BANK_ACCOUNT_ID]);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $bankAccount = $this->repo->bank_account->findOrFail($bankAccountId);

        $nodalBeneficiary = (new Entity)->build($input);

        $nodalBeneficiary->merchant()->associate($merchant);

        $nodalBeneficiary->bankAccount()->associate($bankAccount);

        $this->repo->saveOrFail($nodalBeneficiary);

        return $nodalBeneficiary;
    }

    /**
     * Create Nodal Beneficiary with card.
     *
     * @param array $input
     * @return Entity
     */
    public function createWithCard(array $input): Entity
    {
        $merchantId = $input[Entity::MERCHANT_ID];

        $cardId = $input[Entity::CARD_ID];

        unset($input[Entity::MERCHANT_ID]);

        unset($input[Entity::CARD_ID]);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $card = $this->repo->card->findOrFail($cardId);

        $nodalBeneficiary = (new Entity)->build($input);

        $nodalBeneficiary->merchant()->associate($merchant);

        $nodalBeneficiary->card()->associate($card);

        $this->repo->saveOrFail($nodalBeneficiary);

        return $nodalBeneficiary;
    }

    /**
     * Update Nodal beneficiary with Bank Account
     *
     * @param array $input
     * @return mixed
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * @throws \RZP\Exception\LogicException
     */
    public function updateNodalBeneficiaryWithBankAccount(array $input)
    {
        $bankAccountId = $input[Entity::BANK_ACCOUNT_ID];

        unset($input[Entity::BANK_ACCOUNT_ID]);

        $validator = new Validator;

        $validator->validateInput('edit', $input);

        $channel = $input[Entity::CHANNEL];

        $nodalBeneficiary = $this->repo->nodal_beneficiary
                                 ->fetchBankAccountBeneficiaryDetailsForChannel(
                                     $bankAccountId,
                                     $channel
                                 );

        $validator->validateBankAccount($nodalBeneficiary, $bankAccountId);

        $validator->validateNewRegistrationStatus(
                        $input[Entity::REGISTRATION_STATUS],
                        $nodalBeneficiary->getRegistrationStatus()
                  );

        if (($input[Entity::REGISTRATION_STATUS] === Status::FAILED) and
            ($nodalBeneficiary->getRegistrationStatus() !== Status::FAILED))
        {
            $this->trace->count(
                Metric::BENEFICIARY_REGISTRATION_STATUS,
                [
                    Metric::CHANNEL => $channel
                ],
                1
            );
        }

        $nodalBeneficiary = $nodalBeneficiary->edit($input);

        $this->repo->nodal_beneficiary->saveOrFail($nodalBeneficiary);

        return $nodalBeneficiary;
    }

    /**
     * Update Nodal Beneficiary with card
     *
     * @param array $input
     * @return mixed
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * @throws \RZP\Exception\LogicException
     */
    public function updateNodalBeneficiaryWithCard(array $input)
    {
        $cardId = $input[Entity::CARD_ID];

        unset($input[Entity::CARD_ID]);

        $validator = new Validator;

        $validator->validateInput('edit', $input);

        $channel = $input[Entity::CHANNEL];

        $nodalBeneficiary = $this->repo->nodal_beneficiary
                                       ->fetchCardBeneficiaryDetailsForChannel(
                                           $cardId,
                                           $channel
                                       );

        $validator->validateCard($nodalBeneficiary, $cardId);

        $validator->validateNewRegistrationStatus(
            $input[Entity::REGISTRATION_STATUS],
            $nodalBeneficiary->getRegistrationStatus()
        );

        if (($input[Entity::REGISTRATION_STATUS] === Status::FAILED) and
            ($nodalBeneficiary->getRegistrationStatus() !== Status::FAILED))
        {
            $this->trace->count(
                Metric::BENEFICIARY_REGISTRATION_STATUS,
                [
                    Metric::CHANNEL => $channel
                ],
                1
            );
        }

        $nodalBeneficiary = $nodalBeneficiary->edit($input);

        $this->repo->nodal_beneficiary->saveOrFail($nodalBeneficiary);

        return $nodalBeneficiary;
    }

    /**
     * @param array $input
     * @return mixed
     */
    public function delete(array $input)
    {
        $nodalBeneficiary = $this->repo->nodal_beneficiary
                                 ->fetchBankAccountBeneficiaryDetailsForChannel(
                                     $input[Entity::BANK_ACCOUNT_ID],
                                     $input[Entity::CHANNEL]
                                 );

        return $this->repo->nodal_beneficiary->deleteOrFail($nodalBeneficiary);
    }

    /**
     * Creates or Updates the beneficiary using fund_account_id
     *
     * @param array $input
     * @return Entity
     */
    public function createOrUpdateBeneficiaryForFTS(array $input)
    {
        try
        {
            $validator = new Validator;

            $validator->validateInput('update', $input);

            $ftsFundAccountId = $input['fund_account_id'];

            $bankAccount = $this->repo->bank_account->getBankAccountByFtsFundAccountId($ftsFundAccountId);

            $nodalBeneficiary = [
                Entity::CHANNEL             => $input['channel'],
                Entity::MERCHANT_ID         => $bankAccount->merchant->getId(),
                Entity::BANK_ACCOUNT_ID     => $bankAccount->getId(),
                Entity::BENEFICIARY_CODE    => $bankAccount->getBeneficiaryCode(),
                Entity::REGISTRATION_STATUS => $input['status'],
            ];

            return $this->createWithBankAccount($nodalBeneficiary);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTS_BENEFICIARY_CREATE_OR_UPDATE_FAILED,
                [
                    'error' => $e->getMessage()
                ]);
        }
    }

    /**
     * Fetches the beneficiary code based on the bank details provided. This should be made redundant post
     * moving the early bene registration of yes bank via fts itself
     * Ref link https://razorpay.slack.com/archives/C01DQKYPMFU/p1617774389024400
     * @param array $input
     * @return array
     */

    public function fetchNodalBeneficiaryCode(array $input)
    {
        try
        {
            $validator = new Validator;

            $validator->validateInput('fetch', $input);

            if (!$this->validateSourceBankAccount($input[self::CHANNEL], $input[self::SOURCE_ACCOUNT_NUMBER], self::PRIMARY_YESBANK_NODAL_ACC)) {
                $this->trace->info(
                    TraceCode::FTS_FETCH_NODAL_BENEFICIARY_ATTEMPT_FAILED,
                    [
                        'message'  => 'failed to match source account no',
                        'input'    => $input
                    ]
                );

                return [
                    self::BENEFICIARY_CODE  => ''
                ];
            }

            $bankAccountId = $this->fetchbenedetails($input, self::TYPE_MERCHANT);
            // if bank account is null for type merchant then we check for type contact
            if ($bankAccountId == null) $bankAccountId = $this->fetchBeneDetails($input, self::TYPE_CONTACT);

            if ($bankAccountId != null)
            {
                $beneficiaryCode = $bankAccountId[0];

                $this->trace->info(
                    TraceCode::FTS_FETCH_NODAL_BENEFICIARY_ATTEMPT_SUCCESS,
                    [
                        'message'   => 'successfully find bene code',
                        'input'     => $input,
                        'bene_code' => $beneficiaryCode
                    ]
                );

                return [
                    self::BENEFICIARY_CODE => $beneficiaryCode
                ];
            }
            else
            {
                $this->trace->info(
                    TraceCode::FTS_FETCH_NODAL_BENEFICIARY_ATTEMPT_FAILED,
                    [
                        'message'  => 'failed to find record',
                        'input'    => $input
                    ]
                );

                return [
                    self::BENEFICIARY_CODE  => ''
                ];
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTS_FETCH_NODAL_BENEFICIARY_ATTEMPT_FAILED,
                [
                    'error' => $e->getMessage()
                ]);
        }
    }

    /**
     * validates the source accoutno. Since there is no source account field present in the nodal beneficiary table,
     * have to apply the filter at the application level
     * @param string $channel
     * @param string $sourceAccountNumber
     * @return bool
     */
    private function validateSourceBankAccount(string $channel, string $sourceAccountNumber, string $nodalAccType)
    {
        /**
         *  For this route currently we have use case of fetching settlement type source account means account
         */
         switch (strtolower($channel))
         {
             case 'yesbank':
                 $config = Config::get($nodalAccType);

                 $primaryAccountNumber = $config['account_number'];

                 $this->trace->debug(
                     TraceCode::FTS_FETCH_NODAL_BENEFICIARY_DEBUG,
                     [
                         'stored_source_account_no'  => mask_except_last4($primaryAccountNumber),
                         'input_source_account_no'   => mask_except_last4($sourceAccountNumber),
                         'nodalAccType'              => $nodalAccType
                     ]
                 );

                 return $sourceAccountNumber == $primaryAccountNumber;
             default:
                 return false;
         }
    }

    private function fetchBeneDetails(array $input, string $type) {
        $bankAccountId = $this->repo->nodal_beneficiary->fetchRegisteredBeneficiaryCodeForBeneDetails(
            $input[self::BENEFICIARY_NAME],
            $input[self::BENEFICIARY_IFSC_CODE],
            $input[self::BENEFICIARY_ACCOUNT_NUMBER],
            strtolower($input[self::CHANNEL]),
            $type
        );
        if ($bankAccountId != null) {
            $this->trace->info(
                TraceCode::FTS_FETCH_NODAL_BENEFICIARY_DEBUG,
                [
                    'message'  => 'bene found',
                    'input'    => $input,
                    'type'     => $type
                ]
            );
        }
        return $bankAccountId;
    }
}
