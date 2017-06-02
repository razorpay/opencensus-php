import { connect } from 'react-redux';
import PaymentsList from 'merchant/components/Payments/PaymentsList';
import { fetchAll } from 'merchant/modules/payments/list';

export default connect(state => state.payments, { fetchAll })(PaymentsList);
