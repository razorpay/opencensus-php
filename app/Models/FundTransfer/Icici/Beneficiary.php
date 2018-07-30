<?php

namespace RZP\Models\FundTransfer\Icici;

use Mail;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Mail\Base\Constants;
use RZP\Services\BeamClient;
use RZP\Models\Settlement\Channel;
use RZP\Models\Base\PublicCollection;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Mail\Banking\BeneficiaryFile as BeneficiaryFileMail;
use RZP\Models\FundTransfer\Base\Beneficiary\FileProcessor;

class Beneficiary extends FileProcessor
{
    const BEAM_JOB_NAME = 'icici_settlement_beneficiary';

    protected $id;

    protected $channel = Channel::ICICI;

    public function __construct()
    {
        parent::__construct();

        $this->id = Base\UniqueIdEntity::generateUniqueId();
    }

    /**
     * @param $bankAccounts
     * @param array $input
     *
     * @return array
     * @return array with keys 'signed_url'
     *                         'local_file_path'
     *                         'file_name'
     *                         'merchants_count'
     */
    public function register(PublicCollection $bankAccounts, array $input = []): array
    {
        $rows = $this->getData($bankAccounts);

        $txt = $this->getTxt($rows);

        $file = $this->generateFile($txt);

        $merchantCount = count($rows);

        $response = $this->makeResponse($file, $merchantCount);

        $recipientEmails = $input[BankAccount::RECIPIENT_EMAILS] ?? null;

        $mailData = array_merge($response, [BankAccount::RECIPIENT_EMAILS => $recipientEmails]);

        $this->sendEmail($mailData);

        //
        // Pushing to Beam after sending the email
        // such that current beneficiary processing
        // doesn't get affected by Beam errors.
        //
        $this->sendFile($file);

        return $response;
    }

    protected function getData(PublicCollection $bankAccounts): array
    {
        $rows = [];

        foreach ($bankAccounts as $ba)
        {
            $address = $ba->source->merchantDetail->getBusinessRegisteredAddress();

            // Removes line break from the string
            $address = $this->normalizeString($address, 30, '');

            $row = [
                'A',
                $ba->getId(),
                $ba->getBeneficiaryName(),
                $ba->getAccountNumber(),
                'vendor',
                $address,
            ];

            $rows[] = $row;
        }

        return $rows;
    }

    protected function getTxt(array $rows): string
    {
        $txt = '';

        $totalElements = count($rows);

        foreach ($rows as $index => $row)
        {
            $txt .= implode('|', $row);

            if ($index < $totalElements - 1)
            {
                //
                // Double quote is required to suggest new line
                // Single quote will NOT work
                //
                $txt .= "\r\n";
            }
        }

        return $txt;
    }

    protected function generateFile($txt): FileStore\Creator
    {
        $fileName = 'icici/outgoing/NRPSS_NRPSSBENEUPLD_' . $this->id;

        if ($this->env === 'beta')
        {
            $fileName = 'icici/outgoing/TEST_BENEUPLD_' . $this->id;
        }

        $metadata = $this->getH2HMetadata();

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::TXT)
                        ->content($txt)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::FUND_TRANSFER_H2H)
                        ->id($this->id)
                        ->metadata($metadata)
                        ->save();

        return $file;
    }

    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }

    protected function sendEmail(array $data)
    {
        $beneficiaryFileMail = new BeneficiaryFileMail($data, $this->channel, $data['merchants_count']);

        Mail::queue($beneficiaryFileMail);
    }

    /**
     * @param FileStore\Creator $file
     * Send file to bank through Beam
     */
    protected function sendFile(FileStore\Creator $file)
    {
        $data =  [
            BeamClient::BEAM_PUSH_FILES   => [$file->getFullFileName()],
            BeamClient::BEAM_PUSH_JOBNAME => self::BEAM_JOB_NAME
        ];

        $mailInfo   = $this->getBeamMailInfo($file);

        // In seconds
        $timelines = [15, 28, 56, 112, 225, 450, 900, 1800, 3600, 2*3600];

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);
    }

    /**
     * Set beam mail data
     * @param FileStore\Creator $file
     * @return array
     */
    protected function getBeamMailInfo(FileStore\Creator $file): array
    {
        $recipient = Constants::MAIL_ADDRESSES[Constants::SETTLEMENT_ALERTS];

        $subject   = 'Beneficiary file failure';

        $fileParam = explode('/', $file->getFullFileName());

        $body      = 'Hi,\n Beneficiary file send failed through Beam.\n'.
                     'Channel  :: ' . $this->channel . '\n'.
                     'Filename :: ' . $fileParam[count($fileParam) - 1] . '\n';

        return [
            'recipient' => $recipient,
            'subject'   => $subject,
            'body'      => $body
        ];
    }
}
