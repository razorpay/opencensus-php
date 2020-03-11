<?php

namespace RZP\Models\PaperMandate\PaperMandateUpload;

use RZP\Models\Base;
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

        $paperMandateUpload->build([]);

        $this->repo->saveOrFail($paperMandateUpload);

        $this->extractAndStoreDataFromUploadedForm($input, $paperMandateUpload, $paperMandate);

        $paperMandateUpload->validateExtractedData();

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
        catch (ServerErrorException $e)
        {
            $paperMandateUpload->setStatus(Status::FAILED);

            throw $e;
        }
        finally
        {
            $timeTaken = microtime(true) - $timeStarted;

            $paperMandateUpload->setTimeTakenToProcess($timeTaken);

            $this->repo->saveOrFail($paperMandateUpload);
        }
    }
}
