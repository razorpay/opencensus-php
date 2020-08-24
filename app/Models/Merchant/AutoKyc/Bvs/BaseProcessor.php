<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use App;
use Twirp\Error;
use RZP\Models\Merchant\AutoKyc\Response;

abstract class BaseProcessor implements Processor
{
    protected $input;

    protected $trace;

    /**
     *
     * @param array $input
     */
    public function __construct(array $input)
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->input = $input;
    }

    /**
     * @return array
     */
    public function getOwnerInput(): array
    {
        return [
            Constant::PLATFORM   => Constant::PG,
            Constant::OWNER_ID   => $this->input[Constant::OWNER_ID],
            Constant::OWNER_TYPE => Constant::MERCHANT,
        ];
    }

    /**
     * this function basically aggregates the payload together and push it to BVS client for creation of validation.
     *
     * @return Response
     * @throws \ErrorException
     * @throws \RZP\Exception\IntegrationException
     */
    public function Process(): Response
    {
        $validation = [];

        $validation[Constant::ARTEFACT] = $this->GetArtefact();

        $validation[Constant::ENRICHMENTS] = $this->GetEnrichments();

        $validation[Constant::RULES] = $this->GetRules();

        $response = (new BvsClient())->CreateValidation($validation);

        return new BaseResponse($response);
    }
}
