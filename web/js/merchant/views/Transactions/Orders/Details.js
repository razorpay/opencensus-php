import React, { Component } from 'react';
import { connect } from 'react-redux';
import OrderDetails from 'merchant/views/Transactions/Orders/components/OrderDetails';
import * as OrderActions from 'merchant/reducers/orders/details';
import { getEventCategoryFromPath } from 'common/utils/rzp-utils';
import { bindActionCreators } from 'redux';
import MagicCheckoutOrderDetails from 'merchant/views/Transactions/Orders/components/MagicCheckoutOrderDetails';

class OrderDetailsContainer extends Component {
  UNSAFE_componentWillReceiveProps({ id }) {
    if (this.props.id !== id) {
      const { ordersListItems, fetchItem, fetchMagicCheckoutItem } = this.props;

      this.isMagicCheckoutOrder(ordersListItems, id) ? fetchMagicCheckoutItem(id) : fetchItem(id);
    }
  }

  isMagicCheckoutOrder = (ordersListItems, id) =>
    ordersListItems?.find((order) => order?.id === id && 'line_items_total' in order);

  fetchOrderPayments = (order) => {
    return this.props.fetchOrderPayments(order);
  };

  componentDidMount() {
    const { closeUrl, id, ordersListItems, fetchItem, fetchMagicCheckoutItem } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);

    this.isMagicCheckoutOrder(ordersListItems, id) ? fetchMagicCheckoutItem(id) : fetchItem(id);

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
    const { order: orderInfo } = this.props;
    const { loading, error, order, payments } = orderInfo || {};
    let statusMsg = {};

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
      />
    );
  }
}

const mapStateToProps = (state) => {
  return {
    ordersListItems: state.orders?.items,
    order: state.order,
  };
};

export default connect(mapStateToProps, (dispatch) => bindActionCreators(OrderActions, dispatch))(
  OrderDetailsContainer,
);
