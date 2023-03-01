import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import DataTable from 'common/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import OrdersListFilter from 'merchant/views/Transactions/Orders/components/OrdersListFilter';
import { fetchOrders as fetchAll } from 'merchant/reducers/collection';
import { orderId, attempts, amount, status, receipt, createdAt } from 'common/ui/item/pair';
import { analyticsTrack } from 'common/utils/analytics';
import { getKeysSeparatedByPipe, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import {
  SHOPIFY_RECEIPT_PREFIX,
  ORDER_PENDING,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { selfServerTrack, selfServeTrackResult } from 'merchant/views/Transactions/AnalyticsTrack';
import { makeIdLink } from 'merchant/views/Transactions/Orders/Utils';
import { SelfServeActionPages } from 'common/constant/enums';

const _orderId = (initiatePage = SelfServeActionPages.TransactionsOrders) => {
  return {
    title: orderId.title,
    value: (item) => {
      const intermediateElement = makeIdLink('order')(item, initiatePage);
      return <div>{intermediateElement}</div>;
    },
  };
};

class OrdersListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Orders',
      eventAction: 'Go To - Orders',
    });
  }

  onSearchAnalytics = (params) => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Orders',
        eventAction: 'Search - Orders',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Orders',
      eventAction: 'Clear Search Params - Orders',
    });
  };

  //  For shopify if receipt No. contains rcptid prefixed we need to change as Order Pending
  getUpdatedReceipt = () => {
    const { items } = this.props;
    return items.map((item) => {
      const newItem = { ...item };
      if (item?.receipt?.includes(SHOPIFY_RECEIPT_PREFIX)) {
        newItem.receipt = ORDER_PENDING;
      }
      return newItem;
    });
  };

  getColumns = () => {
    const { selfServeActionsPage } = this.props;
    const initiatePage = selfServeActionsPage
      ? selfServeActionsPage
      : SelfServeActionPages.TransactionsOrders;
    const cols = [_orderId(initiatePage), amount, attempts, receipt, createdAt, status];
    return cols;
  };

  render() {
    const updatedItems = this.getUpdatedReceipt();
    return (
      <div class="content-wrapper" data-testId="orders-list">
        <OrdersListFilter
          form="orderListFilter"
          count={this.state.count}
          onSubmit={(args) => {
            selfServerTrack({ type: 'order', actionType: 'search' });
            analyticsTrack({
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
                if (args.status) {
                  selfServeTrackResult({ type: 'order', actionType: 'filter' });
                }
                selfServeTrackResult({ type: 'order', actionType: 'search' });
                analyticsTrack({
                  objectName: 'orders search',
                  actionName: 'result',
                  screen: 'transactions',
                  properties: {
                    id: args.id,
                    orderStatus: args.status,
                    emailFilled: Boolean(args.email),
                    notesFilled: Boolean(args.notes),
                    count: args.count,
                    resultsReturned: true,
                    status: 'success',
                    location: 'orders',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              })
              .catch((er) => {
                analyticsTrack({
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
          columns={this.getColumns()}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
          items={updatedItems}
        />
      </div>
    );
  }
}

export default connect(
  (state) => state.orders,
  (dispatch) => bindActionCreators({ fetchAll }, dispatch),
)(OrdersListContainer);
