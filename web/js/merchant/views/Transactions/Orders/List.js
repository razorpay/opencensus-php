import { Component } from 'react';
import { connect } from 'react-redux';
import DataTable from 'common/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import OrdersListFilter from 'merchant/views/Transactions/Orders/components/OrdersListFilter';
import { fetchOrders as fetchAll } from 'merchant/reducers/collection';
import { orderId, attempts, amount, status, receipt, createdAt } from 'common/ui/item/pair';
import analyticsService from '@commander/services/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';

@connect((state) => state.orders, { fetchAll })
export default class OrdersListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Orders',
      eventAction: 'Go To - Orders',
    });
  }

  onSearchAnalytics = (params) => {
    const label = getKeysSeparatedByPipe(params);
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
          onSubmit={(args) => {
            analyticsService.track({
              objectName: 'orders search',
              actionName: 'clicked',
              screen: 'transactions',
              properties: {
                ...args,
                location: 'orders',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.search(args)
              .then(() => {
                analyticsService.track({
                  objectName: 'orders search',
                  actionName: 'result',
                  screen: 'transactions',
                  properties: {
                    ...args,
                    resultsReturned: true,
                    requestStatus: 'success',
                    location: 'orders',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              })
              .catch((er) => {
                analyticsService.track({
                  objectName: 'orders search',
                  actionName: 'result',
                  screen: 'transactions',
                  properties: {
                    ...args,
                    resultsReturned: true,
                    status: 'failure',
                    failureReason: er.errors[0],
                    location: 'orders',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              });
          }}
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
