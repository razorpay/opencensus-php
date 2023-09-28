import React, { useRef, useEffect } from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import InternationalCards from './InternationalCards';
import PaypalOnboardingButton from './PaypalOnboarding';
import { DocLink } from 'merchant/components/DocsLink';
import Popover, { PopoverBody } from 'common/ui/Popover';

export const getClassName = (status) => {
  if (status === 'activated') {
    return 'success';
  }
  if (['pending', 'created', 'requested', 'permission_missing'].includes(status)) {
    return 'warning';
  }
  return '';
};

export const getBadgeVariant = (status) => {
  if (status === 'activated') {
    return 'success';
  }
  if (['created', 'requested'].includes(status)) {
    return 'information';
  }

  if (['pending', 'permission_missing'].includes(status)) {
    return 'notice';
  }
  return '';
};

export const getStatusMessage = (status) => {
  switch (status) {
    case 'activated':
      return 'You are now accepting payments via Paypal.';

    case 'permission_missing':
      return 'Please provide the necessary permission to Razorpay from your paypal account. Please contact the PayPal customer support team for any queries.';

    case 'requested':
      return 'There was an issue with linking your account with PayPal. We request you to register for your Paypal account again.';

    case 'pending':
      return (
        <span>
          Your Paypal account is not configured to receive payments. Please visit your Paypal
          account and make sure you have added the bank details/card details correctly. Please
          contact{' '}
          <a
            href="https://www.paypal.com/in/smarthelp/contact-us"
            target="_blank"
            rel="noopener noreferrer"
            className={`support-link status-${getClassName(status)}`}
          >
            PayPal customer support <i class="i i-external-link" />
          </a>{' '}
          team for any queries.
        </span>
      );

    case 'created':
      return 'Please verify the confirmation email sent by Paypal to your registered email id. Email verification can take upto 24 hours to update.';
    default:
      return '';
  }
};

const InternationalPayments = ({ mode, user, config, org, paypal_terminals }) => {
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
          rel="noopener noreferrer"
          class="know-more-link"
          href={
            org.custom_code === 'axis'
              ? 'https://axisbank-docs.razorpay.com/payments/payments/international-payments/'
              : 'https://razorpay.com/payment-gateway/#go-international'
          }
        >
          <span>Know more</span>
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
            <PaypalWrapper terminals={paypal_terminals} />
          )}
        </ol>
      </div>
    </div>
  );
};

const PaypalWrapper = ({ terminals }) => {
  const status = terminals.length && terminals[0].terminal.status;
  const showStatus = [
    'created',
    'activated',
    'requested',
    'permission_missing',
    'pending',
  ].includes(status);
  return (
    <div class="paypal-auto-onboarding" id="paypal-auto-onboarding">
      <div class="heading">
        <li class="title">
          PayPal{' '}
          <DocLink
            class="highlight know-more-link"
            target="_blank"
            href="https://razorpay.com/docs/payment-methods/paypal"
          >
            <span>Know more</span>
            <i class="i i-external-link" />
          </DocLink>
          {showStatus ? (
            <a className={`status-pill status-pill-${getClassName(status)}`}>
              <span className="status-text">
                {['created', 'pending', 'permission_missing', 'requested'].includes(status)
                  ? 'pending'
                  : status}
              </span>{' '}
              {status === 'activated' && (
                <span>
                  <Popover theme="dark" align="bottom">
                    <PopoverBody>
                      <div>{getStatusMessage(status)}</div>
                    </PopoverBody>
                  </Popover>
                </span>
              )}
            </a>
          ) : null}
          {terminals.length > 0 && (
            <a className="merchant-id">
              {terminals[0].terminal.merchant_id}
              <Popover align="right" theme="dark">
                <PopoverBody>
                  <div className="disabled-text">Paypal generated Merchant ID</div>
                </PopoverBody>
              </Popover>
            </a>
          )}
        </li>
        <PaypalOnboardingButton isInternationalPayment={false} status={status} />
      </div>

      <div class="body">
        {terminals.length === 0 && (
          <div class="description">
            Accept international payments using PayPal on Razorpay Checkout.
          </div>
        )}

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
};

export default withRouter(InternationalPayments);
