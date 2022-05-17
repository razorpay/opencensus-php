<?php

namespace RZP\Models\Payment;

use App;
use Illuminate\Support\Str;
use RZP\Constants\Mode;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Customer\Token;
use RZP\Models\Card;

class TokenisationExperiment
{
    protected $app;
    protected $trace;
    protected $mode;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
        $this->mode = $this->app['rzp.mode'] ?? '';
    }

    /**
     * This decides if the card payments should go through actual card numbers
     * or tokenised card number based on two experiments
     *
     * Experiment 1 - for tokenised global saved card payments,
     * only y% of traffic will go through tokenised card
     *
     * Experiment 2 - for tokenised global and local saved card payments,
     * based on the combination of (issuer, network, card type),
     * three lists are maintained -
     * 1. Blacklist - (100% will go through actual card)
     * 2. Ramp up list - x% traffic will go through tokenised card
     * 3. Whitelist - everything else will go through tokenised card
     *
     * @param  Token\Entity $token
     * @return bool
     */
    public function shouldPaymentProcessThroughTokenisedCard(Token\Entity $token): bool
    {
        if (($token->isGlobal() === true) and
            ($this->shouldGlobalCardPaymentGoThroughTokenisedCardExp() === false))
        {
            return false;
        }

        return $this->shouldPaymentGoThroughTokenisedCardIssuerNetworkTypeExp($token->card);
    }

    /**
     * Splitz experiment - for tokenised global saved card payments,
     * only y% of traffic will go through tokenised card
     *
     * @return bool
     */
    protected function shouldGlobalCardPaymentGoThroughTokenisedCardExp(): bool
    {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.global_card_payment_splitz_experiment_id'),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            if ($variant === 'variant_on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::GLOBAL_CARD_PAYMENT_PROCESS_SPLITZ_ERROR
            );
        }

        return false;
    }

    /**
     * Razorx experiment - for tokenised global and local saved card payments,
     * based on the combination of (issuer, network, card type),
     * three lists are maintained -
     * 1. Blacklist - (100% will go through actual card)
     * 2. Ramp up list - x% traffic will go through tokenised card
     * 3. Whitelist - everything else will go through tokenised card
     *
     * @param  Card\Entity $card
     * @return bool
     */
    protected function shouldPaymentGoThroughTokenisedCardIssuerNetworkTypeExp(Card\Entity $card): bool
    {
        try
        {
            $experimentKey = implode('_', [
                $card->getIssuer(),
                $card->getNetworkCode(),
                $card->getType()
            ]);

            $variant = $this->app->razorx->getTreatment(
                $experimentKey,
                Merchant\RazorxTreatment::PAYMENT_PROCESS_THROUGH_TOKENISED_CARD,
                $this->mode
            );

            if ($variant === 'on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::ISSUER_NETWORK_TYPE_RAZORX_EXPERIMENT_ERROR
            );
        }

        return false;
    }

    /**
     * Runs a razorx contextramp experiment to help control & gradually ramp-up
     * sync. provisioning of network tokens for global saved cards.
     *
     * @param Card\Entity $card
     *
     * @return bool
     */
    public function shouldProvisionGlobalToken(Card\Entity $card): bool
    {
        try
        {
            $variant = $this->app->razorx->getTreatment(
                $card->getNetworkCode(),
                Merchant\RazorxTreatment::PROVISION_GLOBAL_NETWORK_TOKEN,
                $this->mode
            );

            if (Str::startsWith($variant, 'on_')) {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::GLOBAL_CARD_PAYMENT_PROCESS_SPLITZ_ERROR
            );
        }

        return false;
    }

    /**
     * Splitz experiment - Ramp-up for creating a local token on the global
     * customer in the payment create flow
     *
     * @param string $merchantId
     * 
     * @return bool
     */
    public function shouldCreateLocalTokenOnGlobalCustomer(string $merchantId): bool
    {
        try
        {
            $mode = $this->app['rzp.mode'] ?? '';

            if($mode === Mode::TEST)
            {
                return true;
            }

            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.local_token_on_global_customer_experiment_id'),
                'request_data'  => json_encode(['merchant_id' => $merchantId]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            if($variant === 'variant_on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::LOCAL_TOKEN_ON_GLOBAL_CUSTOMER_SPLITZ_ERROR
            );
        }

        return false;
    }
}
