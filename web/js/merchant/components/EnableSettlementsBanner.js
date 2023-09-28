/* eslint-disable */
import React from 'react';
import { Link } from 'react-router-dom';
import Banner from 'common/ui/Banner';
import RTracking from 'react-tracking';

const EnableSettlementsBanner = (props) => (
  <div className="enable-settlements-banner">
    <Banner>
      Your settlements are on hold. You will need to fill the KYC Form to receive your payments in
      your bank account
      <span className="big-dot-separator" />
      <Link
        to="/activation"
        onClick={() => {
          props.tracking.trackEvent(
            window.rzpQ.onbr().initiated('kyc.form_fill', {
              clickSource: props.source ? props.source : '',
            }),
          );
        }}
      >
        Complete KYC Form
      </Link>
    </Banner>
  </div>
);

export default RTracking((state, props, args) => {
  return window.rzpQ.component('EnableSettlementsBanner');
})(EnableSettlementsBanner);
