<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use ApiResponse;
use RZP\Trace\Tracer;
use Illuminate\Http\Request  as CurrentRequest;
use RZP\Error\ErrorCode;
use RZP\Models\PaymentLink\Entity;
use RZP\Models\PaymentLink\ViewType;
use RZP\Exception\BadRequestException;
use RZP\Http\Controllers\Traits\HasCrudMethods;
use RZP\Exception\BadRequestValidationFailureException;

class PaymentLinkController extends Controller
{
    use HasCrudMethods;

    /**
     * {@inheritDoc}
     * Overridden as it passes around $input to service method
     */
    public function get(string $id)
    {
        $response = Tracer::inSpan(['name' => 'payment_page.get'], function() use($id)
        {
            return $this->service()->fetch($id, $this->input);
        });

        return ApiResponse::json($response);
    }

    public function getWithDetailsForDashboard(string $id)
    {
        $response = Tracer::inSpan(['name' => 'payment_page.get_details'], function() use($id)
        {
            return $this->service()->fetchWithDetailsForDashboard($id, $this->input);
        });

        return Tracer::inSpan(['name' => 'payment_page.get_details.response'], function() use($response)
        {
            return ApiResponse::json($response);
        });
    }

    public function sendNotification(string $id)
    {
        Tracer::inSpan(['name' => 'payment_page.send_notification'], function() use($id)
        {
            $this->service()->sendNotification($id, $this->input);
        });
        return ApiResponse::json([]);
    }

    public function expirePaymentLinks()
    {
        $summary = Tracer::inSpan(['name' => 'payment_page.expire'], function()
        {
            return $this->service()->expirePaymentLinks();
        });

        return ApiResponse::json($summary);
    }

    public function deactivate(string $id)
    {
        $response = Tracer::inSpan(['name' => 'payment_page.deactivate'], function() use($id)
        {
            return $this->service()->deactivate($id);
        });

        return ApiResponse::json($response);
    }

    public function activate(string $id)
    {
        $response = Tracer::inSpan(['name' => 'payment_page.activate'], function() use($id)
        {
            return $this->service()->activate($id, $this->input);
        });

        return ApiResponse::json($response);
    }

    /**
     * Checks for existence of a given slug string in gimli.
     * @param  string $slug
     * @return \Illuminate\Http\Response
     */
    public function slugExists(string $slug)
    {
        $gimli  = $this->app['elfin']->driver('gimli');

        $exists = Tracer::inSpan(['name' => 'payment_page.slug.exists.gimli_expand'], function() use($gimli, $slug)
        {
            return ($gimli->expand($slug) !== null);
        });

        return ApiResponse::json(compact('exists'));
    }

    public function buttonHostedView(string $id, CurrentRequest $request)
    {
        list ($view, $payload) = $this->service()->getButtonViewNameAndPayload($id, $this->input, $request);

        return View::make($view)->with('data', $payload);
    }

    public function subscriptionButtonHostedView(string $id, CurrentRequest $request)
    {
        list ($view, $payload) = $this->service()->getButtonViewNameAndPayload($id, $this->input, $request, ViewType::SUBSCRIPTION_BUTTON);

        return View::make($view)->with('data', $payload);
    }

    public function createSubscription(string $id)
    {
        $response = $this->service()->createSubscription($id, $this->input);

        return ApiResponse::json($response);
    }

    public function create()
    {
        $input = Tracer::inSpan(['name' => 'payment_page.controller.create.input'], function() {
            return Request::all();
        });

        $entity = Tracer::inSpan(['name' => 'payment_page.controller.create.service_call'], function() use ($input) {
            return $this->service()->create($input);
        });

        return Tracer::inSpan(['name' => 'payment_page.controller.create.response'], function() use ($entity) {
            return ApiResponse::json($entity);
        });
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
        try {
            // Fetch view name and payload
            [$view, $payload] = Tracer::inSpan(['name' => 'payment_page.controller.view'], function() use ($id) {
                return $this->service()->getViewNameAndPayload($id);
            });

            // If request had an error string, append that to the payload separately for view to consume
            if (empty($error = Request::get(Entity::ERROR)) === false)
            {
                $payload[Entity::ERROR] = $error;
            }

            // Additionally, appends all request parameters too for view to consume
            $payload[Entity::REQUEST_PARAMS] = $this->input;

            Tracer::inSpan(['name' => 'payment_page.controller.view.append_amount'], function() use ($id, &$payload) {
                $this->service()->appendAmountIfPossible($id, $this->input, $payload);
            });

            return Tracer::inSpan(['name' => 'payment_page.controller.view.response'], function() use ($view, $payload) {
                return View::make($view, $payload);
            });
        }
        catch(BadRequestException | BadRequestValidationFailureException $e)
        {
            $view_type = $e->getData()[Entity::VIEW_TYPE];

            $data = ['error_code' => $e->getCode(), 'message' => $e->getMessage(), 'data' => $e->getData()];

            if($view_type === ViewType::PAYMENT_HANDLE)
            {
                return View::make('payment_handle.error_payment_handle', ['data' => $data]);
            }
            return View::make('payment_link.error_payment_link', ['data' => $data]);
        }
    }

