<?php

namespace RZP\Models\Merchant\Promotion;

use Mail;

use RZP\Constants\Product;
use RZP\Models\Promotion;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Credits;
use RZP\Mail\Merchant\RazorpayX\Credits\SignUpCredits as SignUpCreditsMail;

class Notify
{
    public function notifyCreditViaEmail(Credits\Entity $credit)
    {
        $promotion = $credit->promotion;

        $merchant = $credit->merchant;

        // This logic of figuring out event based emails can be more
        // generic once we have more use cases of events. For now
        // simply calling signUp email
        if ($promotion->event !== null and $promotion->event->getName() === Promotion\Event\Constants::SIGN_UP)
        {
            $this->sendEmailForSignUp($promotion, $credit, $merchant);
        }

        return;
    }

    protected function sendEmailForSignUp($promotion, $credit, $merchant)
    {
        if ($promotion->getProduct() !== Product::BANKING)
        {
            return;
        }
        // For Capital mobile sign up flow email wont be collected hence skipping this flow
        if ($merchant->getEmail() === null)
        {
            return;
        }

        $data = [
            Promotion\Entity::CREDITS       => (new Credits\Core)->getCreditInAmount(
                                                                        $credit->getValue(),
                                                                        $credit->getProduct()),
            Merchant\Constants::MERCHANT    => [
                Merchant\Entity::EMAIL      => $merchant->getEmail(),
            ]
        ];

        $data[Promotion\Entity::CREDITS] = $this->getFormattedAmount($data[Promotion\Entity::CREDITS]);

        $signupMail = new SignUpCreditsMail($data);

        Mail::queue($signupMail);
    }

    protected function getFormattedAmount($amount)
    {
        $formattedAmount = number_format($amount / 100, 2, '.', '');

        return $formattedAmount;
    }
}
