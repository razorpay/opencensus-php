<?php

namespace RZP\Models\BankingAccountStatement\Processor;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base\PublicEntity;
use Rzp\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\BankingAccountStatement\Pool;
use RZP\Models\BankingAccountStatement\Entity;
use RZP\Models\BankingAccountStatement\Channel;
use RZP\Models\BankingAccountStatement\Details as BasDetails;

abstract class Base extends BaseCore
{
    const MOZART_NAMESPACE = 'razorpayx';

    const MOZART_ACTION    = 'account_statement';

    protected $accountNumber;

    protected $source;

    protected $version;

    protected $channel;

    /** @var BasDetails\Entity */
    protected $basDetails;

    abstract public function checkForDuplicateTransactions(array $bankTransactions,
                                                           string $channel,
                                                           string $accountNumber);

    abstract protected function sendRequestAndGetResponse(array $input);

    abstract public function getUtrForChannel(PublicEntity $basEntity);

    public function __construct(string $channel, string $accountNumber)
    {
        parent::__construct();

        $this->setChannel($channel)
             ->setAccountNumber($accountNumber);
    }

    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    public function fetchAccountStatementDetails(array $input)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_REMOTE_FETCH_SOURCE,
            [
                'input'     => $input,
                'source'    => $this->source,
                'version'   => $this->version
            ]);

        if ($this->source === Source::FETCH_API)
        {
            return $this->fetchAccountStatementDetailsViaFetchApi($input);
        }
        else
        {
            throw new Exception\LogicException(
                'Unhandled source passed to fetch account statement details',
                null,
                [
                    'source' => $this->source,
                ]);
        }
    }

    protected function fetchAccountStatementDetailsViaFetchApi(array $input)
    {
        $formattedResponse = $this->sendRequestAndGetResponse($input);

        return $formattedResponse;
    }

    protected function validateInputForMozartRequest(array $input)
    {
        $validator = $this->getValidator();

        $validator->validateInput('create', $input);
    }

    protected function getValidator()
    {
        $validator = __NAMESPACE__ . '\\' . studly_case($this->channel) . '\\' . 'Validator';

        return new $validator;
    }

    protected function getLastBankTransaction()
    {
        $isAccountTypeShared = ($this->basDetails->getAccountType() === BasDetails\AccountType::SHARED);

        switch (true)
        {
            case (($isAccountTypeShared === true) and
                  ($this->channel === Channel::RBL)):
                /** @var Pool\Rbl\Entity|null $bankTxn */
                $bankTxn = $this->repo->banking_account_statement_pool_rbl
                    ->findLatestByAccountNumber($this->getAccountNumber());

                break;

            case (($isAccountTypeShared === true) and
                  ($this->channel === Channel::ICICI)):
                /** @var Pool\Icici\Entity|null $bankTxn */
                $bankTxn = $this->repo->banking_account_statement_pool_icici
                    ->findLatestByAccountNumber($this->getAccountNumber());

                break;

            default:
                /** @var Entity|null $bankTxn */
                $bankTxn = $this->repo->banking_account_statement
                    ->findLatestByAccountNumberAndChannel($this->getAccountNumber(),
                                                          $this->channel);
        }

        return $bankTxn;
    }

    protected function alterStatementColumnsToMatch()
    {
        if ($this->basDetails->getAccountType() === BasDetails\AccountType::SHARED)
        {
            array_delete(Entity::CHANNEL, $this->statementRecordsToMatch);
        }
    }

    protected function getColumnsToFindDuplicates($bankTransaction)
    {
        $record = [
            $bankTransaction[Entity::BANK_TRANSACTION_ID],
            $bankTransaction[Entity::BANK_SERIAL_NUMBER],
            $bankTransaction[Entity::TRANSACTION_DATE],
            $bankTransaction[Entity::AMOUNT],
        ];

        if ($this->basDetails->getAccountType() === BasDetails\AccountType::DIRECT)
        {
            $record[] = $bankTransaction[Entity::CHANNEL];
        }

        $record[] = $bankTransaction[Entity::ACCOUNT_NUMBER];

        return $record;
    }

    protected function getTimestampFromDateString(string $dateStr, string $timezone = Timezone::IST): int
    {
        $date = Carbon::parse($dateStr, $timezone);

        $timestamp = $date->timestamp;

        return $timestamp;
    }

    protected function getDateTimeStringFromTimestamp(
        int $timestamp,
        string $format,
        string $timezone = Timezone::IST): string
    {
        $date = Carbon::createFromTimestamp($timestamp, $timezone)->format($format);

        return $date;
    }

    protected function setChannel(string $channel)
    {
        Channel::validate($channel);

        $this->channel = $channel;

        return $this;
    }

    protected function getChannel()
    {
        return $this->channel;
    }

    protected function setAccountNumber(string $accountNumber)
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    protected function getAccountNumber()
    {
        return $this->accountNumber;
    }

    protected function getStartOfFinancialYear($timestamp)
    {
        $currentTime = Carbon::createFromTimestamp($timestamp, Timezone::IST);
        $year = $currentTime->year;
        $month = $currentTime->month;

        if($month < Carbon::APRIL){
            $year = $year-1;
        }

        return Carbon::create($year, Carbon::APRIL , 1, 0, 0, 0, Timezone::IST);
    }

    protected function getStartOfMonth($timestamp)
    {
        $currentTime = Carbon::createFromTimestamp($timestamp, Timezone::IST);
        $year = $currentTime->year;
        $month = $currentTime->month;

        return Carbon::create($year, $month , 1, 0, 0, 0, Timezone::IST);
    }

    protected function getStartTime()
    {
        if ($this->basDetails->getAccountType() === BasDetails\AccountType::SHARED)
        {
            return $this->getStartOfMonth($this->basDetails->getCreatedAt());
        }

        return $this->getStartOfFinancialYear($this->basDetails->getCreatedAt());
    }
}
