<?php 

namespace Models\Service\Transaction;

use Models\Manager\Transaction;
use Models\Manager\TransactionStatus;
use Models\DAL;
use Gateway\GatewayManager;
use Exceptions;

class Base
{
	protected $txn;

	protected $card;

	/**
     * Creates an entry for a new transaction.
     */
    public function create($input = null)
    {
    	$card_token = null;

        $txn_input = $input;
        
    	if (! array_key_exists('card', $input))
    	{
    		throw new \Exceptions\InvalidArgumentException('Card not provided');
    	}

    	$card_input = $input['card'];

    	$card_data = Manager\Card::createValidate($card_input)->getData();

    	unset($txn_input['card']);

    	$data = Manager\Transaction::createValidate($txn_input)->getData();

        $txn = DAL\Transaction::createOrFail($data);

        $txn = $this->txn->process($txn, $card_data);

        return array($txn, $card_data);
    }

    /**
     * Processes a transaction.
     */
    public function process($txn, $card)
    {
        if (! ($txn instanceof DAL\Transaction))
        {
        	throw new Exceptions\InvalidArgumentException('Invalid transaction id');
        }

        $this->txn = $txn;

        //
        // Call gateway with required info
        //
    	$txnInfo = array(
    				'txn' => $txn->toArray(),
    				'card' => $card);

        $gateway = new GatewayManager();

        list($status, $data) = $gateway->process($txnInfo);

        switch ($status)
        {
        	//@todo: Update data on hold
			case TransactionStatus::HOLD:
			$this->updateTransactionHold();
			break;

			//@todo: Update data on captured
			case TransactionStatus::CAPTURED:
			$this->updateTransactionCaptured();
			break;

			//@todo: Fill errors on failure
			case TrnsacationStatus::FAILED:
			$error = $this->fillErrorDetails($data, $txn);
			$this->updateTransactionFailed();
			break;

			default:
			throw new \Exceptions\LogicException($status . ' is an invalid status');
        }

        return $txn;
    }

    /**
     * Capture a preivous auth transaction
     * 
     * @param  [type] $txn [description]
     * @return [type]      [description]
     */
    public function capture($txn)
    {
    	;
    }

    protected function updateTransactionHold()
    {
    	$this->txn->updateStatus(TransactionStatus::HOLD);
    }

    protected function updateTransactionCaptured()
    {
    	$this->txn->updateStatus(TransactionStatus::CAPTURED);
    }

    protected function updateTransactionFailed($error)
    {
    	$this->txn->failed();
    }

    protected function fillErrorDetails($error, $txn)
    {
    	
    }
}
