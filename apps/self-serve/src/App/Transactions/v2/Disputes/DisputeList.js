import { Box, Button, DownloadIcon, Heading } from '@razorpay/blade/components';
import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { withRouter } from 'shell/deprecated/withRouter';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchDisputes as fetchAll } from 'apps/self-serve/src/bootstrap/Store/reducers/disputesReducer';
import DisputeListFilter from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeListFilter';
import DisputeOverview from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeOverview';
import DisputesTable from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputesTable.tsx';
import { handleDetailsClick } from 'apps/self-serve/src/App/Transactions/v2/common/components/Details/Details';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { track } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { onSearch } from 'apps/self-serve/src/App/Transactions/v2/common/utils';
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
          <Box
            display="flex"
            justifyContent="space-between"
            alignItems="center"
            marginBottom="spacing.6"
          >
            <Heading>Disputes</Heading>
            <Button
              variant="tertiary"
              onClick={this.onExport}
              icon={DownloadIcon}
              isDisabled={loading}
            />
          </Box>
          <DisputeListFilter onSubmit={onSearch(history)} loading={loading} />
          <DisputesTable
            title="Disputes"
            loading={loading}
            count={this.state.count}
            skip={this.state.skip}
            paginate={this.paginate}
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
