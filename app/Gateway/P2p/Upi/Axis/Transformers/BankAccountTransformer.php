<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\BankAccount\Bank;
use RZP\Models\P2p\BankAccount\Type;
use RZP\Models\P2p\BankAccount\Entity;
use RZP\Models\P2p\BankAccount\Credentials;

class BankAccountTransformer extends Transformer
{
    public function transform(): array
    {
        $output = [
            Entity::IFSC                    => $this->input[Fields::IFSC],
            Entity::BENEFICIARY_NAME        => $this->input[Fields::NAME],
            Entity::MASKED_ACCOUNT_NUMBER   => $this->input[Fields::MASKED_ACCOUNT_NUMBER],
            Entity::GATEWAY_DATA            => $this->transformGatewayData(),
            Entity::CREDS                   => $this->transformCreds(),
            Entity::TYPE                    => $this->transformType(),
        ];

        return $output;
    }

    public function transformGatewayData()
    {
        $gatewayData = array_only($this->input, [
            Fields::BANK_NAME,
            Fields::BANK_CODE,
            Fields::BRANCH_NAME,
            Fields::REFERENCE_ID,
            Fields::BANK_ACCOUNT_UNIQUE_ID,
        ]);

        // This is by which API will identify the bank account
        $gatewayData[Entity::ID] = $gatewayData[Fields::BANK_ACCOUNT_UNIQUE_ID];

        $gatewayData[Fields::VPA_SUGGESTIONS] = implode(',', $this->input[Fields::VPA_SUGGESTIONS] ?? []);

        return $gatewayData;
    }

    public function transformCreds()
    {
        $creds = [
            [
                Credentials::TYPE       => Credentials::PIN,
                Credentials::SUB_TYPE   => Credentials::UPI_PIN,
                Credentials::SET        => $this->toBoolean($this->input[Fields::MPIN_SET]),
                Credentials::LENGTH     => $this->toInteger($this->input[Fields::MPIN_LENGTH]),
            ],
            [
                Credentials::TYPE       => Credentials::OTP,
                Credentials::SUB_TYPE   => Credentials::SMS,
                Credentials::SET        => isset($this->input[Fields::OTP_LENGTH]),
                Credentials::LENGTH     => $this->toInteger($this->input[Fields::OTP_LENGTH] ?? 4),
            ],
            [
                Credentials::TYPE       => Credentials::PIN,
                Credentials::SUB_TYPE   => Credentials::ATM_PIN,
                Credentials::SET        => isset($this->input[Fields::ATM_PIN_LENGTH]),
                Credentials::LENGTH     => $this->toInteger($this->input[Fields::ATM_PIN_LENGTH] ?? 4),
            ],
        ];

        return $creds;
    }

    public function transformType()
    {
        $map = [
            Fields::SAVINGS            => Type::SAVINGS,
            Fields::CURRENT            => Type::CURRENT,
            Fields::SOD                => Type::SOD,
            Fields::UOD                => Type::UOD
        ];

        if (isset($map[$this->input[Fields::TYPE]]) === true)
        {
            return $map[$this->input[Fields::TYPE]];
        }
        else
        {
            return $this->input[Fields::TYPE];
        }
    }

    public function transformBanks($handle) : array
    {
        return [
            Bank\Entity::NAME               => $this->input[Fields::NAME],
            Bank\Entity::HANDLE             => $handle,
            BANK\Entity::GATEWAY_DATA       => $this->transformBankGatewayData(),
            BANK\Entity::UPI_IIN            => $this->input[Fields::CODE],
            BANK\Entity::ACTIVE             => true,
        ];
    }

    public function transformBankGatewayData()
    {
        $gatewayData = [];

        $gatewayData[Entity::ID] = $this->input[Fields::REFERENCE_ID];

        return $gatewayData;
    }
}
