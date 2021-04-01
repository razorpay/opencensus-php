import { connect } from 'react-redux';
import { UPI_AVL_LIMIT } from 'merchant/helpers/data';
import { getFormattedAmount } from 'common/utils/rzp-utils';

@connect(state => ({
  user: state.session.user,
}))
export default class UPIBanner extends React.Component {
  render() {
    return (
      <div class="upi-banner">
        <i class="i i-info-circle m-r" />
        UPI payment is not available when amount is greater than ₹{' '}
        {getFormattedAmount(UPI_AVL_LIMIT)}.
        <a class="m-l" href="https://razorpay.com/docs/subscriptions/">
          Learn more
          <i className="i i-external-link" />
        </a>
      </div>
    );
  }
}
