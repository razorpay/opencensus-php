<?php

namespace RZP\Models\Merchant\Detail\Verifiers;

use App;

use RZP\Models\Merchant\Detail\POIStatus;

class PanVerifierResponse
{
    /**
     * @var array
     */
    protected $response;

    /**
     * @var bool
     */
    protected $mock;

    protected $panOwnerName;

    public function __construct(array $response)
    {
        $app = App::getFacadeRoot();

        $this->mock = $app['config']['applications.karza.mock'];

        $this->response = $response['data']['content']['response'] ?? [];
    }

    public function setPanOwnerName(string $panOwnerName)
    {
        $this->panOwnerName = $panOwnerName;
    }

    public function isNameMatch(): bool
    {
        if ($this->isCorrectDetails() === false)
        {
            return false;
        }
        $nameInResponse = strtolower(preg_replace('/\s+/', '', $this->response['result']['name']));
        $panOwnerName   = strtolower(preg_replace('/\s+/', '', $this->panOwnerName));

        return $panOwnerName === $nameInResponse;
    }

    public function isCorrectDetails()
    {
        $result = $this->response['result'] ?? null;

        if (empty($result) === true)
        {
            return false;
        }

        return true;
    }

    public function getStatus()
    {
        if ($this->isCorrectDetails() === false)
        {
            return POIStatus::INCORRECT_DETAILS;
        }

        if ($this->isNameMatch() === false)
        {
            return POIStatus::NOT_MATCHED;
        }

        return POIStatus::VERIFIED;
    }
}
