import { connect } from 'react-redux';
import ReversalsListFilter from 'merchantLA/components/Marketplace/ReversalsListFilter';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchReversals as fetchAll } from 'merchantLA/modules/collection';
import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';

import {
  reversalId,
  transferId,
  customerRefundId,
  amount,
  createdAt,
} from 'merchantLA/utils/item/pair';

const gaEvents = setGaTrack('LA Dashboard - Reversals');

@connect(
  state => ({
    ...state.reversals,
    user: state.session.user,
  }),
  { fetchAll }
)
export default class ReversalsTable extends ListContainer {
  onSearchAnalytics = params => {
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
      <div class="revsersals-list-container content-wrapper">
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
