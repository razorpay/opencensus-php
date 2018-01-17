import React, { Component } from 'react';
import { connect } from 'react-redux';
import OrderDetails from 'merchant/components/Orders/OrderDetails';
import * as OrderActions from 'merchant/modules/orders/details';
import { getEventCategoryFromPath } from 'rzp/utils/rzp-utils';

@connect(state => state.order, OrderActions)
export default class OrderDetailsContainer extends Component {
  componentWillMount() {
    this.props.fetchItem(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  fetchOrderPayments = order => {
    return this.props.fetchOrderPayments(order);
  };

  componentDidMount() {
    const { closeUrl, id } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Open Details - Orders',
        eventLabel: `order_id=${id}`,
      });
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Close Details - Orders',
        eventLabel: `order_id=${id}`,
      });
  }

  render() {
    let { loading, error, order, payments } = this.props;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error,
      };
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
