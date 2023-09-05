import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';

import ListContainer from 'merchant/containers/ListContainer';
import { fetchRefunds as fetchAll } from 'merchant/reducers/collection';
import RefundsListFilter from 'merchant/views/Transactions/v2/Refunds/components/RefundsListFilter';
import RefundsTable from 'merchant/views/Transactions/v2/Refunds/components/RefundsTable';
import { onPaginate, onSearch } from 'merchant/views/Transactions/v2/common/utils';

class RefundsList extends ListContainer {
  render() {
    const { loading, history } = this.props;
    const { count, skip } = this.state;
    return (
      <>
        <RefundsListFilter count={count} onSubmit={onSearch(history)} loading={loading} />
        <RefundsTable
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
      ...state.refunds,
    }),
    { fetchAll },
  )(RefundsList),
);
