import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'shell/deprecated/withRouter';
import { withZustand } from 'shell/commonStore';
import { compose } from 'redux';

import { ListContainer } from '@dashboard/shared-ui/containers';
import { fetchPayments as fetchAll } from 'apps/self-serve/src/bootstrap/Store/reducers/paymentsReducer';
import PaymentsListFilter from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsListFilter';
import PaymentsTable from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsTable';
import { handleDetailsClick } from 'apps/self-serve/src/App/Transactions/v2/common/components/Details/Details';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { onPaginate, onSearch } from 'apps/self-serve/src/App/Transactions/v2/common/utils';
import { paymentStatusVariantMap } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsTable/constants';

class PaymentsList extends ListContainer {
  render() {
    const { count, skip } = this.state;
    const {
      loading,
      history,
      location: { pathname },
      navigate,
      store,
    } = this.props;

    const {
      session: {
        user: { isOmniChannelMerchant, pos_activation_status, isOmniEnabledMerchant },
      },
    } = store;

    const isOmniView = isOmniEnabledMerchant || (!!pos_activation_status && isOmniChannelMerchant);

    return (
      <>
        <PaymentsListFilter onSubmit={onSearch(history)} loading={loading} />
        <PaymentsTable
          count={count}
          skip={skip}
          paginate={onPaginate(this.paginate)}
          isDisabled={({ status }) => !paymentStatusVariantMap[status]}
          isOmniView={isOmniView}
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

export default compose(
  withRouter,
  connect(
    (state) => ({
      ...state.payments,
    }),
    { fetchAll },
  ),
  (component) => withZustand(component, ['session']),
)(PaymentsList);
