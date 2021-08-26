import React from 'react';
export default class PaypalOnboardRedirect extends React.Component {
  componentWillMount() {
    (window.opener || window.parent).postMessage('paypal_onboard_redirect', {});
  }

  render() {
    return null;
  }
}
