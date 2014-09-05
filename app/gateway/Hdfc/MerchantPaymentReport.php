<?php

namespace Gateway\Hdfc;

use Carbon\Carbon;
use EE\Exception;
use Gateway\Hdfc;
use Models\Card;
use Models\Ledger;
use Models\Terminal;
use Models\Transaction;
use Trace\Trace;

class MerchantPaymentReport
{
    public static function getTransactionId($input)
    {
        if (isset($input['merchant_trackid']))
        {
            return $input['merchant_trackid'];
        }

        throw new Exception\LogicException('Hdfc mpr: Transaction id not found');
    }

    public function reconcile($input, $ledgerId, $entities)
    {
        // Translate from hdfc mpr raw fields to the database ones
        $attributes = $this->getTranslatedAttributes($input);

        $repo = new Hdfc\Repository;

        $hdfcTxn = $repo->findOrFail($attributes['gateway_transaction_id']);

        // Add primary key ledger_id
        $attributes['ledger_id'] = $ledgerId;

        if ((string)$attributes['gateway_transaction_id'] !== $hdfcTxn['gateway_transaction_id'])
        {
            throw new Exception\LogicException('Hdfc mpr: Gateway transaction id does not match');
        }

        // Create Hdfc mpr record
        $mpr = new Hdfc\Mpr($attributes);

        // Save to database
        $repo->saveOrFail($mpr);

        // Now convert the data to the format as understood by
        // API Ledger, Card and other entities
        $apiAttributes = $this->translateAndVerifyAttributes($mpr, $entities);

        return $apiAttributes;
    }

    protected function getTranslatedAttributes($row)
    {
        $attributes = array(
            'transaction_id'            => $row['merchant_trackid'],
            'gateway_transaction_id'    => $row['tran_id'],
            'gateway_merchant_id'       => $row['merchant_code'],
            'gateway_terminal_id'       => $row['terminal_number'],
            'card_network'              => $row['card_type'],
            'card_number'               => $row['card_number'],
            'card_type'                 => $row['debitcredit_type'],
            'capture_date'              => $row['trans_date'],
            'settlement_date'           => $row['settle_date'],
            'international_amount'      => (int) $row['intl_amt'] * 100,
            'domestic_amount'           => (int) $row['domestic_amt'] * 100,
            'net_amount'                => (int) $row['net_amount'] * 100,
            'gateway_net_fee'           => (int) $row['msf'] * 100,
            'service_tax'               => (int) $row['service_tax'] * 100,
            'education_cess'            => (int) $row['edu_cess'] * 100,
            'reconciliation_format'     => $row['rfc_fmt'],
            'batch_number'              => $row['bat_nbr'],
            'upvalue'                   => $row['upvalue'],
            'sequence_number'           => $row['sequence_number'],
            'approve_code'              => $row['approv_code']);

        $attributes['gateway_fee'] = $attributes['international_amount'] + $attributes['domestic_amount'] - $attributes['net_amount'];

        return $attributes;
    }

    protected function translateAndVerifyAttributes($mpr, $entities)
    {
        list($amount, $indian) = $this->getAmountAndNationality($mpr);

        $this->verifyTerminal($mpr, $entities['terminal']);

        $this->verifyCaptureTime(
            $mpr['capture_date'],
            $entities['transaction']['captured_at']);

        $this->verifySettlementDate($mpr['settlement_date']);

        $this->verifyTransactionAttributes(
            $mpr,
            $entities['transaction']);

        $card = $entities['card'];

        $this->verifyCardNumberProperties(
                $mpr['card_number'],
                $card);

        $card = $this->translateCardAttributes($mpr, $card);

        if ($indian)
        {
            $card[Card\Entity::COUNTRY] = 'IN';
        }
        else
        {
            $card[Card\Entity::COUNTRY] = null;
        }

        $ledger = array(
            Ledger\Entity::GATEWAY_FEE   => $mpr['gateway_fee']);

        $apiData = array(
            'card'        => $card,
            'ledger'      => $ledger);

        return $apiData;
    }

    protected function verifyTransactionAttributes($mpr, $transaction)
    {
        $txnId = 'txn-' . $transaction[Transaction\Entity::ID];

        if ($mpr['transaction_id'] !== $txnId)
        {
            throw new Exception\LogicException(
                'Hdfc mpr: Transaction id does not match');
        }

        if ((string) $mpr->getAmount() !== $transaction['amount'])
        {
            throw new Exception\LogicException(
                'Hdfc mpr: Transaction amount does not match');
        }
    }

