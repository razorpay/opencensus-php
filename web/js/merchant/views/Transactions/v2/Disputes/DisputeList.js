import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { withSplitzService } from 'common/splitz';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchDisputes as fetchAll } from 'merchant/reducers/collection';
import DisputeListFilter from 'merchant/views/Transactions/v2/Disputes/components/DisputeListFilter';
import DisputeListHeader from 'merchant/views/Transactions/v2/Disputes/components/DisputeListHeader/DisputeListHeader';
import DisputeOverview from 'merchant/views/Transactions/v2/Disputes/components/DisputeOverview';
import DisputesTable from 'merchant/views/Transactions/v2/Disputes/components/DisputesTable.tsx';
import { handleDetailsClick } from 'merchant/views/Transactions/v2/common/components/Details/Details';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'merchant/views/Transactions/v2/common/constants';
import { onSearch, onPaginate } from 'merchant/views/Transactions/v2/common/utils';

class DisputeList extends ListContainer {
  render() {
    const {
      history,
      loading,
      location: { pathname, search },
      navigate,
      user,
      items,
    } = this.props;

    return (
      <>
        <DisputeOverview />
        <div className="content-wrapper" data-testid="disputes-list">
          <DisputeListHeader
            mid={user.merchant.id}
            isFetchingTableData={loading}
            isDownloadDisabled={items.length === 0}
          />
          <DisputeListFilter onSubmit={onSearch(history)} loading={loading} />
          <DisputesTable
            title="Disputes"
            loading={loading}
            count={this.state.count}
            skip={this.state.skip}
            paginate={onPaginate(this.paginate)}
            onRowClick={({ id, rowData }) =>
              handleDetailsClick({
                navigate,
                itemId: id,
                baseUrl: TransactionsEntityRoute.DISPUTES,
                initiatePage: TransactionsPagesMap[pathname],
                prevPath: pathname,
                prevSearch: search,
                rowData,
              })
            }
            {...this.props}
          />
        </div>
      </>
    );
  }
}

const mapStateToProps = (state) => {
  return { mode: state.session.mode, user: state.session.user, ...state.disputes };
};

export default withSplitzService(
  connect(mapStateToProps, (dispatch) => bindActionCreators({ fetchAll }, dispatch))(
    withRouter(DisputeList),
  ),
);
