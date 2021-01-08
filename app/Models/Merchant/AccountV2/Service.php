<?php

namespace RZP\Models\Merchant\AccountV2;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail\Core;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\Validator;

class Service extends Merchant\Service
{
    protected $response;

    public function createAccountV2(array $input): array
    {
        $account = $this->core()->createAccountV2($this->merchant, $input);

        return $this->getResponseObject()->createResponse($account);
    }

    public function fetchAccountV2(string $accountId): array
    {
        $account = $this->core()->fetchAccountV2($accountId);

        return $this->getResponseObject()->createResponse($account);
    }

    protected function getResponseObject()
    {
        if ($this->response === null)
        {
            return new Response;
        }

        return $this->response;
    }

    /**
     * This function will upload files related to account or stakeholder by partner
     * Files of stakeholder will be fetched with account context. So using the same function for both
     * entities documents upload
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function uploadDocument(Merchant\Entity $merchant, array $input)
    {

        (new Validator)->validateInput('uploadDraftDocument', $input);

        $merchantDetailsCore = new Core;

        $merchantDetails = $merchantDetailsCore->getMerchantDetails($merchant, $input);

        $documentType = $input[Merchant\Detail\Constants::PURPOSE];

        $param = [
            $documentType => $input[Entity::FILE]
        ];

        return $this->uploadFileToUFH($merchantDetails, $param);
    }

    /**
     * @param Base\PublicEntity $publicEntity
     * @param array             $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function uploadFileToUFH(Base\PublicEntity $publicEntity, array $input): array
    {
        $params = [];

        $merchant = $publicEntity->merchant;

        $ufhService = $this->app['ufh.service'];

        $merchantDetailsService = new Merchant\Detail\Service();

        foreach ($input as $type => $file)
        {
            (new Validator)->validateFile($file);

            $fileName = $merchantDetailsService->getFileName($file, $merchant->getId());

            $fileMetaData = [
                Merchant\Document\Constants::CONTENT_DISPOSITION => Merchant\Document\Constants::CONTENT_DISPOSITION_INLINE
            ];

            $params[$type] = $ufhService->uploadFileAndGetResponse($file, $fileName, $type, $publicEntity, $fileMetaData);
        }

        return $params;
    }
}
