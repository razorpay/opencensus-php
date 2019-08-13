import { connect } from 'react-redux';

import { RZPFeatures } from 'rzp/utils/constants';

import { fetchMarketplacePayments as fetchAll } from 'merchant/modules/collection';

import PaymentsList from 'merchant/components/Payments/PaymentsList';

export default connect(state => state.mpPayments, { fetchAll })(props => (
  <PaymentsList {...props} quickTourFeature={RZPFeatures.ROUTE} />
));
