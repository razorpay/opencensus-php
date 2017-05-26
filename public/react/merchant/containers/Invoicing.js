import React, { Component } from 'react';
import { Route, NavLink, withRouter } from 'react-router-dom';

import Invoices from 'merchant/containers/Invoices/List';
import Items from 'merchant/containers/Items/List';

@withRouter
export default class InvoicingContainer extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="invoicing-header">
          <NavLink to="/invoices">Invoices</NavLink>
          <NavLink to="/items">Items</NavLink>
        </header>

        <Route path="/invoices" component={Invoices} />
        <Route path="/items" component={Items} />
      </tabbed-container>
    );
  }
}
