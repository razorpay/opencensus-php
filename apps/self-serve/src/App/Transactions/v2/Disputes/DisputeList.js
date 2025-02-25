import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { withRouter } from '@libs/web-nexus/common/deprecated/withRouter';
import ListContainer from 'apps/self-serve/src/legacy/containers/ListContainer';
import { fetchDisputes as fetchAll } from 'apps/self-serve/src/bootstrap/Store/reducers/disputesReducer';
import { handleDetailsClick } from 'apps/self-serve/src/App/Transactions/v2/common/components/Details/Details';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { track } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { onSearch, onPaginate } from 'apps/self-serve/src/App/Transactions/v2/common/utils';
import DisputeListFilter from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeListFilter';
import DisputeListHeader from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeListHeader/DisputeListHeader';
import DisputeOverview from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeOverview';
import DisputesTable from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputesTable.tsx';
import withSplitzService from 'apps/self-serve/src/App/Transactions/v2/Disputes/hoc/withSplitzService';

class DisputeList extends ListContainer {
  onExport = () => {
    track({
      objectName: 'Download Disputes report',
      properties: { section: TransactionsPagesMap[this.props.history.location.pathname] },
    });
  };

  render() {
    const {
      history,
      loading,
      location: { pathname, search },
      navigate,
    } = this.props;

    return (
      <>
        <DisputeOverview />
        <div className="content-wrapper" data-testid="disputes-list">
          <DisputeListHeader mid={this.props.user.merchant.id} isFetchingTableData={loading} />
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
  return { ...state.disputes };
};

export default withSplitzService(
  connect(mapStateToProps, (dispatch) => bindActionCreators({ fetchAll }, dispatch))(
    withRouter(DisputeList),
  ),
);
