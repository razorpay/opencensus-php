<?php

namespace RZP\Models\Merchant\Document\FileHandler;

use App;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Document\Entity;
use RZP\Models\Merchant\Document\Source;

class Factory
{

    /**
     * @param string $source
     *
     * @return FileHandlerInterface
     * @throws LogicException
     */
    public static function getFileStoreHandler(string $source): FileHandlerInterface
    {
        switch ($source)
        {
            case Source::UFH :

                return new UFHFileHandler();

            case Source::API:

                return new APIFileHandler();

            default;
                throw new LogicException(
                    ErrorCode::INVALID_ARGUMENT_INVALID_FILE_HANDLER_SOURCE,
                    [Entity::SOURCE => $source]);
        }
    }

    public static function getApplicableSource(string $merchantId, string $fileStoreId = null): string
    {
        if (empty($fileStoreId) === false)
        {
            return self::getSourceForExistingDocuments($fileStoreId);
        }

        return self::shouldServeFileFromUFH($merchantId) ? Source::UFH : Source::API;
    }

    /**
     * Finds file store source for existing documents .
     * Currently we are storing source and fileId in merchant document table .
     *
     * @param string $fileStoreId
     *
     * @return string
     */
    private static function getSourceForExistingDocuments(string $fileStoreId)
    {
        $app = App::getFacadeRoot();

        $repo = $app['repo'];

        $document = $repo->merchant_document->findDocumentByFileStoreId($fileStoreId);

        return optional($document)->getFileStoreSource() ?? Source::API;
    }

    /**
     * @param string $merchantId
     *
     * @return bool
     */
    private static function shouldServeFileFromUFH(string $merchantId): bool
    {
        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'] ?? Mode::LIVE;

        $status = $app['razorx']->getTreatment($merchantId,
                                               RazorxTreatment::USE_UFH_FILE_STORE,
                                               $mode);

        return (strtolower($status) === 'on');
    }

    /**
     *
     *
     * In bank account change flow we are uploading file to api file store and replacing file from file_id.
     * So after workflow approval we won't have knowledge of source. So here we are trying to guess the source .
     *
     * First we will try to find the file id in api file store if its present we will return same else we return
     *
     * UFH as source
     *
     * @param string $fileStoreId
     * @param string $merchantId
     *
     * @return string
     * @throws LogicException
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function getDocumentSource(string $fileStoreId, string $merchantId)
    {
        $detailService = new Detail\Service();

        $signedUrlFromApi = null;

        try
        {
            $detailService->getSignedUrl($fileStoreId, $merchantId, Source::API);

            return Source::API;
        }
        catch (BadRequestException $e)
        {
            return Source::UFH;
        }

    }
}
