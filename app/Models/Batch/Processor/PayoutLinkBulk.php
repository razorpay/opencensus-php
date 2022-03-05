<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Header;
use RZP\Models\User\Entity as UserEntity;
use RZP\Exception\BadRequestValidationFailureException;

class PayoutLinkBulk extends Base
{
    const TOTAL_PAYOUT_LINK_AMOUNT = "total_payout_link_amount";

    protected $headers = [];

    public function addSettingsIfRequired(& $input)
    {
        if (isset($input["config"]) === true)
        {
            $config = $input["config"];

            /** @var UserEntity $user */
            $user = $this->app['basicauth']->getUser();

            $mode = isset($this->app['rzp.mode']) ? $this->app['rzp.mode'] : 'live';

            if(empty($user) === false)
            {
                $config['user_id'] = $user->getId();
            }

            if(empty($mode) === false)
            {
                $config['mode'] = $mode;
            }

            $input["config"] = $config;
        }
    }

    // validating for any duplicate headers
    protected function validateDuplicateHeaders(array $rows, $delimiter)
    {
        $actualHeaders = str_getcsv(current($rows), $delimiter);

        if(count($actualHeaders) !== count(array_unique($actualHeaders)))
        {
            throw new BadRequestValidationFailureException('Uploaded file has duplicate headers');
        }
    }

    /**
     * Adds the total payout links amount the response
     * @param array $entries
     * @return array
     */
    protected function getValidatedEntriesStatsAndPreview(array $entries): array
    {
        $response = parent::getValidatedEntriesStatsAndPreview($entries);

        // Multiplying by 100 since amount is in rupees and FE is expecting in paise
        $totalPayoutLinksAmount = (int) (array_sum(array_column($entries, Header::PAYOUT_LINK_BULK_AMOUNT)) * 100);

        $response += [self::TOTAL_PAYOUT_LINK_AMOUNT => $totalPayoutLinksAmount];

        return $response;
    }

    protected function parseFirstRowAndGetHeadings(array & $rows, string $delimiter)
    {
        $this->validateDuplicateHeaders($rows, $delimiter);

        $headers = str_getcsv(current($rows), $delimiter);

        return $headers;
    }

}
