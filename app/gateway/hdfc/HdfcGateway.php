<?php

/**
 * This file implements the interactions with HDFC gateway 
 * via the api of FSF gateway (which HDFC uses) and which
 * we actually interact with.
 *
 * The transaction flow for a purchase/auth txn 
 * in few simple words goes like this:
 * 1. We send an enroll request for a card
 * 2. For certain cards (probably cc) we get a 'NOT ENROLLED' response back
 * 	  2.1. For these cards, we send auth request and complete the txn.
 * 3. For certain cards (probably dc) we get an 'ENROLLED' response back
 * 	  3.1. For these cards, we send a request to acquiring bank (hdfc)
 * 	  	   ACS where the customer enters card fields etc. and the bank
 * 	  	   redirects to a url provided by us.
 * 	  3.2. From the redirected url, we send auth request and 
 * 	  	   complete the txn.
 * 	  	   
 * Note: Refer to HDFC FSF Payment Gateway Integration 
 *       Version 4.0 pdf document
 * 
 */

namespace Gateway\HdfcGateway;

use Gateway\BaseGateway;

class HdfcGateway extends BaseGateway
{
	/**
	 * Rzp transaction id
	 * @var string
	 */
	protected $id;

	protected $model;

	/**
	 * If during the txn flow, we detect an
	 * error, or the txn fails for any reason, 
	 * then this variable is set to true.
	 * @var boolean
	 */
	protected $error = false;

	/**
	 * Fields sent in xml format to enroll
	 * @var array
	 */
	protected $fields = array(
		'id',
		'password',
		'card',
		'cvv2',
		'expyear',
		'expmonth',
		'action',
		'amt',
		'currencycode',
		'member',
		'trackid',
		'udf1',
		'udf2',
		'udf3',
		'udf4',
		'udf5');

	/**
	 * Mapping of keys from rzp to
	 * to hdfc gateway for card
	 * @var array
	 */
	protected $card_key_mappings = array(
		'name' => 'member',
		'number' => 'card',
		'expiry_month' => 'expmonth',
		'expiry_year' => 'expyear',
		'cvv' => 'cvv2');

	/**
	 * Tranportal username for hdfc gateway
	 * @var string
	 */
	protected $username = "";

	/**
	 * Tranportal password for hdfc gateway
	 * @var string
	 */
	protected $password = "";

	/**
	 * Parameters required to construct request
	 * for enrolling a card
	 * @param [type] $response [description]
	 */
	protected $enrollRequest = array(
		'url' => 'https://securepgtest.fssnet.co.in:443/pgway/servlet/MPIVerifyEnrollmentXMLServlet',
		'type' => 'enroll',
		'xml' => '',
		'header' => array('Content-Type'=>'text/xml'),
		'data' => array());

	/**
	 * Response received after sending enroll card request
	 * @var array
	 */
	protected $enrollResponse = array(
		'fields' => array(
					'error_text', 'eci', 'result', 'url', 'PAReq', 'paymentid', 'trackid'),
		'type' => 'enroll'
		'xml' => '',
		'data' => array(),
		'error' => array());

	/**
	 * The assoc array is used to constructing
	 * auth request for credit cards
	 * @var array
	 */
	protected $authNotEnrolledRequest = array(
		'url' => 'https://securepgtest.fssnet.co.in:443/pgway/servlet/TranPortalXMLServlet',
		'type' => 'auth_not_enrolled',
		'header' => array('Content-Type:text/xml'),
		'xml' => '',
		'data' => array());

	protected $authNotEnrolledResponse = array(
		'fields' =>  array(
						'result', 'amt', 'trackid', 'payid', 'ref', 'tranid', 'auth', 'avr', 'postdate'),
		'type' => 'auth_not_enrolled',
		'xml' => '',
		'data' => array(),
		'error' => array());

	/**
	 * The assoc array is used to construct auth
	 * request for debit cards
	 * @var array
	 */
	protected $authEnrolledRequest = array(
		'url' => 'https://securepgtest.fssnet.co.in:443/pgway/servlet/MPIPayerauthEnrolledticationXMLServlet',
		'type' => 'auth_enrolled',
		'header' => array('Content-Type:text/xml'),
		'xml' => '',
		'data' => array());

