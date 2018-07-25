import { connect } from 'react-redux';
import PaymentsList from 'merchant/components/Payments/PaymentsList';
import { fetchMarketplacePayments as fetchAll } from 'merchant/modules/collection';

export default connect(state => state.mpPayments, { fetchAll })(PaymentsList);
