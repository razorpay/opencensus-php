import React, { Component } from 'react';
import { connect } from 'react-redux';

import { NavLink, withRouter } from 'react-router-dom';

import Payments from 'merchant/containers/Payments/List';
import Refunds from 'merchant/containers/Refunds/List';
import Orders from 'merchant/containers/Orders/List';

const transactionsRoutes = {
  '/refunds': <Refunds />,
  '/orders': <Orders />,
};

@withRouter
export default class TransactionsContainer extends Component {
  render() {
    let content =
      transactionsRoutes[this.props.location.pathname] || <Payments />;
    return (
      <div>
        <NavLink to="/payments">Payments</NavLink>
        <NavLink to="/refunds">Refunds</NavLink>
        <NavLink to="/orders">Orders</NavLink>
        {content}
      </div>
    );
  }
}
