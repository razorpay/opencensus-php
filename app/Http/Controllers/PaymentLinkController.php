<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use ApiResponse;

use RZP\Constants\Entity as E;
use RZP\Exception\BaseException;
use RZP\Models\PaymentLink\Entity;
use RZP\Http\Controllers\Traits\HasCrudMethods;

class PaymentLinkController extends Controller
{
    use HasCrudMethods;

    /**
     * {@inheritDoc}
     * Overridden as it passes around $input to service method
     */
    public function get(string $id)
    {
        $input = Request::all();

        $entity = $this->service()->fetch($id, $input);

        return ApiResponse::json($entity);
    }

    public function sendNotification(string $id)
    {
        $this->service()->sendNotification($id, $this->input);

        return ApiResponse::json([]);
    }

    public function expirePaymentLinks()
    {
        $summary = $this->service()->expirePaymentLinks();

        return ApiResponse::json($summary);
    }

    public function deactivate(string $id)
    {
        $response = $this->service()->deactivate($id);

        return ApiResponse::json($response);
    }

    public function activate(string $id)
    {
        $response = $this->service()->activate($id, $this->input);

        return ApiResponse::json($response);
    }

    /**
     * Renders hosted view for payment link with given id
     * @param string $id
     */
    public function view(string $id)
    {
        try
        {
            $viewPayload = $this->service()->getHostedViewPayload($id);

            // If request had an error string, append that separately too for view to consume
            if (empty($error = Request::get(Entity::ERROR)) === false)
            {
                $viewPayload[Entity::ERROR] = $error;
            }
            // Additionally, appends all request parameters too for view to consume
            $viewPayload[Entity::REQUEST_PARAMS] = Request::all();
        }
        catch (BaseException $e)
        {
            $viewPayload = $e->getError()->toPublicArray();
        }

        $hostedTemplateId = $viewPayload['data'][E::PAYMENT_LINK][Entity::HOSTED_TEMPLATE_ID] ?? null;

        $view = $this->service()->getHostedViewTemplate($hostedTemplateId);

        return View::make($view, $viewPayload);
    }
}
