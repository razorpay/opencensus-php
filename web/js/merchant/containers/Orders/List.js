import { Component } from 'react';
import { connect } from 'react-redux';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import OrdersListFilter from 'merchant/components/Orders/OrdersListFilter';
import { fetchOrders as fetchAll } from 'rzp/modules/collection';
import {
  orderId,
  attempts,
  amount,
  status,
  receipt,
  createdAt,
} from 'rzp/ui/item/pair';

import { stringifyQueryParamsWithPipe } from 'rzp/utils/rzp-utils';

@connect(state => state.orders, { fetchAll })
export default class OrdersListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Orders',
      eventAction: 'Go To - Orders',
    });
  }

  onSearchAnalytics = params => {
    const label = stringifyQueryParamsWithPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Orders',
        eventAction: 'Search - Orders',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Orders',
      eventAction: 'Clear Search Params - Orders',
    });
  };

  render() {
    return (
      <div class="content-wrapper">
        <OrdersListFilter
          form="orderListFilter"
          count={this.state.count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <DataTable
          title="Orders"
          columns={[orderId, amount, attempts, receipt, createdAt, status]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
