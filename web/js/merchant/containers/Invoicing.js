import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/containers/TestModeBanner';

import Invoices from 'merchant/containers/Invoices/List';
import Customers from 'merchant/containers/Customers/List';
import Items from 'merchant/containers/Items/List';

@withRouter
@connect(state => state.session)
export default class InvoicingContainer extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="invoicing-header">
          <NavLink to="/invoices">Invoices</NavLink>
          <ShowWhen notMyRole="sellerapp" featureEnabled="Invoice">
            <span>
              <NavLink to="/items">Items</NavLink>
            </span>
          </ShowWhen>
        </header>
        <TestModeBanner />
        <content>
          <Route path="/invoices" component={Invoices} />
          <Route path="/items" component={Items} />
          <Route path="/customers" component={Customers} />
        </content>
      </tabbed-container>
    );
  }
}
