<?php


namespace RZP\Models\BankingAccount\Activation\MIS;


use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Admin\Admin;
use RZP\Models\Base\PublicCollection;
use RZP\Models\BankingAccount\Activation\Comment;
use RZP\Models\BankingAccount\Activation\Detail;
use RZP\Models\BankingAccount;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class ExternalComments extends Base
{
    // Headers
    const RZP_REF_NO = 'RZP Ref No';
    const CUSTOMER_NAME = 'Customer Name';
    const COMMENTS = 'Comments';
    const SPOC_NAME = 'Sales POC Name';
    const SPOC_NUMBER = 'Sales POC Number';

    public function __construct(array $input)
    {
        $timestamp = Carbon::createFromTimestamp(time(), Timezone::IST)->format('Y-m-d--H-i');

        $this->fileName = "CA-Comments-For-Bank-MIS-" . $timestamp;

        $this->fileType = 'banking_account_comments_for_bank';

        parent::__construct($input);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_LEADS_MIS_REQUEST,
            [
                'file_name' => $this->fileName,
                'type'      => $this->fileType,
                'input'     => $input
            ]);
    }

    protected function groupCommentsByBankingAccount(PublicCollection $comments)
    {
        $bankingAccountToCommentsMap = [];

        /** @var Comment\Entity $comment */
        foreach ($comments as $comment)
        {
            $ba = $comment->bankingAccount;

            $baKey = $ba->getId();

            if (isset($bankingAccountToCommentsMap[$baKey]) === false)
            {
                $bankingAccountToCommentsMap[$baKey] = [
                    'entity' => $ba,
                    'comments' => []
                ];
            }

            array_push($bankingAccountToCommentsMap[$baKey]['comments'], $comment);
        }

        return $bankingAccountToCommentsMap;
    }

    protected function formatCommentsString(array $comments)
    {
        $commentsStr = "";

        /** @var Comment\Entity $comment */
        foreach ($comments as $comment)
        {
            $commentStr = "[" . epoch_format($comment->getCreatedAt(), 'M d, Y') . "] " . strip_tags($comment->getComment());

            $commentsStr = $commentsStr . $commentStr . "\n";
        }

        return $commentsStr;
    }

    public function getFileInput()
    {
        $bankingAccountComments = $this->repo->banking_account_comment->fetchExternalComments();

        $bankingAccountInfoMap = $this->groupCommentsByBankingAccount($bankingAccountComments);

        $fileInput = [];

        /** @var BankingAccount\Entity $ba */
        foreach ($bankingAccountInfoMap as $baId => $baInfo)
        {
            $commentsString = $this->formatCommentsString($baInfo['comments']);
            $bankingAccount = $baInfo['entity'];

            $fileInput[] = [
                self::RZP_REF_NO    => $bankingAccount->getBankReferenceNumber(),
                self::CUSTOMER_NAME => $bankingAccount->merchant[Merchant\Entity::NAME],
                self::COMMENTS      => $commentsString,
                self::SPOC_NAME     => $bankingAccount->spocs()->first()[Admin\Entity::NAME],
                self::SPOC_NUMBER   => $bankingAccount->bankingAccountActivationDetails[Detail\Entity::SALES_POC_PHONE_NUMBER],
            ];
        }
        return $fileInput;
    }
}
