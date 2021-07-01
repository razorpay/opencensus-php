<?php

namespace RZP\Models\Risk;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\PaymentLink;

class Service extends Base\Service
{
    public function create(array $input)
    {
        if (isset($input[Entity::PAYMENT_ID]) === true)
        {
            $payment = $this->repo->payment->findByPublicId($input[Entity::PAYMENT_ID]);
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                'The payment id field is required.');
        }

        $risk = (new Core)->create($payment, $input);

        return $risk->toArrayPublic();
    }

    public function update(string $id, array $input)
    {
        $risk = $this->repo->risk->findByPublicId($id);

        $risk = (new Core)->edit($risk, $input);

        return $risk->toArrayPublic();
    }

    public function fetch(string $id)
    {
        $risk = $this->repo->risk->findByPublicId($id);

        return $risk->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $entities = $this->repo->risk->fetch($input);

        return $entities->toArrayPublic();
    }

    public function getGrievanceEntityDetails(string $id)
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        (new Validator)->validateInput('grievance_fetch_input', ['id' => $id]);

        return $this->getEntityDetailsFromId($id);
    }

    public function postCustomerGrievance(array $input)
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        (new Validator)->validateInput('grievance_post_input', $input);

        $this->validateCaptcha($input['captcha_id']);

        $entityDetails = $this->getEntityDetailsFromId($input['entity_id']);

        return $this->core()->postCustomerFlaggingToRiskService($input, $entityDetails);
      }

    protected function getEntityDetailsFromId(string $id)
    {
        $delimiter = '_';

        $ix = strpos($id, $delimiter);

        if ($ix === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Entity.');
        }

        $sign = substr($id, 0,$ix);

        switch ($sign)
        {
            case Invoice\Entity::getSign():
            case Invoice\Entity::getV2Sign():
                return (new Invoice\Core)->getGrievanceEntityDetails($id);

            case PaymentLink\Entity::getSign():
                return (new PaymentLink\Core)->getGrievanceEntityDetails($id);

            case Payment\Entity::getSign():
                return (new Payment\Core)->getGrievanceEntityDetails($id);

            default:
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid Entity.');
        }
    }

    protected function validateCaptcha(string $captchaResponse)
    {
        $app = App::getFacadeRoot();

        if ($app->environment('production') === false)
        {
            return;
        }

        $clientIpAddress = $_SERVER['HTTP_X_IP_ADDRESS'] ?? $app['request']->ip();

        $noCaptchaSecret = config('app.pl_demo.nocaptcha_secret');

        $input = [
            'secret'   => $noCaptchaSecret,
            'response' => $captchaResponse,
            'remoteip' => $clientIpAddress,
        ];

        $captchaQuery = http_build_query($input);

        $url = 'https://www.google.com/recaptcha/api/siteverify?'. $captchaQuery;

        $response = \Requests::get($url);

        $output = json_decode($response->body);

        if($output->success !== true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CAPTCHA_FAILED,
                null,
                [
                    'output_from_google'        => (array)$output,
                ]
            );
        }
    }
}
