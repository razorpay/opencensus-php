import { render } from 'test-utils';
import { PaytmWallet } from 'merchant/views/Settings/PaymentMethods/components/PaytmWallet';
import { ACCOUNT_LINKABLE } from 'merchant/views/Settings/PaymentMethods/constants';

export const defaultProps = {
  paytm_production_status: ACCOUNT_LINKABLE,
  loading: true,
};

export const renderApp = ({ props = {} } = {}) =>
  render(<PaytmWallet {...defaultProps} {...props} />);
