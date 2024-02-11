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
import { paymentStatusVariantMap } from 'merchant/views/Transactions/v2/Payments/components/PaymentsTable/constants';

class PaymentsList extends ListContainer {
  render() {
    const { count, skip } = this.state;
    const {
      loading,
      history,
      location: { pathname, search },
      navigate,
      user: { isOmniChannelMerchant, pos_activation_status },
    } = this.props;
    return (
      <>
        <PaymentsListFilter onSubmit={onSearch(history)} loading={loading} />
        <PaymentsTable
          count={count}
          skip={skip}
          paginate={onPaginate(this.paginate)}
          isDisabled={({ status }) => !paymentStatusVariantMap[status]}
          shouldDisplaySourceChannel={isOmniChannelMerchant && !!pos_activation_status}
          onRowClick={(id) =>
            handleDetailsClick({
              navigate,
              itemId: id,
              baseUrl: TransactionsEntityRoute.PAYMENTS,
              initiatePage: TransactionsPagesMap[pathname],
              prevPath: pathname,
              prevSearch: search,
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
      user: state.session.user,
    }),
    { fetchAll },
  )(PaymentsList),
);
