<?php

namespace RZP\Models\Merchant\Product\TncMap\ConsentDocuments;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Product;

class RouteConsentDocumentsService extends ConsentDocumentsBaseService
{

    public function __construct()
    {
        parent::__construct();
    }

    public function createLegalDocuments(Merchant\Entity $merchant, Product\Entity $merchantProduct, string $activationFormMilestone)
    {
        //Currently creation of consent document for Route use cases is disabled.
        return ;
    }
}
