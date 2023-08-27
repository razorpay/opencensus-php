<?php

namespace RZP\Models\CardMandate\MandateHubs\OptimizerHubs\PayuHub;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\CardMandate;
use RZP\Models\CardMandate\MandateHubs\Mandate;
use RZP\Models\CardMandate\MandateHubs\Notification;
use RZP\Models\CardMandate\MandateHubs\OptimizerHubs\OptimizerHubUtils;
use RZP\Models\Payment;
use RZP\Models\CardMandate\MandateHubs\OptimizerHubs\Constants;

class PayuHub extends CardMandate\MandateHubs\BaseHub
{

    use OptimizerHubUtils;

    public function RegisterMandate(CardMandate\Entity $cardMandate, Payment\Entity $payment, $input = []): Mandate
    {
        // No API call to payu or any other mandateHQ solution. Store Mandate in DB with empty MandateID.
        // Update MandateID once registration payment is successful.
        return $this->buildMandateEntity($cardMandate, $payment, $input);
    }

    public function CancelMandate(CardMandate\Entity $cardMandate): ?Mandate
    {
        $input = $this->buildCancelMandatePayload($cardMandate);
        $this->app['gateway']->call(Constants::MOZART, Payment\Action::CARD_MANDATE_CANCEL, $input, $this->mode);
        return null;
    }

    public function ReportInitialPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        if ($payment->isFailed() === true) {
            return;
        }

        // 1. Update mandate id. In Payu, the ID sent by Payu is stored inside gateway_reference_id1 field of
        // cards.authorization entity
        $this->updateMandateId($cardMandate, $payment, 'gateway_reference_id1');

        // If card is network tokenized, update network token details at Gateway and store token pan in card mandate
        $token = $payment->localToken;
        if ($token->card->isRzpSavedCard() == false) {

            $tokenInput = $token->card->buildTokenisedTokenForMandateHub();
            $networkToken = $tokenInput['token'];

            // 2. Store Vault Token PAN in card mandate entity. Used during subsequent debits
            $this->storeVaultTokenPan($cardMandate, $networkToken);

            // 3. Update token details at payu's end.
            // Found in testing that this is not needed.
            //$input = $this->buildUpdateTokenPayload($cardMandate, CardMandate\MandateHubs\MandateHubs::PAYU_HUB, $networkToken);
            //$this->app['gateway']->call(Constants::MOZART, Payment\Action::CARD_MANDATE_UPDATE_TOKEN, $input, $this->mode);
        }
    }

    public function reportSubsequentPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        // As of now no reporting to be done after subsequent payment for Payu
    }

    public function getRedirectResponseIfApplicable(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        // Not applicable for Payu
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public function getValidationBeforeSubsequentPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment, $input = [], $forceCard = true)
    {
        // 1. Payment terminal and Card Mandate terminal Must be same
        if ($payment->terminal->getId() != $cardMandate->terminal->getId()) {
            throw new BadRequestValidationFailureException('Subsequent payment validation failed.');
        }

        $validationResponse = [];


        // 2. Check if mandate is active by calling Payu
        $input = $this->buildMandateVerifyPayload($cardMandate, $payment);
        $verifyResponse = $this->app['gateway']->call(Constants::MOZART, Payment\Action::CARD_MANDATE_VERIFY, $input, $this->mode);
        if ($verifyResponse[Constants::DATA][Constants::MANDATE_STATUS] != Constants::ACTIVE) {
            throw new BadRequestValidationFailureException('Subsequent payment validation failed.');
        }

        $validationResponse[Payment\Action::CARD_MANDATE_VERIFY] = $verifyResponse;

        // 3. check pre-debit notification status
        $input = $this->buildPreDebitVerifyPayload($cardMandate, $payment);
        $response = $this->app['gateway']->call(Constants::MOZART, Payment\Action::CARD_MANDATE_PRE_DEBIT_NOTIFY, $input, $this->mode);
        if ($response[Constants::DATA][Constants::MANDATE_STATUS] != 1) {
            throw new BadRequestValidationFailureException('Subsequent payment validation failed.');
        }

        if ($response[Constants::DATA][Constants::AFA_STATUS] == Constants::PENDING) {
            throw new BadRequestValidationFailureException('Subsequent payment validation failed. Mandate not approved.');
        }

        $validationResponse[Constants::NOTIFY_VERIFY] = $response;

        return $validationResponse;
    }

    public function CreatePreDebitNotification(CardMandate\Entity $cardMandate, ?Payment\Entity $payment, $input): Notification
    {
        $input = $this->buildCreatePreDebitNotificationPayload($payment, $cardMandate, $input);
        $response = $this->app['gateway']->call(Constants::MOZART, Payment\Action::CARD_MANDATE_PRE_DEBIT_NOTIFY, $input, $this->mode);
        return $this->buildCreatePreDebitNotificationResponse($response[Constants::DATA][Constants::INVOICE_ID]);
    }

    public function updateTokenisedCardTokenInMandate(CardMandate\Entity $cardMandate, $input)
    {
        // Mostly we will never reach this point, as this happens in async tokenization flow.
        // TODO : check if necessary
        $input = $this->buildUpdateTokenPayload($cardMandate, $input[Constants::TOKEN]);
        $this->app['gateway']->call(Constants::MOZART, Payment\Action::CARD_MANDATE_UPDATE_TOKEN, $input, $this->mode);
    }

    // ====== EXTRA FUNCTIONS specific to Payu =====

}
