<?php

namespace RZP\Models\Emi\Banks\Sbin;

use Carbon\Carbon;

use RZP\Models\FileStore;
use RZP\Models\Emi\Banks\Base;
use RZP\Models\Merchant\Detail\Entity as E;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = '';

    protected $emailIdsToSendTo = ['sbicards.emi@razorpay.com'];

    protected $bankName = 'Sbi';

    protected $type = FileStore\Type::SBI_EMI_FILE_SFTP;

    protected $totalAmount;

    protected $totalTransactions;

    public function __construct()
    {
        parent::__construct();

        $this->transferMode = Base\EmiMode::SFTP;
    }

    protected function getEmiData($input)
    {
        $body = '';

        $totalAmount = 0;

        $totalTransactions = 0;

        // date 6 chars + time 4 chars + 4 seq numbers
        //mmddyy
        $uniqueReferenceNum =intval(Carbon::now()->format('mdyHi') . '0000');

        foreach ($input as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $merchant = $emiPayment->merchant;

            $merchantDetail = $merchant->merchantDetail->toArray();

            $totalTransactions++;

            $uniqueReferenceNum++;

            $principalAmount = $emiPayment->getAmount();

            $totalAmount = $totalAmount + $principalAmount;

            $rate = $emiPlan->getRate() / 100;

            $tenure = $emiPlan->getDuration();

            $body = $body .
                'DD' .    // record type always DD
                'R' . strval($uniqueReferenceNum) .
                $this->strpad('Razor Pay', 40) .
                $this->numpad($this->getCardNumber($emiPayment->card), 19) .
                $this->numpad($principalAmount, 17) .
                $this->numpad($tenure, 3) .
                $this->strpad($this->getAuthCode($emiPayment), 6) .
                Carbon::createFromTimestamp($emiPayment->getAuthorisedAt())->format('dmY') .
                $this->strpad('Razor Pay', 40) .
                $merchantDetail[E::SBI_MID] .       // TODO: fix this
                $this->strpad($merchantDetail[E::BUSINESS_NAME], 40) .
                $this->strpad('TERMINAL TID', 8) .  // TODO: fill in the TID
                $this->formatRate($rate) .
                $this->strpad('', 40) .
                $this->numpad($principalAmount, 17) .
                'F' .
                '0/1' .                               // TODO: ask priyanshu
                ' ' .                                 // TODO: ask priyanshu
                $this->numpad('0', 7) .
                $this->getSkuId($merchantDetail[E::SBI_MID]) .
                $this->numpad('0', 17) .
                $this->numpad($this->getEmiAmount($principalAmount, $rate, $tenure), 17) .
                $this->strpad('', 108) .
                '\n';

            $this->totalTransactions = $totalTransactions;

            $this->totalAmount = $totalAmount;
        }

        $header =
            'HH' .
            Carbon::now()->format('dmY') .
            Carbon::now()->format('HiS') .
            $this->numpad($totalTransactions, 5) .
            $this->numpad($totalAmount, 17) .
            'F' .
            $this->strpad('', 411) .
            '\n';

        $data = $header . $body;

        return $data;
    }

    protected function generateEmiFile(array $emiData, array $metadata = [])
    {
        $fileData = null;

        // for sftp file is uploaded to
        if ($this->transferMode === Base\EmiMode::SFTP)
        {
            $metadata = $this->getH2HMetadata();
        }
        else
        {
            $this->type = FileStore\Type::ICICI_EMI_FILE_MAIL;
        }

        $fileData = parent::generateEmiFile($emiData, $metadata);

        return $fileData;
    }

    // TODO:  fix this
    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10004',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }

    protected function getFileToWriteName(array $data)
    {
        $count = $this->totalTransactions;

        $date = Carbon::now(Timezone::IST)->format('dmY');

        static::$fileToWriteName = 'Razorpay_SBIEMI_' . $date . '_' . $count;

        $filePath = '';

        // for sftp we put the file in a H2H path
        if ($this->transferMode === Base\EmiMode::SFTP)
        {
            $filePath = 'sbin/outgoing/';
        }

        return $filePath . static::$fileToWriteName;
    }

    private function getSkuId($mid)
    {
        return $this->strpad('GG0001' . substr($mid, -4), 20);
    }

    private function numpad($num, $count)
    {
        return strtoupper(str_pad($num, $count, '0', STR_PAD_LEFT));
    }

    private function strpad($str, $length)
    {
        return strtoupper(str_pad($str, $length, ' ', STR_PAD_RIGHT));
    }

    private function formatRate($rate)
    {
        if ($rate < 10)
        {
            $rate = '0' . $rate;
        }

        return str_pad($rate, 7, '0', STR_PAD_RIGHT);
    }
}
