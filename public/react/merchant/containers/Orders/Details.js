import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import OrderDetails from 'merchant/components/Orders/OrderDetails';
import * as OrderActions from 'merchant/modules/orders/details';

@withRouter
@connect(state => state.order, OrderActions)
export default class OrderDetailsContainer extends Component {
  componentWillMount() {
    let id = this.props.id || this.props.match.params.id;
    this.props.fetchOrder(id);
  }

  componentWillReceiveProps(nextProps) {
    let oldId = this.props.id || this.props.match.params.id;
    let newId = nextProps.id || nextProps.match.params.id;
    if (oldId !== newId) {
      this.props.fetchOrder(newId);
    }
  }

  fetchOrderPayments = order => {
    return this.props.fetchOrderPayments(order);
  };

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
