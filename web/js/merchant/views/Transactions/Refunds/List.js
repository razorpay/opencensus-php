import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import EntityTable from 'merchant/components/EntityTable';
import ListContainer from 'merchant/containers/ListContainer';
import RefundsListFilter from 'merchant/views/Transactions/Refunds/components/RefundsListFilter';
import { fetchRefunds as fetchAll } from 'merchant/reducers/collection';
import {
  refundId,
  paymentId,
  amount,
  createdAt,
  status,
} from 'common/ui/item/pair';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import EnableInstantRefundsModal from '../Payments/components/EnableInstantRefundsModal';
import { withRouter } from 'react-router-dom';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

@withRouter
@connect(state => state.refunds, { fetchAll, openModal })
export default class RefundsListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Refunds',
      eventAction: 'Go To - Refunds',
    });
  }

  onSearchAnalytics = params => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Refunds',
        eventAction: 'Search - Refunds',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Refunds',
      eventAction: 'Clear Search Params - Refunds',
    });
  };

  popupIfSettle() {
    if (this.props.location.hash === '#instantrefunds') {
      this.resetHash();
      this.enableInstantRefunds();
    }
  }

  resetHash = () => {
    this.props.history.push({
      pathname: this.props.history.location.pathname,
      hash: '',
    });
  };

  enableInstantRefunds = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Enable Now',
      eventLabel: `Announcement | Enable Now`,
    });
    this.props.openModal({
      component: <EnableInstantRefundsModal openedFrom={'Announcement'} />,
      size: 'small',
    });
  };

  componentDidUpdate() {
    this.popupIfSettle();
  }

  render() {
    const columns = [refundId, paymentId, amount, createdAt];
    columns.push(status);

    return (
      <div class="content-wrapper">
        <RefundsListFilter
          form="refundListFilter"
          count={this.state.count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <EntityTable
          title="Refunds"
          columns={columns}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
