<?php

namespace Gateway\HdfcGateway;

use Requests;

class HdfcGatewayUtility
{
	public static function postRequest($request)
	{
		$options['verify'] = false;

		$response = Requests::post(
						$request['url'],
						$request['header'],
						$request['xml'],
						$options);

		return $response;
	}

	public static function createXml($array)
	{
		$xml = "";

		foreach ($array as $key => $value)
		{
			$xml .= "<$key>$value</$key>";
		}

		return $xml;
	}

	public static function getAndParseError(array &$response)
	{
		$error = self::getFieldFromXML($response['xml'], 'error_code_tag');

		if ($error === null)
		{ 
			return true;
		}

		$response['error']['code'] = $error;
		$response['error']['service'] = self::getFieldFromXML($response['xml'], 'error_service_tag');
		$response['error']['text'] = self::getFieldFromXML($response['xml'], 'error_text');

		return true;
	}

	public static function parseResponseXml(array &$response)
	{
		if (self::getAndParseError($response))
		{
			return;
		}

		self::getFieldsFromXML(
			$response['xml'],
			$response['fields'],
			$response['data']);
	}

	public static function getFieldsFromXML($xml, $fields, &$array)
	{
		foreach ($fields as $field)
		{
			$array[$field] = GetTextBetweenTags($xml, "<$field>", "</$field>");
		}
	}

	public static function getFieldFromXML($xml, $field)
	{
		return GetTextBetweenTags($xml, "<$field>", "</$field>");
	}

	public static function runRequestResponseFlow(array &$request, array &$response)
	{
		// XML generated from the fields
		$request['xml'] = self::createXml($request['data']);

		// send the request and get response
		$response['response'] = self::postRequest($request);
		
		$response['xml'] = $response['response']->body;

		self::parseResponseXml($response);
	}
}