import React, { Component } from 'react';
import { connect } from 'react-redux';
import OrderDetails from 'merchant/views/Transactions/Orders/components/OrderDetails';
import * as OrderActions from 'merchant/reducers/orders/details';
import { getEventCategoryFromPath } from 'common/utils/rzp-utils';
import { bindActionCreators } from 'redux';
import SuperCheckoutOrderDetails from 'merchant/views/Transactions/Orders/components/SuperCheckoutOrderDetails';

class OrderDetailsContainer extends Component {
  componentWillMount() {
    this.props.fetchItem(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  fetchOrderPayments = (order) => {
    return this.props.fetchOrderPayments(order);
  };

  componentDidMount() {
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);

    if (eventCategory)
      window.rzpAnalytics({
        eventCategory,
        eventAction: 'Open Details - Orders',
        eventLabel: `order_id=${id}`,
      });
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);

    if (eventCategory)
      window.rzpAnalytics({
        eventCategory,
        eventAction: 'Close Details - Orders',
        eventLabel: `order_id=${id}`,
      });
  }

  render() {
    const { loading, error, order, payments } = this.props;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error,
      };
    }

    if ('line_items_total' in order) {
      return (
        <SuperCheckoutOrderDetails
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

export default connect(
  (state) => state.order,
  (dispatch) => bindActionCreators(OrderActions, dispatch),
)(OrderDetailsContainer);
