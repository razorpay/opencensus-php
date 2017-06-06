import { connect } from 'react-redux';
import PaymentsList from 'merchant/components/Payments/PaymentsList';
import { fetchPayments as fetchAll, destroy } from 'rzp/modules/collection';

export default connect(state => state.collection, { fetchAll, destroy })(
  PaymentsList
);
