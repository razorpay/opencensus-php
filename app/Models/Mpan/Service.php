<?php


namespace RZP\Models\Mpan;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $mpan = $this->core()->create($input);

        return $mpan->toArrayPublic();
    }

    public function issueMpans(array $input)
    {
        $validator = new Validator();

        $validator->validateInput(CONSTANTS::ISSUE_MPANS_OPERATION, $input);

        $mpans = $this->core()->issueMpans($input);

        return (new Base\PublicCollection($mpans))->toArrayPublic();
    }

    public function fetchMpans(array $input)
    {
        $mpans = $this->repo->mpan->fetch($input, $this->merchant->getId());

        return (new Base\PublicCollection($mpans))->toArrayPublic();
    }
}