    protected function verifyTerminal($input, $terminal)
    {
        if (($terminal['gateway_merchant_id'] !== $terminal[Terminal\Entity::GATEWAY_MERCHANT_ID]) or
            ($terminal['gateway_terminal_id'] !== $terminal[Terminal\Entity::GATEWAY_TERMINAL_ID]))
        {
            throw new Exception\LogicException('Hdfc mpr: gateway terminal id or gateway merchant id does not match');
        }
    }

    protected function verifyCaptureTime($captureDate, $captureTimestamp)
    {
        $mprDate = Carbon::createFromFormat('d-M-y', $captureDate);
        $apiTimestamp = Carbon::createFromTimestampUTC($captureTimestamp);

        // @todo: finish this
        if (false)
        {
            throw new Exception\LogicException(
                'Hdfc mpr: Capture timestamp does not match with hdfc mpr');
        }
    }

    protected function verifySettlementDate($settlementDate)
    {
        $date = new Carbon($settlementDate);

        // @todo: finish this
        if (false)
        {
            throw new Exception\LogicException(
                'Hdfc mpr: Settlement date should of today. Provided: ' . $settlementDate);
        }
    }

    protected function translateCardAttributes($mpr, $card)
    {
        $card = array(
            Card\Entity::TYPE    => $this->translateCardType($mpr['card_type']),
            Card\Entity::NETWORK => $this->translateCardNetwork($mpr['card_network']));

        return $card;
    }

    /**
     * Verifies first and last 4 digits of card number.
     * The format of card number given in hdfc mpr is
     * ????xxxxxxxx???? where each '?' denotes a digit
     * and each 'x' denotes masked digit
     *
     * @param  string  $number
     * @param  integer $iin
     * @param  integer $last4
     */
    protected function verifyCardNumberProperties($number, $card)
    {
        $iin = $card[Card\Entity::IIN];
        $last4 = $card[Card\ENtity::LAST4];
        $len = $card[Card\ENtity::LENGTH];

        if ((substr($number, 0, 4) !== substr($iin, 0, 4)) or
            (substr($number, -4) !== $last4))
        {
            throw new Exception\LogicException(
                'Hdfc mpr: card number does not match for card id' . $card[Card\Entity::ID]);
        }

        if (strlen($number) !== (int) $len)
        {
            throw new Exception\LogicException(
                'Hdfc mpr: card number length does not match for card id ' . $card[Card\Entity::ID]);
        }
    }

    protected function translateCardNetwork($cardNetwork)
    {
        $network = null;

        $networkTranslation = array(
            'VISA LOCAL'        => Card\Network::VISA,
            'MASTERCARD LOCAL'  => Card\Network::MC,
            'RUPAY LOCAL'       => Card\Network::RUPAY,
            'MAESTRO LOCAL'     => Card\Network::MAES);

        if (in_array($cardNetwork, $networkTranslation))
        {
            $network = $networkTranslation($cardNetwork);
        }
        else
        {
            ; // trace here
        }

        return $network;
    }

    protected function translateCardType($cardType)
    {
        if ($cardType === 'DC')
        {
            return Card\Type::DEBIT;
        }
        else if ($cardType === 'CC')
        {
            return Card\Type::CREDIT;
        }
        else
        {
            throw new Exception\LogicException(
                'Unknown card type value in HDFC mpr. type: ' . $cardType);
            ; // @todo: trace!
        }
    }

    protected function getAmountAndNationality($mpr)
    {
        $indian = null;

        $da = $mpr['domestic_amount'];
        $ia = $mpr['international_amount'];

        if (($da !== 0) and ($ia !== 0))
        {
            throw new Exception\LogicException(
                'Both domestic and international amount cannot be non-zero in Hdfc mpr');
        }

        if (($da === 0) and ($ia === 0))
        {
            throw new Exception\LogicException(
                'Both domestic and international amount cannot be zero in Hdfc mpr');
        }

        if ($da !== 0)
        {
            $indian = true;
            $amount = $mpr['domestic_amount'];
        }
        else if ($mpr['international_amount'] !== 0)
        {
            $indian = false;
            $amount = $mpr['international_amount'];
        }

        return array($amount, $indian);
    }
}
