import React, { useRef, useEffect } from 'react';
import { withRouter } from 'react-router-dom';
import InternationalCards from './InternationalCards';
import PaypalOnboardingButton from './PaypalOnboarding';
import { DocLink } from 'merchant/components/DocsLink';

const InternationalPayments = ({ mode, user, config }) => {
  const internationalSection = useRef(null);
  useEffect(() => {
    if (internationalSection.current && location.hash === '#request-international') {
      internationalSection.current.scrollIntoView();
    }
  }, []);
  return (
    <div ref={internationalSection} class="panel panel-default international-payments">
      <div class="panel-heading">
        <span class="title">International Payments</span>
        <a
          target="_blank"
          class="know-more-link"
          href="https://razorpay.com/payment-gateway/#go-international"
        >
          Know more
          <i class="i i-external-link" />
        </a>
        <div class="description">
          Accept international payments in nearly 100 foreign currencies from your customers
        </div>
      </div>
      <div class="panel-body">
        <ol>
          {mode === 'live' && <InternationalCards />}

          {user.isActivated && mode === 'live' && config.fee_bearer !== 'customer' && (
            <PaypalWrapper />
          )}
        </ol>
      </div>
    </div>
  );
};

const PaypalWrapper = () => (
  <div class="paypal-auto-onboarding" id="paypal-auto-onboarding">
    <div class="heading">
      <li class="title">PayPal </li>
      <DocLink
        class="highlight know-more-link"
        target="_blank"
        href="https://razorpay.com/docs/payment-methods/paypal"
      >
        Know more
        <i class="i i-external-link" />
      </DocLink>

      <PaypalOnboardingButton />
    </div>

    <div class="body">
      <div class="description">
        Accept international payments using PayPal on Razorpay Checkout.
      </div>

      <div className="alert alert-info">
        <h4>International Payments Only</h4>
        <p>Currently, you can only accept payments in international currencies using PayPal.</p>
        <p>
          You <i>CANNOT</i> accept payments in <span class="inr">INR</span> using PayPal.
        </p>
      </div>
    </div>
  </div>
);

export default withRouter(InternationalPayments);
