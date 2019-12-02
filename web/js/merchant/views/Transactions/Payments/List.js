import { connect } from 'react-redux';
import PaymentsList from 'merchant/views/Transactions/Payments/components/PaymentsList';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';

export default connect(state => state.payments, { fetchAll })(PaymentsList);
