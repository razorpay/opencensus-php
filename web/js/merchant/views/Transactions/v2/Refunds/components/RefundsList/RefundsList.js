import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';

import ListContainer from 'merchant/containers/ListContainer';
import { fetchRefunds as fetchAll } from 'merchant/reducers/collection';
import RefundsListFilter from 'merchant/views/Transactions/v2/Refunds/components/RefundsListFilter';
import RefundsTable from 'merchant/views/Transactions/v2/Refunds/components/RefundsTable';
import { handleDetailsClick } from 'merchant/views/Transactions/v2/common/components/Details/Details';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'merchant/views/Transactions/v2/common/constants';
import { onPaginate, onSearch } from 'merchant/views/Transactions/v2/common/utils';
import { useNavigate } from 'react-router-dom';

// TODO: @Shivam KS @Joel refactor it correctly
const RefundsListWrapper = (props) => {
  const navigate = useNavigate();
  return <RefundsList navigate={navigate} {...props} />;
};

class RefundsList extends ListContainer {
  render() {
    const {
      loading,
      history,
      navigate,
      location: { pathname },
    } = this.props;
    const { count, skip } = this.state;
    return (
      <>
        <RefundsListFilter count={count} onSubmit={onSearch(history)} loading={loading} />
        <RefundsTable
          count={count}
          skip={skip}
          paginate={onPaginate(this.paginate)}
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
    }),
    { fetchAll },
  )(RefundsListWrapper),
);
