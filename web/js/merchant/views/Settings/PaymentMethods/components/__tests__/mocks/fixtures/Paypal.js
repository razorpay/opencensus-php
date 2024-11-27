import { render } from 'test-utils';
import Paypal from 'merchant/views/Settings/PaymentMethods/components/Paypal';

export const defaultProps = {
  instrument: {
    name: 'PayPal',
    description: 'Accept International Payments using PayPal on Razorpay Checkout',
    status: 'account_linkable',
    slug: 'paypal',
    icon: 'paypal',
    merchant_instrument_request_id: '',
    path: 'pg.international.paypal',
    created_at: 0,
  },
  user: {
    isCountryIndia: true,
    isOrgCurlec: false,
  },
  terminals: [],
  isIERevamp: true,
};

const defaultInitialState = {
  config: {
    paypal_terminals: [],
  },
};

export const renderApp = ({ props = {}, initialState = {} } = {}) =>
  render(<Paypal {...defaultProps} {...props} />, {
    initialState: {
      ...defaultInitialState,
      ...initialState,
    },
  });
