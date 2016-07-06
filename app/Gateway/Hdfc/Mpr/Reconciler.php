<?php

namespace Gateway\Hdfc\Mpr;

use Carbon\Carbon;
use EE\Exception;
use Gateway\Hdfc;
use Gateway\Hdfc\Mpr;
use Models\Card;
use Models\Transaction;
use Models\Terminal;
use Models\Payment;
use Trace;
use Trace\TraceCode;

class Reconciler
{
    public static function getPaymentOrRefundId($input)
    {
        $array = [];
        if (isset($input['merchant_trackid']))
        {
            $trackId = $input['merchant_trackid'];
        }

        if (isset($input['rec_fmt']) === false)
        {
            throw new Exception\LogicException('rec_fmt not defined');
        }

        $type = $input['rec_fmt'];

        $txnType = null;
        if ($type === 'BAT')
            $txnType = 'payment';
        else if ($type === 'CVD')
            $txnType = 'refund';

        $array['id'] = $trackId;
        $array['type'] = $txnType;

        return $array;
    }

    public function reconcile($input, $transactionId, $entities)
    {
        // Translate from hdfc mpr raw fields to the database ones
        $attributes = $this->getTranslatedAttributes($input);

        $repo = new Hdfc\Repository;
        $hdfcPayment = $repo->findByGatewayTransactionIdOrFail(
            $attributes['gateway_transaction_id']);

        if ((string) $attributes['gateway_transaction_id'] !== (string) $hdfcPayment['gateway_transaction_id'])
        {
            throw new Exception\LogicException(
                'Hdfc mpr: Gateway payment id does not match.
                Gateway payment id: ' . $attributes['gateway_transaction_id'] .
                ' Hdfc payment id: ' . $hdfcPayment['gateway_transaction_id']);
        }

        // Create Hdfc mpr record
        $mpr = new Hdfc\Mpr\Entity($attributes);

        $repo = new Mpr\Repository;
        if ($this->checkPreviousEntries($mpr, $repo) === false)
        {
            // Save to database
            $repo->saveOrFail($mpr);
        }

        // Now convert the data to the format as understood by
        // API Transaction, Card and other entities
        $apiAttributes = $this->translateAndVerifyAttributes($mpr, $entities);

        return $apiAttributes;
    }

    protected function getTranslatedAttributes($row)
    {
        // // Remove 'pay_' from beginning of payment_id
        // $row['merchant_trackid'] = substr($row['merchant_trackid'], 4);

        $attributes = array(
            'track_id'                  => $row['merchant_trackid'],
            'gateway_transaction_id'    => (int) $row['tran_id'],
            'gateway_merchant_id'       => $row['merchant_code'],
            'gateway_terminal_id'       => $row['terminal_number'],
            'card_trivia'               => $row['card_type'],
            'card_number'               => $row['card_number'],
            'card_type'                 => $row['debitcredit_type'],
            'transaction_date'          => $row['trans_date'],
            'settlement_date'           => $row['settle_date'],
            'international_amount'      => (int) ($row['intl_amt'] * 100),
            'domestic_amount'           => (int) ($row['domestic_amt'] * 100),
            'net_amount'                => (int) ($row['net_amount'] * 100),
            'gateway_net_fee'           => (int) ($row['msf'] * 100),
            'service_tax'               => (int) ($row['service_tax'] * 100),
            'education_cess'            => (int) ($row['edu_cess'] * 100),
            'rec_format'                => $row['rec_fmt'],
            'batch_number'              => $row['bat_nbr'],
            'upvalue'                   => $row['upvalue'],
            'sequence_number'           => $row['sequence_number'],
            'approve_code'              => $row['approv_code']);

        $attributes['gateway_fee'] = $attributes['international_amount'] + $attributes['domestic_amount'] - $attributes['net_amount'];

        return $attributes;
    }

