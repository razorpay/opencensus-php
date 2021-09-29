import { connect } from 'react-redux';
import PaymentsList from 'merchant/views/Transactions/Payments/components/PaymentsList';
import { fetchFA } from 'merchant/reducers/payments/details';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';

export default connect(
  (state) => {
    return {
      ...state.payment,
      // need to make sure that the payments reducer is imported below to override loading state
      ...state.payments,
      user: state.session.user,
    };
  },
  { fetchAll, fetchFA },
)(PaymentsList);
