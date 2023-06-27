<?php


namespace RZP\Models\Batch\Processor;


use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Batch\Header;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\BankingAccount\Activation\Comment;

class IciciVideoKycBulkUpload extends Base
{
    public function addSettingsIfRequired(& $input)
    {
        if(isset($input[Batch\Entity::CONFIG]) === false)
        {
            $input[Batch\Entity::CONFIG] = [];
        }

        /** @var AdminEntity $admin */
        $admin = $this->app['basicauth']->getAdmin();

        // Saving admin_id, admin_email and admin_name in settings so that
        // they are accessible in the job that executes the batch.
        // admin_id is needed to determine who added the comment.
        // admin_email is needed as recipients of the email that
        // is sent after processing the batch job.
        // admin_name needed and as it needed to send it to BAS
        //TODO: Ask Vishaal if below is correct
        $input[Batch\Entity::CONFIG][Comment\Entity::ADMIN_ID] = $admin->getId();

        $input[Batch\Entity::CONFIG][Comment\Entity::ADMIN_EMAIL] = $admin->getEmail();

        $input[Batch\Entity::CONFIG][Comment\Entity::ADMIN_NAME] = $admin->getName();
    }

    protected function validateHeaders(array $rows, $delimiter)
    {
        $headings = $this->getHeadings();
        $firstRow = str_getcsv(current($rows), $delimiter);

        if (Header::areTwoHeadersSame($headings, $firstRow) === false)
        {
            $msg = 'Uploaded file has invalid headers. Acceptable headers are [%s]';

            $msg = sprintf($msg, implode(', ',$headings));

            throw new Exception\BadRequestValidationFailureException($msg);
        }
    }

    protected function parseFirstRowAndGetHeadings(array & $rows, string $delimiter)
    {
        $this->validateHeaders($rows, $delimiter);

        return parent::parseFirstRowAndGetHeadings($rows, $delimiter);
    }

    public function shouldSendToBatchService(): bool
    {
        return true;
    }
}
