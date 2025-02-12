import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import Amount from 'common/ui/Amount';
import Popover, { PopoverBody } from 'common/ui/Popover';
import DataTable from 'common/ui/Table/DataTable';
import { classList, pluralize, getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import ListContainer from 'merchant/containers/ListContainer';
import TransfersListFilter from 'merchantLA/components/Marketplace/TransfersListFilter';
import TestModeBanner from 'merchantLA/containers/TestModeBanner';
import { fetchTransfers as fetchAll } from 'merchantLA/reducers/collection';
import {
  transferId,
  parentPaymentId,
  amount,
  createdAt,
  settlementStatus,
} from 'merchantLA/utils/item/pair';

const helperCues = {
  title: '',
  value: (item) => {
    const hasReversals = item.amount_reversed > 0;

    let notesMsg = 'No Notes';
    const notesLength = item.notes && Object.keys(item.notes).length;

    if (notesLength) {
      notesMsg = `${notesLength} ${pluralize('Note', notesLength)} attached`;
    }

    return (
      <div style={{ color: 'green' }}>
        <span>
          <i
            className={classList('i i-undo cue', hasReversals ? 'cue--active' : 'cue--inactive')}
          />
          <Popover align="top">
            <PopoverBody>
              <div>
                {hasReversals ? (
                  <span>
                    <Amount value={item.amount_reversed} /> amount reversed
                  </span>
                ) : (
                  'No Reversals'
                )}
              </div>
            </PopoverBody>
          </Popover>
        </span>

        <span>
          <i
            className={classList('i i-notes cue', notesLength ? 'cue--active' : 'cue--inactive')}
          />
          <Popover align="top">
            <PopoverBody>
              <div>{notesMsg}</div>
            </PopoverBody>
          </Popover>
        </span>
      </div>
    );
  },
};

class TransfersListContainer extends ListContainer {
  onSearchAnalytics = (params) => {
    const { pathname } = this.props.location;
    if (pathname && pathname.indexOf('route') < 0) {
      const label = getKeysSeparatedByPipe(params);
      if (label && label.length > 0) {
        window.rzpAnalytics({
          eventCategory: 'LA Dashboard - Transfers',
          eventAction: 'Search - Refunds',
          eventLabel: label,
        });
      }
    }
  };

  onClearAnalytics = () => {
    const { pathname } = this.props.location;
    if (pathname && pathname.indexOf('route') < 0) {
      window.rzpAnalytics({
        eventCategory: 'LA Dashboard - Transfers',
        eventAction: 'Clear Search Params - Search params',
      });
    }
  };

  render() {
    const columns = this.props.isShowParentPaymentIdEnabled
      ? [transferId, parentPaymentId, amount, createdAt, settlementStatus, helperCues]
      : [transferId, amount, createdAt, settlementStatus, helperCues];

    return (
      <div className="transfers-list">
        <tabbed-container>
          <header id="marketplace-header">
            <NavLink to="/transfers">Transfers</NavLink>
          </header>
          <TestModeBanner />
          <content>
            <div className="content-wrapper">
              <TransfersListFilter
                form="transfersListFilter"
                count={this.state.count}
                onSubmit={this.search}
                onSearchAnalytics={this.onSearchAnalytics}
                onClearAnalytics={this.onClearAnalytics}
                isShowParentPaymentIdEnabled={this.props.isShowParentPaymentIdEnabled}
              />

              <DataTable
                title="Transfers"
                columns={columns}
                count={this.state.count}
                skip={this.state.skip}
                paginate={this.paginate}
                {...this.props}
              />
            </div>
          </content>
        </tabbed-container>
      </div>
    );
  }
}

export default connect(
  (state) => ({
    ...state.transfers,
    isShowParentPaymentIdEnabled: state.session.user.isShowParentPaymentIdEnabled,
  }),
  { fetchAll },
)(withRouter(TransfersListContainer));
