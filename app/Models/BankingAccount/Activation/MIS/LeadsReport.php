<?php


namespace RZP\Models\BankingAccount\Activation\MIS;

use RZP\Base\ConnectionType;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Status;
use RZP\Models\Base\PublicCollection;

class LeadsReport extends Leads
{
    const BATCH_SIZE = 150;

    // Used as count param while fetching RBL leads data from BAS
    protected $basBatchSize = 150;

    // Total number of leads to fetch from BAS
    // To be overridden by report, set null for no limit
    protected $basCountLimit = null;

    // Total number of leads in MIS file
    // To be overridden by report, set null for no limit
    protected $totalCountLimit = null;

    protected function getData(): array
    {
        /** ============== PREPARE DATA ================ */

        $bankingAccountsArray = [];
        $commentsMap = [];
        $sentToBankTimestampMap = [];

        $skip = 0;
        $hasMore = true;

        while ($hasMore)
        {
            $input = array_merge($this->input, [
                BankingAccount\Entity::STATUS => [
                    Status::PICKED,
                    Status::INITIATED,
                    Status::VERIFICATION_CALL,
                    Status::DOC_COLLECTION,
                    Status::ACCOUNT_OPENING,
                    Status::API_ONBOARDING,
                    Status::ACCOUNT_ACTIVATION,
                    Status::ACTIVATED,
                    Status::ARCHIVED,
                ],
                BankingAccount\Entity::CHANNEL => BankingAccount\Channel::RBL,
                BankingAccount\Entity::ACCOUNT_TYPE => BankingAccount\AccountType::CURRENT,
                BankingAccount\Fetch::COUNT => self::BATCH_SIZE,
                BankingAccount\Fetch::SKIP => $skip,
            ]);

            /** @var  PublicCollection $bankingAccounts */
            $bankingAccounts = $this->repo->banking_account->fetch($input, null, ConnectionType::SLAVE);

            foreach ($bankingAccounts as $bankingAccount)
            {
                array_push($bankingAccountsArray, $bankingAccount);
            }

            $bankingAccountIds = array_map(function ($bankingAccount) {
                return $bankingAccount['id'];
            }, $bankingAccounts->toArray());

            $this->updateCommentMap($bankingAccountIds, $commentsMap);

            $this->updateStateMap($bankingAccountIds, $sentToBankTimestampMap);

            $hasMore = count($bankingAccounts) == self::BATCH_SIZE;
            $skip = $skip + self::BATCH_SIZE;

        }

        return [$bankingAccountsArray, $commentsMap, $sentToBankTimestampMap];
    }

    public function createFile(array $fileInput)
    {
        $xlsxFilePath = $this->createExcelFile($fileInput, $this->fileName, "/tmp/");

        return [$xlsxFilePath, $this->uploadTemporaryFileToStore($xlsxFilePath)];
    }

    public function generateFile(array $fileInput)
    {
        [$xlsxFilePath, $response] = $this->createFile($fileInput);

        $ufhService = $this->app['ufh.service'];

        $signedUrlResponse = $ufhService->getSignedUrl($response['file_id']);

        return [$xlsxFilePath, $signedUrlResponse];
    }

    public function generate()
    {
        $fileInput = $this->getFileInput();

        return $this->generateFile($fileInput);
    }
}
