<?php

namespace RZP\Services\Mock\HyperVerge;

use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Models\PaperMandate;
use RZP\Services\HyperVerge as BaseHyperVerge;
use RZP\Models\PaperMandate\HyperVerge as HyperVergeEntity;

class HyperVerge extends BaseHyperVerge
{
    public function extractNACHWithOutputImage(array $input, PaperMandate\Entity $paperMandate)
    {
        $this->trace->info(
            TraceCode::PAPER_MANDATE_EXTRACT_FORM_REQUEST_TO_HYPERVERGE,
            [
                'paper_mandate_id' => $paperMandate->getPublicId(),
                'mocked'           => true
            ]);

        $hyperVergeEntity = new HyperVergeEntity();

        // mock for contents
        $responseData = [
            'status'     => 'success',
            'statusCode' => 200,
            'result'     => [
                'type'    => 'nach',
                'details' => [
                    'emailId' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $paperMandate->bankAccount->getBeneficiaryEmail(),
                            'conf'           => 100,
                        ],
                    'phoneNumber' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $paperMandate->bankAccount->getBeneficiaryMobile(),
                            'conf'           => 100
                        ],
                    'amountInNumber' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $paperMandate->getAmount() / 100,
                            'conf'           => 100
                        ],
                    'amountInWords' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $hyperVergeEntity->getAmountInWords($paperMandate->getAmount()),
                            'conf'           => 94,
                        ],
                    'utilityCode' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $paperMandate->getUtilityCode(),
                            'conf'           => 100,
                        ],
                    'reference1' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $paperMandate->getReference1(),
                            'conf'           => 99
                        ],
                    'reference2' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $paperMandate->getReference2(),
                            'conf'           => 98
                        ],
                    'MICR' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => null,
                            'conf'           => 97
                        ],
                    'primaryAccountHolder' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value' => $paperMandate->bankAccount->getBeneficiaryName(),
                            'conf' => 100
                        ],
                    'secondaryAccountHolder' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value' => $paperMandate->getSecondaryAccountHolder(),
                            'conf' => 100
                        ],
                    'tertiaryAccountHolder' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $paperMandate->getTertiaryAccountHolder(),
                            'conf'           => 100
                        ],
                    'signaturePresentPrimary' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value' => 'yes',
                            'conf' => 100
                        ],
                    'signaturePresentSecondary' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => 'no',
                            'conf'           => 100
                        ],
                    'signaturePresentTertiary' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => 'no',
                            'conf'           => 100
                        ],
                    'NACHType' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => 'create',
                            'conf'           => 100
                        ],
                    'nachDate' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => date( 'd/m/o',$paperMandate->getCreatedAt()),
                            'conf'           => 100
                        ],
                    'UMRN' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => null,
                            'conf'           => 0
                        ],
                    'companyName' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $hyperVergeEntity->getCompanyName($paperMandate),
                            'conf'           => 100
                        ],
                    'bankName' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $paperMandate->bankAccount->getBankName(),
                            'conf'           => 100
                        ],
                    'accountNumber' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $paperMandate->bankAccount->getAccountNumber(),
                            'conf'           => 100
                        ],
                    'IFSCCode' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $paperMandate->bankAccount->getIfscCode(),
                            'conf'           => 99
                        ],
                    'untilCanceled' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $paperMandate->getEndAt() === null ? true : false,
                            'conf'           => 100
                        ],
                    'frequency' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $this->getUnFormattedFrequency($paperMandate->getFrequency()),
                            'conf'           => 100
                        ],
                    'debitType' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $this->getUnFormattedDebitType($paperMandate->getDebitType()),
                            'conf'           => 100
                        ],
                    'accountType' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value'          => $this->getUnFormattedAccountType($paperMandate->bankAccount->getAccountType()),
                            'conf'           => 100
                        ],
                    'sponsorCode' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value' => $paperMandate->getSponsorBankCode(),
                            'conf' => 100
                        ],
                    'startDate' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value' => date( 'd/m/o', $paperMandate->getStartAt()),
                            'conf' => 100
                        ],
                    'endDate' =>
                        [
                            'value' => date( 'd/m/o', $paperMandate->getEndAt()),
                            'conf' => 0
                        ],
                    'uid' =>
                        [
                            'to-be-reviewed' => 'no',
                            'value' => $paperMandate->getFormChecksum(),
                            'conf' => 52
                        ],
                    'base64AlignedJPEG' => base64_encode($input[PaperMandate\Entity::FORM_UPLOADED]),
                ]
            ]
        ];

        return $this->mapExtractedData($responseData[self::RESULT][self::DETAILS]);
    }

    public function generateNACH(array $input, PaperMandate\Entity $paperMandate)
    {
        $this->trace->info(
            TraceCode::PAPER_MANDATE_CREATE_FORM_REQUEST_TO_HYPERVERGE,
            [
                'paper_mandate_id' => $paperMandate->getPublicId(),
                'mocked'           => true
            ]);

        // mock for contents
        $responseData = [
            'status'     => 'success',
            'statusCode' => 200,
            'result'     => [
                'outputImage' => base64_encode(file_get_contents(__DIR__ . '/sample_form.pdf')),
                'uid'         => 'XXXXXXX',
            ]
        ];

        return $responseData[self::RESULT];
    }

    private function getUnFormattedAccountType(string $accountType): string
    {
        switch ($accountType)
        {
            case BankAccount\AccountType::SAVINGS:
                return BaseHyperVerge::SB;
            case BankAccount\AccountType::CURRENT:
                return BaseHyperVerge::CA;
            default:
                return '';
        }
    }

    private function getUnFormattedFrequency(string $frequency): string
    {
        switch ($frequency)
        {
            case PaperMandate\Frequency::AS_AND_WHEN_PRESENTED:
                return 'whenPresented';
            case PaperMandate\Frequency::YEARLY:
                return 'yearly';
            default:
                return '';
        }
    }

    protected function getUnFormattedDebitType(string $debitType): string
    {
        switch ($debitType)
        {
            case PaperMandate\DebitType::FIXED_AMOUNT:
                return 'fixedAmount';
            case PaperMandate\DebitType::MAXIMUM_AMOUNT:
                return 'maximumAmount';
            default:
                return '';
        }
    }
}
