import React, { Component } from 'react';
import Modal from 'react-modal';

import { NavLink, withRouter } from 'react-router-dom';

import PaymentsList from 'merchant/containers/Payments/List';
import PaymentDetails from 'merchant/containers/Payments/Details';
import RefundsList from 'merchant/containers/Refunds/List';
import RefundDetails from 'merchant/containers/Refunds/Details';
import OrdersList from 'merchant/containers/Orders/List';
import OrderDetails from 'merchant/containers/Orders/Details';

const components = {
  refunds: [<RefundsList />, id => <RefundDetails id={id} />],
  orders: [<OrdersList />, id => <OrderDetails id={id} />],
  payments: [<PaymentsList />, id => <PaymentDetails id={id} />],
};

@withRouter
export default class TransactionsContainer extends Component {
  render() {
    let urlFragments = this.props.location.pathname.match(
      /\/app\/(\w+)\/?(\w*)/
    );
    let component = components[urlFragments[1]];
    let entityId = urlFragments[2];
    return (
      <tabbed-container>
        <header>
          <NavLink to="/app/payments">Payments</NavLink>
          <NavLink to="/app/refunds">Refunds</NavLink>
          <NavLink to="/app/orders">Orders</NavLink>
          <NavLink to="/batch-refunds">Batch Refunds</NavLink>
        </header>
        {component[0]}
        <Modal
          onRequestClose={() => {
            location.hash = `#/app/${urlFragments[1]}`;
          }}
          isOpen={!!entityId}
          closeTimeoutMS={300}
          contentLabel="💩"
          className="side-pane"
          overlayClassName="main-content side-overlay"
        >
          {entityId && component[1](entityId)}
        </Modal>
      </tabbed-container>
    );
  }
}
