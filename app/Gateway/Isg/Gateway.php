<?php

namespace RZP\Gateway\Isg;

use RZP\Base\RepositoryManager;
use RZP\Constants;
use RZP\Gateway\Base;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\BharatQr;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\AESCrypto;
use RZP\Constants\Entity as BaseEntity;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\QrCode;
use RZP\Constants\Mode;

class Gateway extends Base\Gateway
{
	protected $gateway = 'isg';

	public function preProcessServerCallback($input, $isBharatQr = false): array
	{
		if ($isBharatQr === true)
		{
			$qrData = $this->getQrData($input);

			return [
				'qr_data' => $qrData,
				'callback_data' => $input,
			];
		}

		return $input;
	}

	public function authorize(array $input)
	{
		parent::authorize($input);

		if ($this->isBharatQrPayment() === true)
		{
			$this->createGatewayPaymentEntityForQr($input);
		}
	}

	public function verify(array $input)
	{
		parent::verify($input);

		$verify = new Verify($this->gateway, $input);

		return $this->runPaymentVerifyFlow($verify);
	}

	protected function verifyCallback($input)
	{
		parent::verify($input);

		$verify = new Verify($this->gateway, $input);

		$this->sendPaymentVerifyRequest($verify);

		if (empty(array_diff($input, $verify->verifyResponseContent)) !== true)
		{
			throw new Exception\LogicException(
				'BharatQr ISG callback data incorrect',
				ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
				null,
				null,
				[
					'callback_response' => $input,
					'verify_response'   => $verify->verifyResponseContent,
					'gateway'           => $this->gateway
				]);
		}
	}

	protected function verifyPayment($verify)
	{
		$this->setVerifyStatus($verify);

		$this->setVerifyAmountMismatch($verify);

		$this->saveVerifyContent($verify);
	}

	protected function setVerifyAmountMismatch(Verify $verify)
	{
		$input = $verify->input;

		$content = $verify->verifyResponseContent;

		$verify->amountMismatch = true;

		$expectedAmount = $this->formatAmount($input['payment']['amount']);

		$actualAmount = $this->formatAmount($content[ResponseField::TRANSACTION_AMOUNT]);

		if ($expectedAmount === $actualAmount)
		{
			$verify->amountMismatch = false;
		}
	}
	protected function setVerifyStatus(Verify $verify)
	{
		$this->checkApiSuccess($verify);

		$this->checkGatewaySuccess($verify);

		$status = VerifyResult::STATUS_MISMATCH;

		if ($verify->apiSuccess === $verify->gatewaySuccess)
		{
			$status = VerifyResult::STATUS_MATCH;
		}

		$verify->match = ($status === VerifyResult::STATUS_MATCH);

		$verify->status = $status;
	}

	protected function saveVerifyContent($verify)
	{
		$content = $verify->verifyResponseContent;

		$gatewayPayment = $verify->payment;

		$gatewayPayment->fill($content);

		$this->repo->saveorFail($gatewayPayment);
	}

	protected function formatAmount(float $amount)
	{
		return number_format($amount, 2, '.', '');
	}

	protected function checkGatewaySuccess(Verify $verify)
	{
		$content = $verify->verifyResponseContent;

		$verify->gatewaySuccess = false;

		if ((isset($content[ResponseField::STATUS_CODE]) === true) and
			($content[ResponseField::STATUS_CODE] === Status::APPROVED))
		{
			$verify->gatewaySuccess = true;
		}
	}

