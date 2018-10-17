<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use Carbon\Carbon;

use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Models\Merchant\Detail\Entity as E;

class Sbi extends Base
{
    const BANK_CODE   = IFSC::SBIN;
    const EXTENSION   = FileStore\Format::TXT;
    const FILE_TYPE   = FileStore\Type::SBI_EMI_FILE;
    const FILE_NAME   = 'Sbi_Emi_File';

    protected function formatDataForFile($data)
    {
        $body = '';

        $totalAmount = 0;

        $totalTransactions = 0;

        // date 6 chars + time 4 chars + 4 seq numbers
        //mmddyy
        $uniqueReferenceNum =intval(Carbon::now()->format('mdyHi') . '0000');

        foreach ($data['items'] as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $merchant = $emiPayment->merchant;

            $merchantDetail = $merchant->merchantDetail;

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
                Carbon::createFromTimestamp($emiPayment['authorized_at'])->format('dmY') .
                $this->strpad('Razor Pay', 40) .
//                $merchantDetail[E::SBI_MID] .       // TODO: fix this
                $this->strpad($merchantDetail[E::BUSINESS_NAME], 40) .
                $this->strpad('38R01105', 8) .  // TODO: fill in the TID
                $this->formatRate($rate) .
                $this->strpad('', 40) .
                $this->numpad($principalAmount, 17) .
                'F' .
                '0/1' .                               // TODO: ask priyanshu
                ' ' .                                 // TODO: ask priyanshu
                $this->numpad('0', 7) .
//                $this->getSkuId($merchantDetail[E::SBI_MID]) . // TODO: fix this
                $this->numpad('0', 17) .
                $this->numpad($this->getEmiAmount($principalAmount, $rate, $tenure), 17) .
                $this->strpad('', 108) .
                '\n';
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
