<?php


namespace RZP\Models\Batch\Processor;


use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Batch\Header;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\BankingAccount\Activation\Comment;

class S2pUsersOnboarding extends Base
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
        $input[Batch\Entity::CONFIG]['admin_id'] = $admin->getId();

        $input[Batch\Entity::CONFIG]['admin_email'] = $admin->getEmail();

        $input[Batch\Entity::CONFIG]['admin_name'] = $admin->getName();
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
