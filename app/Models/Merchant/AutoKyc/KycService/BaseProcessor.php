<?php

namespace RZP\Models\Merchant\AutoKyc\KycService;

use App;
use RZP\Http\Request\Requests;

use RZP\Models\Merchant\AutoKyc\Core;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Processor;

abstract class BaseProcessor implements Processor
{
    protected $config;

    public $trace;

    protected $app;

    protected $input;

    use KycServiceClient;

    /**
     * @var int Default timeout
     */
    protected $timeout = 10; //seconds

    /**
     * AbstractVerifier constructor.
     *
     * @param array $input
     *
     */
    public function __construct(array $input)
    {
        $app = App::getFacadeRoot();

        $this->app    = $app;
        $this->config = $app['config']['applications.kyc'];
        $this->trace  = $app['trace'];

        $this->input = $input;
    }

    /**
     * Returns applicable api request .
     *
     * While calling kyc service we have create or update resource
     *
     * If document is already present in kyc service then we need to update (PUT method)
     * else we have to create resource (POST Method)
     *
     * @param string $documentType
     *
     * @return array
     */

    public function getApplicableRequestDetails(string $documentType): array
    {
        $isDocumentAlreadyPresentInKycService = (new Core())->isDocumentAlreadyPresentInKycService(
            $this->input,
            $documentType);

        $method = $isDocumentAlreadyPresentInKycService === true ? Requests::PUT : Requests::POST;

        return [
            Constants::METHOD => $method,
        ];
    }
}
