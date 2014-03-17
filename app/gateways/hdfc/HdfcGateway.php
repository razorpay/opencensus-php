<?php

/**
 * This file implements the interactions with HDFC gateway 
 * via the api of FSF gateway (which HDFC uses) and which
 * we actually interact with.
 *
 * The transaction flow for a purchase txn 
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
 */

namespace Gateway\HdfcGateway;

class HdfcGateway extends Gateway\BaseGateway
{

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
		'name' => 'member'
		'number' => 'card',
		'expiry_month' => 'expmonth',
		'expiry_year' => 'expyear',
		'cvv' => 'cvv2');

	/**
	 * Mapping of keys from rzp to hdfc
	 * gateway for transaction
	 * @var array
	 */
	protected $txn_key_mappings = array(
		'amount' => 'amt');

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
		'url' => 'https://securepgtest.fssnet.co.in/pgway/servlet/MPIVerifyEnrollmentXMLServlet:443',
		'xml' => '',
		'header' => array('Content-Type:text/xml'),
		'data' => array());

	/**
	 * Response received after sending enroll card request
	 * @var array
	 */
	protected $enrollResponse = array();

	/**
	 * The assoc array is used to constructing
	 * auth request for credit cards
	 * @var array
	 */
	protected $authCCRequest = array(
		'url' => 'https://securepgtest.fssnet.co.in/pgway/servlet/TranPortalXMLServlet',
		'header' => array('Content-Type:text/xml'),
		'xml' => '',
		'data' => array());

	/**
	 * The assoc array is used to construct auth
	 * request for debit cards
	 * @var array
	 */
	protected $authDCRequest = array(
		'url' => 'https://securepgtest.fssnet.co.in/pgway/servlet/MPIPayerAuthenticationXMLServlet',
		'header' => array('Content-Type:text/xml'),
		'xml' => '',
		'data' => array());

	protected $status;

	public function __construct()
	{
		parent::__construct();
	}


	public function process($txn, $card)
	{
		$response = $this->enrollCard($input);

		$this->txn = $txn;

		$this->card = $card;

		$this->mapKeys($txn, $txn_key_mappings);

		$this->mapKeys($card, $card_key_mappings);

		$this->runGenerators();
	}

	public function authDCRequest($input)
	{
		$authDCRequest['data']
		$data['paymentid'] = isset($input['MD']) ? $input['MD'] : '';
		$data['PARes'] = isset($input['PaRes']) ? $input['PaRes'] : '';
		$data['id'] = 'fill id here';
		$data['password'] = 'fill password here';

		$xml = $this->createXml($data);

		$response = Requests::post(
						$authDCRequest['url'],
						$authDCRequest['header'],
						$authDCRequest['xml']);


		$this->authDCResponse['xml'] = $response->body;

		$this->parseDCAuthResponseXml($authDCResponse['xml']);

		$fields = array('error_text', 'result', 'trackid', 'paymentid', 'ref', 'tranid', 'auth', 'avr', 'postdate');

		$this->getFieldsFromXML($xml, $fields, $this->enrollResponse);
	}

	protected function enrollCard($input)
	{
		$this->txn = $txn;

		$this->card = $card;

		// Fields to be sent to HDFC gateway for card-enroll
		$this->createFieldsForEnroll();

		// XML generated from the fields
		$this->enrollRequest['xml'] = $this->createXml($this->data);

		// send the request and get response
		$this->enrollResponse['xml'] = $this->postRequestForEnroll();

		$this->parseEnrollResponseXml($this->enrollResponse['xml']);

		$response = $this->enrollResponse;

		$error = $response['error_text'];

		if ($error === null)
		{
			if ($response('result') === 'ENROLLED')
			{
				$this->status = 'ENROLLED';
				$this->postPaymentRequestToBankACS();
			}
			else if ($response['result'] === 'NOT ENROLLED')
			{
				$this->status = 'NOT ENROLLED';
				$this->postAuthCCRequestToBank()
			}
		}
		else
		{
			// if error is not null, then transaction failed.
			// @todo: see if there are standard error values in fsf doc
		}
	}

	protected function postAuthCCRequestToBank()
	{
		// Only need to add zip and addr fields
		// since other fields have already been added during enroll
		$data = $this->enrollRequest['data'];

		$data['zip'] = "";

		$data['addr'] = "";

		$authRequest['xml'] = $this->createXml($data);

		$response = Requests::post(
						$authCCRequest['url'],
						$authCCRequest['header'],
						$authCCRequest['xml']);

		$this->authCCResponse['xml'] = $response->body;

		$this->parseCCAuthResponseXml($authCCResponse['xml']);

		$this->getFieldsFromXML($xml, $fields, $this->authResponse);
	}

	protected function postPaymentRequestToBankACS()
	{
		$enrollResponse = $this->enrollResponse;

		// $data = array(
		// 	'PaReq' => $enrollResponse['PAReq'],
		// 	'MD' => $enrollResponse['paymentid'],
		// 	'TermUrl' => $this->payResponseUrl);

		// $response = Requests::post(
		// 				$enrollResponse['url'],
		// 				array(),
		// 				$data);

		?>

		<HTML>
			<BODY OnLoad="OnLoadEvent();">
			<form name="form1" action="<?= $enrollResponse['url']; ?>" method="post">
				<input type="hidden" name="PaReq" value="<?= $enrollResponse['PAReq'];?>">
				<input type="hidden" name="MD" value="<?= $enrollResponse['paymentid'];?>">
  				<input type="hidden" name="TermUrl" value="<?= $this->calllbackUrl ?>">
 			</form>
		    <script language="JavaScript">
		        function OnLoadEvent() 
			      {
			        document.form1.submit();
			      }

			</script>
			</BODY>
		</HTML>

		<?php

	}

	protected function createFieldsForEnroll()
	{
		$data = &($this->enrollRequest['data']);

		$data['trackid'] = $txn['id'];

		$data['udf2'] = $txn['udf']['email'];

		$data['udf3'] = $txn['udf']['contact'];
		
		$this->mapKeys($txn, $txn_key_mappings, $data);

		$this->mapKeys($card, $card_key_mappings, $data);

		$data['currencycode'] = 356;

		$data['action'] = ($this->txn['process'] === 1) 1 : 4;
	}

	protected function postRequestForEnroll()
	{
		$enrollRequest = $this->enrollRequest;

		$response = Requests::post(
						$enrollRequest['url'],
						$enrollRequest['header'],
						$enrollRequest['xml']);


		return $response->body;
	}

	protected function parseEnrollResponseXml($xml)
	{
		$fields = array('error_text', 'eci', 'result', 'url', 'PAReq', 'paymentid')
		
		$this->getFieldsFromXML($xml, $fields, $this->enrollResponse);

		$eci = $this->enrollResponse['eci'];
		$eci = ($eci === null) '7' : $eci;
		$this->enrollResponse['eci'] = $eci;
	}

	protected function parseCCAuthResponseXml($xml)
	{
		$fields = array('result', 'amt', 'trackid', 'payid', 'ref', 'tranid', 'auth', 'avr', 'postdate');
		// Can also get udf fields in above array

		$this->getFieldsFromXML($xml, $fields, $this->authResponse);
	}

	protected function createXml($array)
	{
		$xml = "";

		foreach ($this->data as $key => $value)
		{
			$xml .= "<$key>$value</$key>"
		}

		return $xml;
	}

	public function refund($txn)
	{
		;
	}

	public function void()
	{
		;
	}

	protected function getFieldsFromXML($xml, $fields, &$array)
	{
		foreach ($fields as $field)
		{
			$array[$field] = GetTextBetweenTags($xml, "<$field>", "</$field">);
		}
	}
}