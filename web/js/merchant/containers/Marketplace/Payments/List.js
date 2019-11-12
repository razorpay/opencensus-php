import { connect } from 'react-redux';

import { RZPFeatures } from 'common/utils/constants';

import { fetchMarketplacePayments as fetchAll } from 'merchant/reducers/collection';

import PaymentsList from 'merchant/components/Payments/PaymentsList';

export default connect(state => state.mpPayments, { fetchAll })(props => (
  <PaymentsList {...props} quickTourFeature={RZPFeatures.ROUTE} isRoute />
));
