<?php

namespace RZP\Models\PaperMandate\PaperMandateUpload;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\PaperMandate;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;

class Core extends Base\Core
{
    public function create(array $input, PaperMandate\Entity $paperMandate): Entity
    {
        $paperMandateUpload = (new Entity)->generateId();

        $paperMandateUpload->paperMandate()->associate($paperMandate);

        $paperMandateUpload->merchant()->associate($paperMandate->merchant);

        $uploadFileId = (new PaperMandate\FileUploader($paperMandate))->uploadUploadedForm($input[PaperMandate\Entity::FORM_UPLOADED]);

        $paperMandateUpload->build([
            Entity::UPLOADED_FILE_ID => $uploadFileId
        ]);

        $this->repo->saveOrFail($paperMandateUpload);

        $this->extractAndStoreDataFromUploadedForm($input, $paperMandateUpload, $paperMandate);

        $paperMandateUpload->validateExtractedData();

        return $paperMandateUpload;
    }

    public function createForPayment(array $input, PaperMandate\Entity $paperMandate): Entity
    {
        $paperMandateUpload = (new Entity)->generateId();

        $paperMandateUpload->validateInput(__FUNCTION__, $input);

        $paperMandateUpload->paperMandate()->associate($paperMandate);

        $paperMandateUpload->merchant()->associate($paperMandate->merchant);

        $uploadFileId = (new PaperMandate\FileUploader($paperMandate))->uploadUploadedForm(
            $input[PaperMandate\Entity::FORM_UPLOADED]);

        $paperMandateUpload->build([Entity::UPLOADED_FILE_ID => $uploadFileId]);

        $this->repo->saveOrFail($paperMandateUpload);

        $this->extractAndStoreDataFromUploadedFormForPayment($input, $paperMandateUpload, $paperMandate);

        $paperMandateUpload->validateExtractedDataForPayment();

        return $paperMandateUpload;
    }

    protected function extractAndStoreDataFromUploadedForm(array $input, Entity $paperMandateUpload, PaperMandate\Entity $paperMandate)
    {
        $timeStarted = microtime(true);

        try
        {
            $extractedMandateData = $this->app->hyperVerge->extractNACHWithOutputImage($input, $paperMandate);

            $fileId = (new PaperMandate\FileUploader($paperMandate))->uploadEnhancedForm($extractedMandateData[Entity::ENHANCED_IMAGE]);

            $paperMandateUpload->setEnhancedFileId($fileId);

            $paperMandateUpload->edit($extractedMandateData);
        }
        catch (BadRequestException $e)
        {
            $paperMandateUpload->setStatus(Status::REJECTED);

            $paperMandateUpload->setStatusReason($e->getError()->getDescription() ?? '');

            throw $e;
        }
        catch (\Exception $e)
        {
            $paperMandateUpload->setStatus(Status::FAILED);

            $paperMandateUpload->setStatusReason($e->getCode());

            throw $e;
        }
        finally
        {
            $timeTaken = microtime(true) - $timeStarted;

            $paperMandateUpload->setTimeTakenToProcess($timeTaken);

            $this->repo->saveOrFail($paperMandateUpload);
        }
    }

    protected function extractAndStoreDataFromUploadedFormForPayment(
        array $input,
        Entity $paperMandateUpload,
        PaperMandate\Entity $paperMandate)
    {
        try
        {
            $this->extractAndStoreDataFromUploadedForm($input, $paperMandateUpload, $paperMandate);
        }
        catch (BadRequestException $e)
        {
            $this->trace->traceException($e);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_NACH_IMAGE_NOT_CLEAR);
        }
        catch (\Exception $e)
        {
            throw new ServerErrorException(
                'nach form data extraction failed',
                ErrorCode::SERVER_ERROR_NACH_EXTRACT_IMAGE_FAILED,
                ['paper_mandate_id' => $paperMandate->getId()],
                $e);
        }
    }
}