	protected $authEnrolledResponse = array(
		'fields' => array(
			'paymentid', 'error_text', 'result', 'ref', 'tranid', 'auth', 'avr', 'postdate'),
		'type' => 'auth_enrolled',
		'xml' => '',
		'data' => array(),
		'error' => array());

	/**
	 * For ENROLLED card cases, we submit a form to bank ACS
	 * which redirects back to this url (on our server) after
	 * the customer enter's the requisite details
	 * 
	 * @var string
	 */
	protected $callbackUrl = 'http://rzp/transactions/callback';

	protected $bankAcsResponseRules = array(
		'PaRes' => 'required',
		'MD' => 'required|numeric|digits_between:1,19');

	protected $status;

	public static function getCreds()
	{
		$creds = array(
			HdfcGatewayConfig::id, 
			HdfcGatewayConfig::password);

		return $creds;
	}

	public function process($input)
	{
		$this->id = $input['txn']['id'];

		$this->enrollCard($input);

		$error = $this->enrollResponse['error'];

		if (isset($this->enrollResponse['error']))
		{
			if ($this->enrollResponse['data']['result'] === HdfcGatewayResult::ENROLLED)
			{
				$this->status = HdfcGatewayResult::ENROLLED;
				$this->postPaymentRequestToBankACS();
			}
			else if ($response['result'] === HdfcGatewayResult::NOT_ENROLLED)
			{
				$this->status = HdfcGatewayResult::NOT_ENROLLED;
				$this->postAuthNotEnrolledRequestToBank();
			}
			else
			{
				// No error on the request side, but enroll failed with an invalid enroll code.
				// 
				// Bail out and fail transaction.
			}
		}
		else
		{
			$errorCode = HdfcGatewayErrorHandler::getInstance()->parseErrorStr($error);
		}
	}

	public function bankAcsCallback($input)
	{
		$invalid_keys = array_diff_key($input, $this->bankAcsResponseRules);

        if (count($invalid_keys) !== 0)
        {
            throw new Exceptions\InvalidKeysException($invalid_keys);
        }

        $validation = \Validator::make($input, $this->bankAcsResponseRules);

        if ($validation->fails()) 
        {
        	var_dump($validation->messages()->all());die();
            throw new \InvalidArgumentException('d');
        }

        $this->model = HdfcGatewayDal::findOrFail($input['MD']);
        $this->authEnrolledRequest['data']['paymentid'] = $input['MD'];
        $this->authEnrolledRequest['data']['PaRes'] = $input['PaRes'];

        $this->authEnrolledRequest();
        
        return array(true, $this->model->trackid);
	}

	public function authEnrolledRequest()
	{
		if ($this->model->enroll_result !== HdfcGatewayResult::ENROLLED)
		{
			throw new \InvalidArgumentException('Result not valid');
		}
		else if ($this->model->status !== 'VERES Received')
		{
			throw new \InvalidArgumentException('Status not valid');
		}

		$data = &$this->authEnrolledRequest['data'];

		list($data['id'], $data['password']) = $this->getCreds();

		$this->runRequestResponseFlow(
			$this->authEnrolledRequest,
			$this->authEnrolledResponse);

		$this->model->persistAfterDCAuth(
						$this->authEnrolledResponse['data']);
	}

