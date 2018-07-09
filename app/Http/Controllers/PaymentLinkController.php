<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use ApiResponse;

use RZP\Error\ErrorCode;
use RZP\Models\PaymentLink\Entity;
use RZP\Exception\BadRequestException;
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
     * Renders the hosted view for Payment link with given id
     *
     * @param string $id
     *
     * @return
     */
    public function view(string $id)
    {
        // Fetch view name and payload
        list ($view, $payload) = $this->service()->getViewNameAndPayload($id);

        // If request had an error string, append that to the payload separately for view to consume
        if (empty($error = Request::get(Entity::ERROR)) === false)
        {
            $payload[Entity::ERROR] = $error;
        }

        // Additionally, appends all request parameters too for view to consume
        $payload[Entity::REQUEST_PARAMS] = Request::all();

        return View::make($view, $payload);
    }

    /**
     * Renders hosted view for payment link with given slug
     * @param string $slug
     */
    public function viewBySlug(string $slug)
    {
        // Retrieves slug's metadata from Gimli which contains entity, id & mode
        $gimli        = $this->app['elfin']->driver('gimli');
        $slugMetadata = $gimli->expandAndGetMetadata($slug);

        // Renders 404 if no metadata available(error/exception at Gimli side)
        if ($slugMetadata === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        // Sets api's mode & invokes view()
        $this->ba->setModeAndDbConnection($slugMetadata['mode']);

        return $this->view($slugMetadata['id']);
    }
}
