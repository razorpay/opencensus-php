<?php

namespace RZP\Services\FTS;

use App;
use Requests;

class FtsAdminClient extends Base
{

    const TRANSFERS          = 'transfers';

    const FUND_ACCOUNTS      = 'fund_accounts';

    const BENEFICIARY_STATUS = 'beneficiary_status';

    const ATTEMPTS           = 'attempts';

    public function __construct()
    {
        $app = App::getFacadeRoot();

        parent::__construct($app);

        $this->setAdminHeader();
    }

    public function fetchMultiple(string $entity, array $input)
    {
        switch ($entity)
        {
            case self::TRANSFERS:
                return $this->getTransfers($input);

            case self::ATTEMPTS:
                return $this->getAttempts($input);

            case self::FUND_ACCOUNTS:
                return $this->getFundAccounts($input);

            case self::BENEFICIARY_STATUS:
                return $this->getBeneficiaryStatus($input);
        }
    }

    public function getTransfers(array $input)
    {
        return $this->createAndSendRequest(
            parent::FUND_TRANSFER_FETCH_URI,
            Requests::GET,
            $input)['body'][self::TRANSFERS];
    }

    public function getFundAccounts(array $input)
    {
        return $this->createAndSendRequest(
            parent::FUND_ACCOUNT_FETCH_URI,
            Requests::GET,
            $input)['body'][self::FUND_ACCOUNTS];
    }

    public function getBeneficiaryStatus(array $input)
    {
        return $this->createAndSendRequest(
            parent::FUND_ACCOUNT_STATUS_FETCH_URI,
            Requests::GET,
            $input)['body'][self::BENEFICIARY_STATUS];
    }

    public function getAttempts(array $input)
    {
        return $this->createAndSendRequest(
            parent::FUND_TRANSFER_STATUS_FETCH_URI,
            Requests::GET,
            $input)['body'][self::ATTEMPTS];
    }

    public function fetch(string $entity, string $id, array $input)
    {
        $input += [ 'id' => $id ];

        switch ($entity)
        {
            case self::TRANSFERS:
                return $this->getTransfers($input)[0];

            case self::ATTEMPTS:
                return $this->getAttempts($input)[0];

            case self::FUND_ACCOUNTS:
                return $this->getFundAccounts($input)[0];

            case self::BENEFICIARY_STATUS:
                return $this->getBeneficiaryStatus($input)[0];
        }
    }
}
