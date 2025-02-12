import React from 'react';
import { connect } from 'react-redux';

import { getFormattedAmount } from 'common/utils/rzp-utils';
import { DocLink } from 'merchant/components/DocsLink';
import { UPI_AVL_LIMIT } from 'merchant/helpers/data';

class UPIBanner extends React.Component {
  render() {
    return (
      <div className="upi-banner">
        <i className="i i-info-circle m-r" />
        UPI payment is not available when amount is greater than ₹{' '}
        {getFormattedAmount(UPI_AVL_LIMIT)}.
        <DocLink className="m-l" href="https://razorpay.com/docs/subscriptions/">
          Learn more <i className="i i-external-link" />
        </DocLink>
      </div>
    );
  }
}

export default connect((state) => ({
  user: state.session.user,
}))(UPIBanner);
