<?php

namespace RZP\Models\BankTransfer;

use RZP\Constants;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Models\FundAccount;
use RZP\Models\PayoutSource;

class PayoutsClient extends Core
{
    const BANK_TRANSFER_TO_PAYOUT_MODE_MAPPING = [
        Mode::UPI  => Payout\Mode::NEFT,
        Mode::IMPS => Payout\Mode::NEFT,
        Mode::FT   => Payout\Mode::NEFT,
        Mode::IFT  => Payout\Mode::NEFT,
        Mode::NEFT => Payout\Mode::NEFT,
        Mode::RTGS => Payout\Mode::RTGS,
    ];

    /**
     * IMPORTANT:
     *
     * Smart Collect/Fund Loading acts as an app/source for the payout in this case and the code written below
     * should ideally be a service call. But, there are alot of service level validations on auth. Since this code
     * is currently being called internally, we have called the core->create functions of each entity.
     * When this is shifted to a separate service, we shall just make a composite API call as all other services do.
     *
     * @param Entity $bankTransfer
     *
     * @throws \RZP\Exception\BadRequestException
     */
    public function refundFundLoadingViaPayout(Entity $bankTransfer)
    {
        $sourceMerchant = $bankTransfer->merchant;

        $contactPayload = $this->getContactCreationPayload($bankTransfer);

        $contact = (new Contact\Core)->create($contactPayload, $sourceMerchant);

        $fundAccountPayload = $this->getFundAccountCreationPayload($bankTransfer, $contact);

        $fundAccount = (new FundAccount\Core)->create($fundAccountPayload, $sourceMerchant, $contact);

        $payoutPayload = $this->getPayoutCreationPayload($bankTransfer, $fundAccount);

        $payoutCreationResponse = (new Payout\Core)->createPayoutToFundAccount($payoutPayload, $sourceMerchant);

        $this->trace->info(TraceCode::FUND_LOADING_REFUND_VIA_X_PAYOUT_CREATION_RESPONSE,
                           [
                               Payout\Entity::ID                  => $payoutCreationResponse['id'],
                               Payout\Entity::STATUS              => $payoutCreationResponse['status'],
                               Payout\Entity::AMOUNT              => $payoutCreationResponse['amount']
                           ]);
    }

    /**
     * Returns payload used by the payout core create method.
     *
     * Note that this payload is not the same as the payout create API payload, since we are passing
     * the balanceId here and not the source account number of the merchant. When we shift this to a separate service,
     * then we shall use composite API with the source account number only
     *
     * @param Entity             $bankTransfer
     * @param FundAccount\Entity $fundAccount
     *
     * @return array
     */
    protected function getPayoutCreationPayload(Entity $bankTransfer,
                                                FundAccount\Entity $fundAccount)
    {
        // Payout Data
        $payoutMode   = self::BANK_TRANSFER_TO_PAYOUT_MODE_MAPPING[strtolower($bankTransfer->getMode())];
        $payoutAmount = $bankTransfer->getAmount();

        // Bank Transfer Data
        $bankTransferUtr = $bankTransfer->getUtr();

        // Banking Balance Data
        $sourceBalanceId = $bankTransfer->getBalanceId();

        // Payout Source Data
        $payoutSourceId   = $bankTransfer->getPublicId();
        $payoutSourceType = Constants\Entity::BANK_TRANSFER;

        return [
            Payout\Entity::FUND_ACCOUNT_ID      => $fundAccount->getPublicId(),
            Payout\Entity::BALANCE_ID           => $sourceBalanceId,
            Payout\Entity::AMOUNT               => $payoutAmount,
            Payout\Entity::CURRENCY             => 'INR',
            Payout\Entity::MODE                 => $payoutMode,
            Payout\Entity::PURPOSE              => Payout\Purpose::REFUND,
            Payout\Entity::QUEUE_IF_LOW_BALANCE => true,
            Payout\Entity::REFERENCE_ID         => $bankTransferUtr,
            Payout\Entity::SOURCE_DETAILS       => [
                [
                    PayoutSource\Entity::SOURCE_ID   => $payoutSourceId,
                    PayoutSource\Entity::SOURCE_TYPE => $payoutSourceType,
                    PayoutSource\Entity::PRIORITY    => 1,
                ],
            ],
        ];
    }

    protected function getContactCreationPayload(Entity $bankTransfer)
    {
        $payerName = $bankTransfer->payerBankAccount->getName();

        return [
            Contact\Entity::NAME => $payerName,
        ];
    }

    protected function getFundAccountCreationPayload(Entity $bankTransfer,
                                                     Contact\Entity $contact)
    {
        $payerName            = $bankTransfer->payerBankAccount->getName();
        $payerIfscCode        = $bankTransfer->payerBankAccount->getIfscCode();
        $payerAccountNumber   = $bankTransfer->payerBankAccount->getAccountNumber();
        $updatedPayerIfscCode = null;

        if (array_key_exists($payerIfscCode, BankAccount\OldNewIfscMapping::$oldToNewIfscMapping) === true)
        {
            $updatedPayerIfscCode = BankAccount\OldNewIfscMapping::getNewIfsc($payerIfscCode);

            $this->trace->info(TraceCode::BANK_ACCOUNT_OLD_TO_NEW_IFSC_BEING_USED,
                               [
                                   'old_ifsc' => $payerIfscCode,
                                   'new_ifsc' => $updatedPayerIfscCode,
                               ]);
        }

        return [
            FundAccount\Entity::CONTACT_ID   => $contact->getPublicId(),
            FundAccount\Entity::ACCOUNT_TYPE => FundAccount\Entity::BANK_ACCOUNT,
            FundAccount\Entity::BANK_ACCOUNT => [
                BankAccount\Entity::NAME           => $payerName,
                BankAccount\Entity::IFSC           => $updatedPayerIfscCode ?? $payerIfscCode,
                BankAccount\Entity::ACCOUNT_NUMBER => $payerAccountNumber,
            ]
        ];
    }
}
