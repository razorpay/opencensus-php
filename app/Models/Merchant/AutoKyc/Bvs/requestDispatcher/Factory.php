<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;

class Factory
{
    public function getBvsRequestDispatchers(Merchant\Entity $merchant, Detail\Entity $merchantDetails): array
    {
        return [
            new CompanyPanOcr($merchant, $merchantDetails),
            new PersonalPanOcr($merchant, $merchantDetails),
            new CancelledChequeOcr($merchant, $merchantDetails),
            new ShopEstablishmentAuth($merchant, $merchantDetails),
            new LlpinAuth($merchant, $merchantDetails),
            new CinAuth($merchant, $merchantDetails),
        ];
    }
}