    /**
     * Renders hosted view for payment link with given slug
     * @param string $slug
     */
    public function viewBySlug(string $slug)
    {
        // Retrieves slug's metadata from Gimli which contains entity, id & mode
        $gimli        = $this->app['elfin']->driver('gimli');
        $slugMetadata = Tracer::inSpan([
            'name' => 'payment_pages.hosted.pages.slug.get.template'],
            function() use ($slug, $gimli) {
                return $gimli->expandAndGetMetadata($slug);
        });

        // Renders 404 if no metadata available(error/exception at Gimli side)
        if ($slugMetadata === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        // Sets api's mode & invokes view()
        Tracer::inSpan(['name' => 'payment_pages.hosted.pages.slug.set_mode'], function() use ($slugMetadata) {
            $this->ba->setModeAndDbConnection($slugMetadata['mode']);
        });

        return $this->view($slugMetadata['id']);
    }

    public function getHostedButtonDetails(string $id)
    {
        $data = $this->service()->getHostedButtonDetails($id);

        return ApiResponse::json($data);
    }

    public function getHostedButtonPreferences(string $id)
    {
        $data = $this->service()->getHostedButtonPreferences($id);

        return ApiResponse::json($data);
    }

    /**
     * It uploads a given array of images into s3 bucket and returns the array of cdn url for the images.
     *
     * @return \Illuminate\Http\Response
     */
    public function upload()
    {
        $data = Tracer::inSpan(['name' => 'payment_page.upload'], function()
        {
            return $this->service()->upload($this->input);
        });

        return ApiResponse::json($data);
    }

    public function createOrder(string $id)
    {
        $response = Tracer::inSpan(['name' => 'payment_page.order.create'], function() use($id)
        {
            return $this->service()->createOrder($id, $this->input);
        });

        return ApiResponse::json($response);
    }

    public function createOrderOptions(string $id, CurrentRequest $request)
    {
        $response = ApiResponse::json([]);

        $origin = $request->headers->get('origin');

        $urls = $this->app['config']->get('app.payment_page_allowed_cors_url');

        if (in_array($origin, $urls) === true)
        {
            $response->headers->set('Access-Control-Allow-Origin', $origin);

            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

            return $response;
        }

       $response->headers->set(
           'Access-Control-Allow-Origin',
           $this->app['config']->get('app.payment_link_hosted_base_url')
       );

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        return $response;
    }

    public function updatePaymentPageItem(string $paymentPageItemId)
    {
        $response = Tracer::inSpan(['name' => 'payment_page.ppi.update'], function() use($paymentPageItemId)
        {
            return $this->service()->updatePaymentPageItem($paymentPageItemId, $this->input);
        });

        return ApiResponse::json($response);
    }

    public function setMerchantDetails()
    {
        $input = Request::all();

        $response =  Tracer::inSpan(['name' => 'payment_page.merchant_details.set'], function() use($input)
        {
            return $this->service()->setMerchantDetails($input);
        });

        return ApiResponse::json($response);
    }

    public function fetchMerchantDetails(string $merchantId)
    {
        $response = Tracer::inSpan(['name' => 'payment_page.merchant_details.fetch'], function()
        {
            return $this->service()->fetchMerchantDetails();
        });

        return ApiResponse::json($response);
    }

    public function setReceiptDetails(string $id)
    {
        $input = Request::all();

        $response = Tracer::inSpan(['name' => 'payment_page.receipts.set'], function() use($input, $id)
        {
            return $this->service()->setReceiptDetails($id, $input);
        });

        return ApiResponse::json($response);
    }

    public function getInvoiceDetails(string $paymentId)
    {
        $response = Tracer::inSpan(['name' => 'payment_page.invoice.get'], function() use($paymentId)
        {
            return $this->service()->getInvoiceDetails($paymentId);
        });

        return ApiResponse::json($response);
    }

    public function sendReceipt(string $paymentId)
    {
        $input = Request::all();

        $response = Tracer::inSpan(['name' => 'payment_page.receipt.send'], function() use($paymentId, $input)
        {
            return $this->service()->sendReceipt($paymentId, $input);
        });

        return ApiResponse::json($response);
    }

    public function saveReceiptForPayment(string $paymentId)
    {
        $input = Request::all();

        $response = Tracer::inSpan(['name' => 'payment_page.receipt.save'], function() use($paymentId, $input)
        {
            return $this->service()->saveReceiptForPayment($paymentId, $input);
        });

        return ApiResponse::json($response);
    }

    public function getPayments(string $id)
    {
        $input = Request::all();

        $response = Tracer::inSpan(['name' => 'payment_page.payments.get'], function() use($id, $input)
        {
            return $this->service()->getPayments($id, $input);
        });

        return ApiResponse::json($response);
    }

    public function updatePaymentHandle()
    {
        $input = Request::all();

        $response = $this->service()->updatePaymentHandle($input);

        return ApiResponse::json($response);
    }

    public function getPaymentHandle()
    {
        $response = $this->service()->getPaymentHandleByMerchant();

        return ApiResponse::json($response);
    }

    public function suggestionPaymentHandle()
    {
        $input = Request::all();

        $response = $this->service()->suggestionPaymentHandle($input);

        return ApiResponse::json($response);
    }

    public function handleExists(string $slug)
    {
        $exists = $this->service()->paymentHandleExists($slug);

        return ApiResponse::json(compact('exists'));
    }

    public function precreatePaymentHandle()
    {
        $response =  $this->service()->precreatePaymentHandle();

        return ApiResponse::json($response);
    }

    public function createPaymentHandle()
    {
        $response = $this->service()->createPaymentHandleV2();

        return ApiResponse::json($response);
    }
}