	protected function sendPaymentVerifyRequest($verify)
	{
		$input = $verify->input;

		if (isset($verify->payment) === true)
		{
			$gatewayPayment = $verify->payment;

			$request = $this->getVerifyRequestArray($input, $gatewayPayment);
		}
		else
		{
			$request = $this->getVerifyRequestArray($input);
		}

		$this->traceGatewayPaymentRequest($request,
			$input,
			TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST);

		$response = $this->sendGatewayRequest($request);

		$this->traceGatewayPaymentResponse($response,
			$input,
			TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

		$responseArray = $this->jsonToArray($response->body);

		$verify->verifyResponseContent = $responseArray;
	}

	protected function getVerifyRequestArray($input, $gatewayPayment = null)
	{
		if ($gatewayPayment === null)
		{
			$terminal = $this->repo->findTeminalByGatewayMpan($input[ResponseField::MERCHANT_PAN], $this->gateway);

			$attributes = [
				RequestField::TRANSACTION_ID        => $input[ResponseField::TRANSACTION_ID],
				RequestField::PRIMARY_ID            => $input[ResponseField::PRIMARY_ID],
				RequestField::TERMINAL_ID           => $terminal->getGatewayMerchantId(),
				RequestField::TRANSACTION_AMOUNT    => $input[ResponseField::TRANSACTION_AMOUNT],
				RequestField::TRANSACTION_DATE      => $this->getFormattedDate($input[ResponseField::TRANSACTION_DATE_TIME],
					'Y-m-d'),
			];

			$this->mode = Mode::TEST;
		}
		else
		{
			$attributes = [
				RequestField::TRANSACTION_ID        => $gatewayPayment[Entity::TRANSACTION_ID],
				RequestField::PRIMARY_ID            => $gatewayPayment[Entity::MERCHANT_REFERENCE],
				RequestField::TERMINAL_ID           => $input[BaseEntity::TERMINAL][TerminalEntity::GATEWAY_MERCHANT_ID],
				RequestField::TRANSACTION_AMOUNT    => $this->getFormattedAmount($gatewayPayment[Entity::TRANSACTION_AMOUNT]),
				RequestField::TRANSACTION_DATE      => $this->getFormattedDate($gatewayPayment[Entity::TRANSACTION_DATE_TIME],
					'Y-m-d'),
			];
		}

		return  $this->getStandardRequestArray($attributes);
	}

	protected function getDecryptedString($string)
	{
		$masterKey = $this->getSecret();

		$aes = new AESCrypto(AES::MODE_ECB, $masterKey);

		return $aes->decryptString(base64_decode($string));
	}

	protected function getEncryptedString($string)
	{
		$masterKey = $this->getSecret();

		$aes = new AESCrypto(AES::MODE_ECB, $masterKey);

		return base64_encode($aes->encryptString($string));
	}

	protected function getQrData(array $input)
	{
		$this->verifyCallback($input);

		$customerCardNumber = $this->getDecryptedString($input[ResponseField::CONSUMER_PAN]);

		$qrData = [
			BharatQr\GatewayResponseParams::AMOUNT                  => $input[ResponseField::TRANSACTION_AMOUNT],
			BharatQr\GatewayResponseParams::CARD_FIRST6             => substr($customerCardNumber, 0, 6),
			BharatQr\GatewayResponseParams::CARD_LAST4              => substr($customerCardNumber, 12, 4),
			BharatQr\GatewayResponseParams::METHOD                  => Payment\Method::CARD,
			BharatQr\GatewayResponseParams::MERCHANT_REFERENCE      => $input[ResponseField::PRIMARY_ID],
			BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID   => $input[ResponseField::TRANSACTION_ID],
			BharatQr\GatewayResponseParams::MPAN                    => $input[ResponseField::MERCHANT_PAN],
		];

		return $qrData;
	}

	public function getSecret()
	{
		return $this->config['bharatqr_secret'];
	}

	protected function createGatewayPaymentEntityForQr($input)
	{
		$attributes = $this->getAttributesFromQrResponse($input);

		$payment = $this->createGatewayPaymentEntity($input, $attributes);

		return $payment;
	}

	protected function getAttributesFromQrResponse(array $input)
	{
		$attributes = [
			Entity::MERCHANT_REFERENCE          => $input[ResponseField::PRIMARY_ID],
			Entity::MERCHANT_PAN                => $input[ResponseField::MERCHANT_PAN],
			Entity::TRANSACTION_ID              => $input[ResponseField::TRANSACTION_ID],
			Entity::TRANSACTION_DATE_TIME       => $input[ResponseField::TRANSACTION_DATE_TIME],
			Entity::AUTH_CODE                   => $input[ResponseField::AUTH_CODE],
			Entity::RRN                         => $input[ResponseField::RRN],
			Entity::CONSUMER_PAN                => $input[ResponseField::CONSUMER_PAN],
			Entity::STATUS_CODE                 => $input[ResponseField::STATUS_CODE],
		];

		if (isset($input[ResponseField::SECONDARY_ID]) === true)
		{
			$attributes[Entity::SECONDARY_ID] = $input[ResponseField::SECONDARY_ID];
		}

		if (isset($input[ResponseField::TIP_AMOUNT]) === true)
		{
			$attributes[ResponseField::TIP_AMOUNT] = $input[ResponseField::TIP_AMOUNT];
		}

		$statusDescription = Status::getStatusCodeDescription($input[ResponseField::STATUS_CODE]);

		$attributes[Entity::STATUS_DESC] = $statusDescription;

		return $attributes;
	}

	protected function createGatewayPaymentEntity(array $input, array $attributes = [], $action = null)
	{
		$gatewayPayment = $this->getNewGatewayPaymentEntity();

		$action = $action ?: $this->action;

		$gatewayPayment->setAction($action);

		$gatewayPayment->setPaymentId($input['payment']['id']);

		$gatewayPayment->setAmount($input['payment']['amount']);

		$gatewayPayment->fill($attributes);

		$this->repo->saveOrFail($gatewayPayment);

		return $gatewayPayment;
	}

	protected function getFormattedDate(string $dateTime, $format)
	{
		$date =  Carbon::parse($dateTime)->format($format);

		$date = str_replace("-", "", $date);

		return $date;
	}

	protected function getFormattedAmount($amount)
	{
		return number_format($amount / 100, 2, '.', ',');
	}

	protected function traceGatewayPaymentRequest(
		array $request,
		$input = null,
		$traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
	{
		if (isset($input['payment']) === true)
		{
			$this->trace->info(
				$traceCode,
				[
					'request'    => $request,
					'gateway'    => $this->gateway,
					'payment_id' => $input['payment']['id'],
				]);
		}
		else
		{
			$this->trace->info(
				$traceCode,
				[
					'request'    => $request,
					'gateway'    => $this->gateway,
				]);
		}
	}

	protected function traceGatewayPaymentResponse(
		$response,
		$input = null,
		$traceCode = TraceCode::GATEWAY_PAYMENT_RESPONSE)
	{
		if (isset($input['payment']) === true) {
			$this->trace->info(
				$traceCode,
				[
					'response' => $response,
					'gateway' => $this->gateway,
					'payment_id' => $input['payment']['id'],
				]);
		} else {
			$this->trace->info(
				$traceCode,
				[
					'request' => $response,
					'gateway' => $this->gateway,
				]);
		}
	}
}
