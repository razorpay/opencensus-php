import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';

import ListContainer from 'merchant/containers/ListContainer';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';
import PaymentsListFilter from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter';
import PaymentsTable from 'merchant/views/Transactions/v2/Payments/components/PaymentsTable';
import { onPaginate, onSearch } from 'merchant/views/Transactions/v2/common/utils';

class PaymentsList extends ListContainer {
  render() {
    const { count, skip } = this.state;
    const { loading, history } = this.props;
    return (
      <>
        <PaymentsListFilter onSubmit={onSearch(history)} loading={loading} />
        <PaymentsTable
          count={count}
          skip={skip}
          paginate={onPaginate(this.paginate)}
          {...this.props}
        />
      </>
    );
  }
}

export default withRouter(
  connect(
    (state) => ({
      ...state.payments,
    }),
    { fetchAll },
  )(PaymentsList),
);