	/**
	 * Sends request for enrolling the card
	 * with hdfc gateway
	 * 
	 * @param  array $input 
	 * Should contain 'txn' and 'card' arrays
	 * 
	 */
	protected function enrollCard($input)
	{
		// Fields to be sent to HDFC gateway for card-enroll
		$this->createEnrollRequestFields($input);

		$this->runRequestResponseFlow(
			$this->enrollRequest,
			$this->enrollResponse);

		$this->parseEnrollResponseEci();

		if (($this->errors) or
			(HdfcGatewayResult::isEnrollSuccess($this->enrollResponse) === false)
		{
			$error = HdfcGatewayErrorHandler::translateEnrollError($response);

			return array(false, $error);
		}

		$this->validateEnrollResponse();
	}

	protected function postAuthNotEnrolledRequestToBank()
	{
		// Only need to add zip and addr fields
		// since other fields have already been added during enroll
		$data = $this->enrollRequest['data'];

		$data['zip'] = "";

		$data['addr'] = "";

		$this->runRequestResponseFlow(
			$this->authNotEnrolledRequest,
			$this->authNotEnrolledResponse);

		$this->model->persistAfterCCAuth(
						$this->authNotEnrolledResponse['data']);
	}

	/**
	 * Generates a form and auto-submits it on load
	 * with the fields received in response
	 * from enrolling the card
	 */
	protected function postPaymentRequestToBankACS()
	{
		$enrollResponse = $this->enrollResponse;
		?>

			<!doctype html>
			<html lang="en">
			<!-- <BODY OnLoad="OnLoadEvent();"> -->
			<body>
			<form name="form1" action="<?= $enrollResponse['data']['url']; ?>" method="post">
				<input type="text" name="PaReq" value="<?= $enrollResponse['data']['PAReq'];?>">
				<br />
				<input type="text" name="MD" value="<?= $enrollResponse['data']['paymentid'];?>">
				<br />
  				<input type="text" name="TermUrl" value="<?= $this->callbackUrl ?>">
  				<br />
  				<input type="submit" >
 			</form>
 			<br>
 			Submit within 30 secs max!
			</body>
		</html>

		<?php
		exit(0);
	}

	/**
	 * Collect all fields to be sent for
	 * enrolling the card
	 * 
	 * @param  array $input 
	 * Contains the 'txn' and 'card' details
	 */
	protected function createEnrollRequestFields($input)
	{
		$txn = $input['txn'];

		$card = $input['card'];

		$data = &$this->enrollRequest['data'];

		// Collect creds
		list($data['id'], $data['password']) = static::getCreds();

		$data['trackid'] = $txn['id'];
		
		// Convert amount from integer to decimal
		$data['amt'] = $txn['amount']/100;

		// Collect udf fields
		$data['udf1'] = 'junk';

		$data['udf2'] = $txn['udf']['email'];

		$data['udf3'] = $txn['udf']['contact'];
		
		$data['udf4'] = 'junk';

		$data['udf5'] = 'junk';

		// Collect fields related to the card
		$this->mapKeys($card, $this->card_key_mappings, $data);

		//
		// Write currency code manually.
		// Later change it to something better
		// when we support multiple currencies
		// 
		$data['currencycode'] = 356;

		if ($txn['processed'] === 0)
		{
			$data['action'] = HdfcGatewayAction::PURCHASE;
		}
		else if ($txn['processed'] === 1)
		{
			$data['action'] = HdfcGatewayAction::HOLD;
		}
		else
		{
			throw new \InvalidArgumentException('process should be 0 or 1');
		}
	}

	protected function validateEnrollResponse()
	{
		$trackid = $this->enrollResponse['data']['trackid'];

		if ($trackid !== $this->id)
		{
			throw new \InvalidArgumentException('Track id do not match');
		}
	}

	protected function persistAfterEnroll()
	{
		if (isset($this->enrollResponse['error']['code']))
		{
			$this->model = HdfcGatewayDal::persistAfterEnrollError(
							$this->id,
							$this->enrollResponse['error']);
		}
		else
		{
			$this->model = HdfcGatewayDal::persistAfterEnroll(
					$this->enrollRequest['data'],
					$this->enrollResponse['data']);
		}
	}

	/**
	 * Get the fields from xml response 
	 * of the enrolling crad
	 *
	 */
	protected function parseEnrollResponseEci()
	{
		$eci = &$this->enrollResponse['data']['eci'];
		$eci = (($eci === null) or ($eci === '')) ? '7' : $eci;

		$result = &$this->enrollResponse['data']['result']
		
		if ($result === 'ENROLLED') $result = HdfcGatewayResult::ENROLLED;
		else if ($result === 'NOT ENROLLED') $result = HdfcGatewayResult::NOT_ENROLLED;
		else if ($result === 'FSS0001') $result = HdfcGatewayResult::FSS0001
		else $result = HdfcGatewayResult::UNKNOWN_ERROR_ENROLLED = 3;
	}

	protected function runRequestResponseFlow(array &$request, array &$response)
	{
		HdfcGatewayUtility::runRequestResponseFlow($request, $response);

		HdfcGatewayResponseXmlDal::saveXml($this->id, $response['xml'], $response['type']);

		if (isset($response['error']['code']))
		{
			$this->error = true;
		}
	}

	public function refund($txn)
	{
		;
	}

	public function void()
	{
		;
	}
}

