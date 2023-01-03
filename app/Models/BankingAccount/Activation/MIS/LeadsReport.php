<?php


namespace RZP\Models\BankingAccount\Activation\MIS;

use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Status;
use RZP\Models\Base\PublicCollection;

class LeadsReport extends Leads
{
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
                BankingAccount\Fetch::COUNT => 1000,
                BankingAccount\Fetch::SKIP => $skip,
            ]);

            /** @var  PublicCollection $bankingAccounts */
            $bankingAccounts = $this->repo->banking_account->fetch($input);

            foreach ($bankingAccounts as $bankingAccount)
            {
                array_push($bankingAccountsArray, $bankingAccount);
            }

            $bankingAccountIds = array_map(function ($bankingAccount) {
                return $bankingAccount['id'];
            }, $bankingAccounts->toArray());
    
            $this->updateCommentMap($bankingAccountIds, $commentsMap);
    
            $this->updateStateMap($bankingAccountIds, $sentToBankTimestampMap);

            $hasMore = count($bankingAccounts);
            $skip = $skip + 1000;

        }

        return [$bankingAccountsArray, $commentsMap, $sentToBankTimestampMap];
    }

    public function createFile(array $fileInput)
    {
        $xlsxFilePath = $this->createExcelFile($fileInput, $this->fileName, "/tmp");

        return $xlsxFilePath;
    }

    public function generateFile(array $fileInput)
    {
        $response = $this->createFile($fileInput);

        return $response;
    }

    public function generate()
    {
        $fileInput = $this->getFileInput();

        $file = $this->generateFile($fileInput);

        return $file;
    }
}
