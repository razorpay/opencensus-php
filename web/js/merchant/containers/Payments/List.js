import { connect } from 'react-redux';
import PaymentsList from 'merchant/components/Payments/PaymentsList';
import { fetchPayments as fetchAll } from 'merchant/modules/collection';

export default connect(state => state.payments, { fetchAll })(PaymentsList);
