<?php

namespace Gateway\HdfcGateway;

class HdfcGateway extends Gateway\BaseGateway
{

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

	protected $card_key_mappings = array(
		'name' => 'member'
		'number' => 'card',
		'expiry_month' => 'expmonth',
		'expiry_year' => 'expyear',
		'cvv' => 'cvv2');

	protected $txn_key_mappings = array(
		'amount' => 'amt');

	protected $generators = array('action', 'currencycode');

	protected $username = "";

	protected $password = "";

	protected $enrollRequest = array(
		'url' => 'https://securepgtest.fssnet.co.in/pgway/servlet/MPIVerifyEnrollmentXMLServlet:443',
		'xml' => '',
		'header' => array('Content-Type:text/xml');

	protected $enrollResponse = array();

	protected $authResponse = array(
		'url' => 'https://securepgtest.fssnet.co.in/pgway/servlet/TranPortalXMLServlet',
		'header' => array('Content-Type:text/xml'),
		'xml' => '');

	protected $status;

	public function __construct()
	{
		parent::__construct();
	}


	public function process($txn, $card)
	{
		if (isset($input['PaRes']))
		{

		}
		else
		{
			$response = $this->enrollCard($input);
		}

		$this->txn = $txn;

		$this->card = $card;

		$this->mapKeys($txn, $txn_key_mappings);

		$this->mapKeys($card, $card_key_mappings);

		$this->runGenerators();
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
				$this->postAuthRequestToBank()
			}
		}
		else
		{
			// if error is not null, then transaction failed.
			// @todo: see if there are standard error values in fsf doc
		}
	}

	protected function postAuthRequestToBank()
	{
		// Only need to add zip and addr fields
		// since other fields have already been added during enroll
		$data = $this->enrollRequest['data'];

		$data['zip'] = "";

		$data['addr'] = "";

		$authRequest['xml'] = $this->createXml($data);

		$response = Requests::post(
						$authRequest['url'],
						$authRequest['header'],
						$authRequest['xml']);

		$this->authResponse['xml'] = $response->body;

		$this->parseAuthResponseXml($authResponse['xml']);
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

	protected function parseAuthResponseXml($xml)
	{
		$fields = array('result', 'amt', 'trackid', 'payid', 'ref', 'tranid', 'auth', 'avr', 'postdate');
		// Can also put and get udf fields in above array

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