import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import RefundsListFilter from 'merchant/components/Refunds/RefundsListFilter';
import { fetchRefunds as fetchAll } from 'rzp/modules/collection';
import { refundId, paymentId, amount, createdAt } from 'rzp/ui/item/pair';

import { stringifyQueryParamsWithPipe } from 'rzp/utils/rzp-utils';

@connect(state => state.refunds, { fetchAll })
export default class RefundsListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Refunds',
      eventAction: 'Go To - Refunds',
    });
  }

  onSearchAnalytics = params => {
    const label = stringifyQueryParamsWithPipe(params);
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

  render() {
    return (
      <div class="content-wrapper">
        <RefundsListFilter
          form="refundListFilter"
          count={this.state.count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <DataTable
          title="Refunds"
          columns={[refundId, paymentId, amount, createdAt]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
