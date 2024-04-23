// TODO: Fix this comoonent, Currently its out of scope.
import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'shell/deprecated/withRouter';
// import ListContainer from 'merchant/containers/ListContainer';
// import { fetchRefunds as fetchAll } from 'merchant/reducers/collection';
// TODO: import/copy from web
import { useNavigate } from 'react-router-dom';
import RefundsListFilter from 'apps/self-serve/src/App/Transactions/v2/Refunds/components/RefundsListFilter';
import RefundsTable from 'apps/self-serve/src/App/Transactions/v2/Refunds/components/RefundsTable';
import { handleDetailsClick } from 'apps/self-serve/src/App/Transactions/v2/common/components/Details/Details';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { onPaginate, onSearch } from 'apps/self-serve/src/App/Transactions/v2/common/utils';

// TODO: @Shivam KS @Joel refactor it correctly
const RefundsListWrapper = (props) => {
  const navigate = useNavigate();
  return <RefundsList navigate={navigate} {...props} />;
};

class RefundsList {
  render() {
    const {
      loading,
      history,
      navigate,
      location: { pathname },
      user: { isOmniChannelMerchant, pos_activation_status, isOmniEnabledMerchant },
    } = this.props;
    const { count, skip } = this.state;
    const isOmniView = isOmniEnabledMerchant || (!!pos_activation_status && isOmniChannelMerchant);

    return (
      <>
        <RefundsListFilter count={count} onSubmit={onSearch(history)} loading={loading} />
        <RefundsTable
          count={count}
          skip={skip}
          paginate={onPaginate(this.paginate)}
          isOmniView={isOmniView}
          onRowClick={(id) =>
            handleDetailsClick({
              navigate,
              itemId: id,
              baseUrl: TransactionsEntityRoute.REFUNDS,
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
      ...state.refunds,
      user: state.session.user,
    }),
    // { fetchAll },
  )(RefundsListWrapper),
);
