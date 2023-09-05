import { connect } from 'react-redux';

import { withSplitzService } from 'common/splitz';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';
import { fetchFA, resetFA } from 'merchant/reducers/payments/details';
import PaymentsList from 'merchant/views/Transactions/v1/Payments/components/PaymentsList';

export default withSplitzService(
  connect(
    (state) => {
      return {
        ...state.payment,
        // need to make sure that the payments reducer is imported below to override loading state
        ...state.payments,
        user: state.session.user,
        user_segment_data: state.session.user_segment_data,
        terminalProviders: state.navigator.terminalProviders,
        selfServeActionsPage: 'Transactions.Payments',
      };
    },
    { fetchAll, fetchFA, resetFA },
  )(PaymentsList),
);
