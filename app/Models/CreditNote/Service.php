<?php

namespace RZP\Models\CreditNote;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;

class Service extends Base\Service
{
    public function create(array $input): array
    {
        $this->trace->info(TraceCode::CREDITNOTE_CREATE_REQUEST, $input);

        $creditNote = $this->core()->create($this->merchant, $input);

        return $creditNote->toArrayPublic();
    }


    public function apply(string $id, array $input): array
    {
        $this->trace->info(TraceCode::CREDITNOTE_APPLY_REQUEST, $input);

        $creditnote = $this->repo->creditnote->findByPublicIdAndMerchant($id, $this->merchant);

        $creditnote->getValidator()->validateInput('apply', $input);

        $creditnote = $this->core()->apply($creditnote, $this->merchant, $input,true);

        $this->trace->info(TraceCode::CREDITNOTE_APPLIED, $creditnote->toArrayPublic());

        return (new ViewDataSerializer($creditnote))->serializeForPublic();
    }

    public function fetchMultiple(array $input)
    {
        $creditnotes = $this->repo->creditnote->fetch($input, $this->merchant->getId());

        return $creditnotes->toArrayPublic();
    }

    public function fetch(string $id)
    {
        $creditnote = $this->repo->creditnote->findByPublicIdAndMerchant($id, $this->merchant);

        return (new ViewDataSerializer($creditnote))->serializeForPublic();
    }
}
