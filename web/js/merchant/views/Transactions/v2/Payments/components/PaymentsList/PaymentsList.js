import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';

import ListContainer from 'merchant/containers/ListContainer';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';
import PaymentsListFilter from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter';
import PaymentsTable from 'merchant/views/Transactions/v2/Payments/components/PaymentsTable';
import { handleDetailsClick } from 'merchant/views/Transactions/v2/common/components/Details/Details';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'merchant/views/Transactions/v2/common/constants';
import { onPaginate, onSearch } from 'merchant/views/Transactions/v2/common/utils';

class PaymentsList extends ListContainer {
  render() {
    const { count, skip } = this.state;
    const {
      loading,
      history,
      location: { pathname },
      navigate,
    } = this.props;
    return (
      <>
        <PaymentsListFilter onSubmit={onSearch(history)} loading={loading} />
        <PaymentsTable
          count={count}
          skip={skip}
          paginate={onPaginate(this.paginate)}
          onRowClick={(id) =>
            handleDetailsClick({
              navigate,
              itemId: id,
              baseUrl: TransactionsEntityRoute.PAYMENTS,
              initiatePage: TransactionsPagesMap[pathname],
              prevPath: pathname,
            })
          }
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
