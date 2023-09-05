import React, { Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { withSplitzService } from 'common/splitz';
import { getEventCategoryFromPath } from 'common/utils/rzp-utils';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import * as OrderActions from 'merchant/reducers/orders/details';
import MagicCheckoutOrderDetails from 'merchant/views/Transactions/v1/Orders/components/MagicCheckoutOrderDetails';
import OrderDetails from 'merchant/views/Transactions/v1/Orders/components/OrderDetails';
import { getSelfServeSuccessData } from 'merchant/views/Transactions/v1/utils';
import { isTransactionsV2Enabled } from 'merchant/views/Transactions/v2/common/utils';

class OrderDetailsContainer extends Component {
  fetchItem(id) {
    const { fetchItem, splitz } = this.props;
    fetchItem(id).then((response) => {
      const selfServeSuccessData = getSelfServeSuccessData(
        'Order Details Fetched',
        'Order Details',
        splitz,
      );
      selfServeTrackSuccess(selfServeSuccessData);
      return response;
    });
  }

  UNSAFE_componentWillReceiveProps({ id }) {
    if (this.props.id !== id) {
      const { ordersListItems, fetchMagicCheckoutItem } = this.props;

      this.isMagicCheckoutOrder(ordersListItems, id)
        ? fetchMagicCheckoutItem(id)
        : this.fetchItem(id);
    }
  }

  isMagicCheckoutOrder = (ordersListItems, id) =>
    ordersListItems?.find((order) => order?.id === id && 'line_items_total' in order);

  fetchOrderPayments = (order) => {
    return this.props.fetchOrderPayments(order);
  };

  componentDidMount() {
    const { closeUrl, id, ordersListItems, fetchMagicCheckoutItem } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);

    this.isMagicCheckoutOrder(ordersListItems, id)
      ? fetchMagicCheckoutItem(id)
      : this.fetchItem(id);

    if (eventCategory) {
      window.rzpAnalytics?.({
        eventCategory,
        eventAction: 'Open Details - Orders',
        eventLabel: `order_id=${id}`,
      });
    }
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);

    if (eventCategory)
      window.rzpAnalytics?.({
        eventCategory,
        eventAction: 'Close Details - Orders',
        eventLabel: `order_id=${id}`,
      });
  }

  render() {
    const { order: orderInfo, user, splitz } = this.props;
    const { loading, error, order, payments } = orderInfo || {};
    let statusMsg = {};
    const version = isTransactionsV2Enabled(splitz, user) ? 'v2' : undefined;

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error,
      };
    }

    if ('line_items_total' in order) {
      return (
        <MagicCheckoutOrderDetails
          order={order}
          payments={payments}
          onTogglePayments={this.fetchOrderPayments}
          isLoading={loading}
          statusMsg={statusMsg}
          version={version}
        />
      );
    }
    return (
      <OrderDetails
        order={order}
        payments={payments}
        onTogglePayments={this.fetchOrderPayments}
        isLoading={loading}
        statusMsg={statusMsg}
        version={version}
      />
    );
  }
}

const mapStateToProps = (state) => {
  return {
    ordersListItems: state.orders?.items,
    order: state.order,
    user: state.session.user,
  };
};

export default withSplitzService(
  connect(mapStateToProps, (dispatch) => bindActionCreators(OrderActions, dispatch))(
    OrderDetailsContainer,
  ),
);
