import React from 'react';
import InternationalConfig from './InternationalConfig';
import PaypalOnboarding from './PaypalOnboarding';

const InternationalPayments = ({ mode, user, config }) => {
  return (
    <div class="panel panel-default international-payments">
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
          {mode === 'live' && <InternationalConfig />}

          {user.isActivated && mode === 'live' && config.fee_bearer !== 'customer' && (
            <PaypalOnboarding />
          )}
        </ol>
      </div>
    </div>
  );
};

export default InternationalPayments;
