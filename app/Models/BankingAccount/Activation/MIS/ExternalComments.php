<?php


namespace RZP\Models\BankingAccount\Activation\MIS;


use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Models\BankingAccount\Activation\Comment;
use RZP\Models\BankingAccount;
use RZP\Trace\TraceCode;

class ExternalComments extends Base
{
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
        $bankingAccountComments = $this->repo->banking_account_comment->fetchExternalCommentsAssignedToTeam('bank');

        $bankingAccountinfoMap = $this->groupCommentsByBankingAccount($bankingAccountComments);

        $fileInput = [];

        /** @var BankingAccount\Entity $ba */
        foreach ($bankingAccountinfoMap as $baId => $baInfo)
        {
            $commentsString =  $this->formatCommentsString($baInfo['comments']);

            $fileInput[] = [
                'RZP Ref No' => $baInfo['entity']->getBankReferenceNumber(),
                'Comments'   => $commentsString
            ];
        }
        return $fileInput;
    }
}
