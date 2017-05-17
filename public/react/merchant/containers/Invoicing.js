import React, { Component } from 'react';
import { Route, NavLink, withRouter } from 'react-router-dom';

import Invoices from 'merchant/containers/Invoices/List';
import Customers from 'merchant/containers/Customers/List';
import Items from 'merchant/containers/Items/List';

@withRouter
export default class InvoicingContainer extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="invoicing-header">
          <NavLink to="/app/invoices">Invoices</NavLink>
          <NavLink to="/app/customers">Customers</NavLink>
          <NavLink to="/app/items">Items</NavLink>
        </header>

        <Route path="/app/invoices" component={Invoices} />
        <Route path="/app/customers" component={Customers} />
        <Route path="/app/items" component={Items} />
      </tabbed-container>
    );
  }
}
