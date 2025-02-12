import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import DataTable from 'common/ui/Table/DataTable';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import ListContainer from 'merchant/containers/ListContainer';
import ReversalsListFilter from 'merchantLA/components/Marketplace/ReversalsListFilter';
import { fetchReversals as fetchAll } from 'merchantLA/reducers/collection';
import {
  reversalId,
  transferId,
  customerRefundId,
  amount,
  createdAt,
} from 'merchantLA/utils/item/pair';

import setGaTrack from './ga';

const gaEvents = setGaTrack('LA Dashboard - Reversals');

class ReversalsTable extends ListContainer {
  onSearchAnalytics = (params) => {
    const { pathname } = this.props.location;
    if (pathname && pathname.indexOf('route') < 0) {
      const label = getKeysSeparatedByPipe(params);
      if (label && label.length > 0) {
        gaEvents.trackSearchAnalytics(label);
      }
    }
  };

  onClearAnalytics = () => {
    const { pathname } = this.props.location;
    if (pathname && pathname.indexOf('route') < 0) {
      gaEvents.trackClearAnalytics();
    }
  };

  render() {
    const isRefundsAllowed = this.props.user.isAllowedLARefunds;
    return (
      <div className="revsersals-list-container content-wrapper">
        <ReversalsListFilter
          form="reversalsListFilter"
          count={this.state.count}
          onSubmit={this.search}
          user={this.props.user}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <DataTable
          title="Reversals"
          columns={
            isRefundsAllowed
              ? [reversalId, customerRefundId, transferId, amount, createdAt]
              : [reversalId, transferId, amount, createdAt]
          }
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}

export default connect(
  (state) => ({
    ...state.reversals,
    user: state.session.user,
  }),
  { fetchAll },
)(withRouter(ReversalsTable));
