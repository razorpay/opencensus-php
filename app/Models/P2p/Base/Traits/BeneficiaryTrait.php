<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Models\P2p\Base\Entity;
use RZP\Models\P2p\Beneficiary\Entity as Beneficiary;

/**
 * Trait BeneficiaryTrait
 *
 * @package RZP\Models\P2p\Base\Traits
 */
trait BeneficiaryTrait
{
    public function buildBeneficiary(array $input): self
    {
        unset($input[Beneficiary::TYPE]);

        $this->input = $input;

        $this->validateInput('beneficiary', $input);

        $this->generate($input);

        $this->fill($input);

        $this->withoutDevice();

        return $this;
    }

    public function toArrayBeneficiary()
    {
        $array = $this->toArrayPublic();

        return [
            Beneficiary::VALIDATED  => true,
            Beneficiary::TYPE       => $array[self::ENTITY],
            self::ID                => $array[self::ID],
            self::ADDRESS           => $this->getAddress(),
            self::BENEFICIARY_NAME  => $this->getBeneficiaryName(),
        ];
    }

    public function isBeneficiary(): bool
    {
        return $this->getDeviceId() === null;
    }
}
