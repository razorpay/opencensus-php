import React from 'react';
import { Link, withRouter } from 'react-router-dom';
import Banner from 'rzp/ui/Banner';
import RTracking from 'react-tracking';

export default RTracking((state, props, args) => {
  return window.rzpQ.component('HomeContainer');
})(
  withRouter(props => (
    <div className="enable-settlements-banner">
      <Banner>
        Your settlements are on hold. You will need to fill the KYC Form to
        receive your payments in your bank account
        <span className="big-dot-separator" />
        <Link
          to="/activation"
          onClick={() => {
            props.tracking.trackEvent(
              window.rzpQ.onbr().initiated('kyc.form_fill', {
                clickSource: props.location.pathname.substr(1),
              })
            );
          }}
        >
          Complete KYC Form
        </Link>
      </Banner>
    </div>
  ))
);
