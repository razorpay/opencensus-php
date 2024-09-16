import React from 'react';
import { Box, Button, DownloadIcon, Heading } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { withSplitzService } from 'common/splitz';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchDisputes as fetchAll } from 'merchant/reducers/collection';
import DisputeListFilter from 'merchant/views/Transactions/v2/Disputes/components/DisputeListFilter';
import DisputeOverview from 'merchant/views/Transactions/v2/Disputes/components/DisputeOverview';
import DisputesTable from 'merchant/views/Transactions/v2/Disputes/components/DisputesTable.tsx';
import { handleDetailsClick } from 'merchant/views/Transactions/v2/common/components/Details/Details';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'merchant/views/Transactions/v2/common/constants';
import { track } from 'merchant/views/Transactions/v2/common/tracking';
import { onSearch } from 'merchant/views/Transactions/v2/common/utils';

import { getErrorMessage } from './utils';

class DisputeList extends ListContainer {
  onExport = () => {
    getErrorMessage();
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
  return { mode: state.session.mode, user: state.session.user, ...state.disputes };
};

export default withSplitzService(
  connect(mapStateToProps, (dispatch) => bindActionCreators({ fetchAll }, dispatch))(
    withRouter(DisputeList),
  ),
);
