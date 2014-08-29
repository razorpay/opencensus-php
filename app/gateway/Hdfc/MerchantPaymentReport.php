<?php

namespace Gateway\Hdfc;

use EE\Exception;
use Gateway\Hdfc;
use Models\Card;
use Models\Ledger;
use Models\Terminal;
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

    public function reconcile($input, $ledgerId, $entitiesArray)
    {
        // Translate from hdfc mpr raw fields to the database ones
        $attributes = $this->getTranslatedAttributes($input);

        $repo = new HdfcRepository;

        $hdfcTxn = $repo->findOrFail($attributes['gateway_transaction_id']);

        // Add primary key ledger_id
        $attributes['ledger_id'] = $ledger_id;

        if ($attributes['gateway_transaction_id'] !== $hdfcTxn['transactionid'])
        {
            throw new Exception\LogicException('Hdfc mpr: Gateway transaction id does not match');
        }

        // Create Hdfc mpr record
        $mpr = new Hdfc\Mpr($attributes);

        // Save to database
        $repo->saveOrFail($mpr);

        // Now convert the data to the format as understood by
        // API Ledger, Card and other entities
        $apiAttributes = $this->translateAndVerifyAttributes($attributes);

        return $apiAttributes;
    }

    protected function getTranslatedAttributes($input)
    {
        $attributes = array(
            'transaction_id'            => $row['merchant_trackid'],
            'gateway_transaction_id'    => $row['tran id'],
            'gateway_merchant_id'       => $row['merchant code'],
            'gateway_terminal_id'       => $row['terminal number'],
            'card_network'              => $row['card type'],
            'card_number'               => $row['card number'],
            'card_type'                 => $row['debitcredit_type'],
            'capture_date'              => $row['trans date'],
            'settlement_date'           => $row['settle date'],
            'international_amount'      => $row['intl_amt'] * 100,
            'domestic_amount'           => $row['domestic amt'] * 100,
            'net_amount'                => $row['net amount'] * 100,
            'gateway_net_fee'           => $row['msf'] * 100,
            'service_tax'               => $row['service tax'] * 100,
            'education_cess'            => $row['edu cess'] * 100,
            'reconciliation_format'     => $row['rfc fmt'],
            'batch_number'              => $row['bat nbr'],
            'upvalue'                   => $row['upvalue'],
            'sequence_number'           => $row['sequence number'],
            'approve_code'              => $row['approv code']);

        $attributes['gateway_fee'] = $attributes['amount'] - $attributes['net_amount'];

        return $attributes;
    }

    protected function translateAndVerifyAttributes($attributes, $entitiesArray)
    {
        list($amount, $indian) = $this->getAmountAndNationality($attributes);

        $this->verifyTerminal($input, $entititesArray['terminal']);

        $this->verifyCaptureTime(
            $attributes['capture_date'],
            $entitiesArray['transaction']['captured_at']);

        $this->verifySettlementDate($input['settlement_date']);

        $this->verifyTransactionAttributes(
            $attributes,
            $entitiesArray['transaction']);

        $card = $entitiesArray['card'];
        $this->verifyCardFirstAndLast4(
                $attributes['card_number'],
                $card[Card\Entity::IIN],
                $card[Card\Entity::LAST4]);

        $card = $this->translateCardAttributes($attributes, $card);

        if ($indian)
        {
            $card[Card\Entity::COUNTRY] = 'IN';
        }
        else
        {
            $card[Card\Entity::COUNTRY] = null;
        }

        $ledger = array(
            Ledger\Entity::GATEWAY_FEE   => $attributes['gateway_fee']);

        $apiData = array(
            'card'        => $card,
            'ledger'      => $ledger);

        return $apiData;
    }

    protected function verifyTransactionAttributes($attributes, $transaction)
    {
        $txnId = $transaction[Transaction\Entity::ID];

        if ($attributes['transaction_id'] !== $txnId)
        {
            throw new Exception\LogicException(
                'Hdfc mpr: Transaction id does not match');
        }

        if ($attributes['amount'] !== $transaction['amount'])
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
        $mprDate = new Carbon($captureDate);
        $apiTimestamp = new Carbon($captureTimestamp);

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

    protected function translateCardAttributes($attributes, $card)
    {
        $card = array(
            Card\Entity::TYPE    => $this->translateCardType($data['card_type']),
            Card\Entity::NETWORK => $this->translateCardNetwork($data['card_network']));

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
    protected function verifyCardFirstAndLast4($number, $iin, $last4)
    {
        if ((substr($number, 0, 4) !== substr($iin, 0, 4)) or
            (substr($number, -4) !== $last4))
        {
            throw new Exception\LogicException('Hdfc mpr: card number does not match');
        }
    }

    protected function translateCardNetwork($cardNetwork)
    {
        $network = null;

        $networkTranslation = array(
            'VISA LOCAL'        => Card\Network::VISA,
            'MASTERCARD LOCAL'  => Card\Network::MC,
            'RUPAY LOCAL'       => Card\Network::RUPAY,
            'MAESTRO LOCAL'     => Card\Network::MAESTRO);

        if (in_array($networkTranslation, $cardNetwork))
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

    protected function getAmountAndNationality($attributes)
    {
        $indian = null;

        $da = $attributes['domestic_amount'];
        $ia = $attributes['international_amount'];

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
            $amount = $attributes['domestic_amount'];
        }
        else if ($attributes['international_amount'] !== 0)
        {
            $indian = false;
            $amount = $attributes['international_amount'];
        }

        return array($amount, $india);
    }
}
