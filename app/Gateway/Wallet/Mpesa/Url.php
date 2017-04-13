<?php

namespace RZP\Gateway\Wallet\Mpesa;

class Url
{
    const TEST_DOMAIN = 'http://182.19.20.182:81';
    const LIVE_DOMAIN = 'https://www.mpesa.in';

    const AUTHORIZE         = '/mcommPg/paymentGateway/mrchntProcessor';
    const VERIFY            = '/mcommerce.webservices/pgService?wsdl';
    const VALIDATE_CUSTOMER = '/mcommerce.webservices/pgService?wsdl';
    const OTP_GENERATE      = '/mcommerce.webservices/pgService?wsdl';
    const OTP_SUBMIT        = '/mcommerce.webservices/pgService?wsdl';
    const REFUND            = '/mcommerce.webservices/pgService?wsdl';

    const WSDL              = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
}
