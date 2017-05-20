import React, { Component } from 'react';
import { connect } from 'react-redux';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import Header from 'rzp/ui/Header';
import PaymentsList from 'merchant/components/Payments/PaymentsList';
import PaymentsDetails from 'merchant/containers/Payments/Details';
import ListContainer from 'merchant/containers/ListContainer';
import PaymentsListFilter
  from 'merchant/components/Payments/PaymentsListFilter';
import { fetchPayments } from 'merchant/modules/payments/list';
import { openSlider } from 'rzp/modules/slider';

@connect(state => state.payments, { fetchPayments, openSlider })
export default class PaymentsListContainer extends ListContainer {
  state = {
    orders: {},
    hasOrders: false,
  };

  fetchEntityList(params) {
    return this.props.fetchPayments(params);
  }

  getOrderId(payment) {
    /* Merchant's custom defined order IDs */
    let notes = payment.notes;
    if (!Object.keys(notes).length) {
      return null;
    }
    let validOrderIds = ['order_id', 'orderId'];
    let orderIdSuffix = '_order_id';
    for (let i = validOrderIds.length - 1; i >= 0; i--) {
      let validOrderId = validOrderIds[i];
      if (typeof notes[validOrderId] !== 'undefined') {
        return notes[validOrderId];
      }
    }
    // Now we try for suffixes
    let suffixLength = orderIdSuffix.length;
    for (let key in notes) {
      let index = -1 * suffixLength;
      let suffix = key.substr(index);
      if (suffix === orderIdSuffix) {
        return notes[key];
      }
    }
    // We couldn't find anything in payments
    return null;
  }

  componentWillReceiveProps({ payments = [] }) {
    let orders = {};
    for (let i = payments.length - 1; i >= 0; i--) {
      let payment = payments[i];
      let orderId = this.getOrderId(payment);
      if (orderId !== null) {
        orders[payment.id] = orderId;
      }
    }
    this.setState({
      orders,
      hasOrders: !!Object.keys(orders).length,
    });
  }

  showPaymentDetails = payment => {
    this.props.history.push(`/app/payments/${payment.id}`, {
      notify: false,
    });
    this.props.openSlider({
      component: <PaymentsDetails id={payment.id} />,
    });
  };

  render() {
    let { loading, payments = [], error } = this.props;

    return (
      <div class="content-wrapper">
        <PaymentsListFilter
          form="paymentListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        {error && <Alert type="error" message={error} />}

        <PaymentsList
          payments={payments}
          isLoading={loading}
          hasOrders={this.state.hasOrders}
          orders={this.state.orders}
          onPaymentClick={this.showPaymentDetails}
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={payments.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