    protected function translateAndVerifyAttributes($mpr, $entities)
    {
        $type = $entities['transaction']['type'];
        list($amount, $indian) = $this->getAmountAndNationality($mpr);

        $this->verifyTerminal($mpr, $entities['terminal']);

        $this->verifyTransactionTime(
            $mpr['transaction_date'],
            $entities['transaction']['created_at']);

        $this->verifySettlementDate($mpr['settlement_date']);

        $this->verifyEntityAttributes(
            $mpr,
            $entities[$type]);

        $card = [];
        if ($type === 'payment')
        {
            $card = $entities['card'];

            $this->verifyCardNumberProperties(
                    $mpr['card_number'],
                    $card);

            $card = $this->translateCardAttributes($mpr, $card);
        }

        $transaction = array(
            Transaction\Entity::GATEWAY_FEE   => $mpr['gateway_fee']);

        $apiData = array(
            'transaction'      => $transaction,
            'card'             => $card);

        return $apiData;
    }

    protected function verifyEntityAttributes($mpr, $entity)
    {
        $id = $entity['id'];

        if ($mpr['track_id'] !== $id)
        {
            throw new Exception\LogicException(
                'Hdfc mpr: Track id does not match' .
                'Mpr payment id: ' . $mpr['track_id'] . ' Entity id: ' . $id);
        }

        if ($mpr->getAmount() !== (int) $entity['amount'])
        {
            throw new Exception\LogicException(
                'Hdfc mpr: Payment amount does not match' .
                'Mpr amount: ' . $mpr->getAmount() . ' Payment amount: ' . $entity['amount']);
        }
    }

    protected function verifyTerminal($input, $terminal)
    {
        if (($terminal['gateway_merchant_id'] !== $terminal[Terminal\Entity::GATEWAY_MERCHANT_ID]) or
            ($terminal['gateway_terminal_id'] !== $terminal[Terminal\Entity::GATEWAY_TERMINAL_ID]))
        {
            throw new Exception\LogicException(
                'Hdfc mpr: gateway terminal id or gateway merchant id does not match');
        }
    }

    protected function verifyTransactionTime($txnDate, $transactionTimestamp)
    {
        $mprDate = Carbon::createFromFormat('d-M-y', $txnDate);
        $apiTimestamp = Carbon::createFromTimestampUTC($transactionTimestamp);

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
        $value = $mpr['card_type'];

        $card = [];

        $international = CardDetail::isInternational($value);

        $card[Card\Entity::INTERNATIONAL] = $international;

        if ($international === false)
        {
            $card[Card\Entity::COUNTRY] = 'IN';
        }

        if (CardDetail::isCredit($value))
        {
            $card[Card\Entity::TYPE] = Card\Type::CREDIT;
        }
        else
        {
            $card[Card\Entity::TYPE] = Card\Type::DEBIT;
        }

        $card[Card\Entity::TRIVIA] = $mpr['card_trivia'];

        return $card;
    }

    /**
     * Verifies first and last 4 digits of card number.
     * The format of card number given in hdfc mpr is
     * ????xxxxxxxx???? where each '?' denotes a digit
     * and each 'x' denotes masked digit
     * Verifies card number length
     *
     * @param   string  $number
     * @param   array   $card
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

    protected function checkPreviousEntries($curr, $repo)
    {
        $prev = $repo->find($curr->getKey());

        if ($prev === null)
        {
            return false;
        }

        $attrPrev = $prev->getAttributes();
        $attrCurr = $curr->getAttributes();

        // Timestamps are allowed to be different
        // Ignore timestamps for similarity.
        unset(
            $attrPrev['created_at'],
            $attrPrev['updated_at'],
            $attrCurr['created_at'],
            $attrCurr['updated_at']);

        $diff1 = array_diff_assoc($attrPrev, $attrCurr);
        $diff2 = array_diff_assoc($attrCurr, $attrPrev);

        $diff = false;
        $msg = '';

        if (count($diff1) > 0)
        {
            ob_start();
            print_r($diff1);
            $msg .= ob_get_clean() . PHP_EOL;
            $diff = true;
        }
        if (count($diff2) > 0)
        {
            ob_start();
            print_r($diff2);
            $msg .= ob_get_clean() . PHP_EOL;
            $diff = true;
        }

        if ($diff)
        {
            $msg = 'Entity: Hdfc curr row' . PHP_EOL . $msg;
            $msg = 'Previous hdfc mpr row do not match' . PHP_EOL . $msg;
            throw new Exception\LogicException($msg);
        }

        return true;
    }
}
