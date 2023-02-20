import { connect } from 'react-redux';
import { RZPFeatures } from 'merchant/helpers/data';
import { fetchMarketplacePayments as fetchAll } from 'merchant/reducers/collection';
import PaymentsList from 'merchant/views/Transactions/Payments/components/PaymentsList';
import { SelfServeActionPages } from 'common/constant/enums';

export default connect((state) => state.mpPayments, { fetchAll })((props) => (
  <PaymentsList
    {...props}
    quickTourFeature={RZPFeatures.ROUTE}
    isRoute
    selfServeActionsPage={SelfServeActionPages.RoutePayments}
  />
));
