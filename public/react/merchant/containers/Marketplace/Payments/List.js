import { connect } from 'react-redux';
import PaymentsList from 'merchant/components/Payments/PaymentsList';
import {
  fetchMarketplacePayments as fetchAll,
} from 'merchant/modules/payments/list';

export default connect(state => state.mpPayments, { fetchAll })(PaymentsList);
