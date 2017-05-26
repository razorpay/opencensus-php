import React, { Component } from 'react';
import { NavLink, Switch, Route, withRouter, Redirect } from 'react-router-dom';

import Home from 'merchant/containers/HomeContainer';
import Transactions from 'merchant/containers/Transactions';
import Settlements from 'merchant/containers/Settlements/List';
import PaymentLinks from 'merchant/containers/PaymentLinks/List';
import InvoicingContainer from 'merchant/containers/Invoicing';
import InvoicesNew from 'merchant/containers/Invoices/New';
import Customers from 'merchant/containers/Customers/List';
import Accounts from 'merchant/containers/Accounts/List';
import Reports from 'merchant/containers/Reports';
import TeamManagement from 'merchant/containers/Team';
import MyAccount from 'merchant/containers/MyAccount';
import Settings from 'merchant/containers/Settings';

import SettlementDetails from 'merchant/containers/Settlements/Details';
import PaymentLinkDetails from 'merchant/containers/PaymentLinks/Details';
import PaymentsDetails from 'merchant/containers/Payments/Details';
import RefundDetails from 'merchant/containers/Refunds/Details';
import OrderDetails from 'merchant/containers/Orders/Details';

const getBaseView = location => {
  return (
    <Switch location={location}>
      <Route path="/payments" component={Transactions} />
      <Route path="/refunds" component={Transactions} />
      <Route path="/orders" component={Transactions} />

      <Route
        path="/settlements"
        render={() => (
          <tabbed-container>
            <header id="link-header">
              <NavLink to="/settlements">Settlements</NavLink>
            </header>
            <Route path="/settlements" component={Settlements} />
          </tabbed-container>
        )}
      />

      <Route path="/invoices" exact component={InvoicingContainer} />
      <Route path="/invoices/:id(inv_.+)" component={InvoicesNew} />
      <Route path="/invoices/new" component={InvoicesNew} />
      <Route path="/items" component={InvoicingContainer} />

      <Route
        path="/paymentlinks"
        render={() => (
          <tabbed-container>
            <header id="link-header">
              <NavLink to="/paymentlinks">Payment Links</NavLink>
            </header>

            <Route path="/paymentlinks" component={PaymentLinks} />
          </tabbed-container>
        )}
      />

      <Route path="/customers" component={Customers} />

      <Route path="/accounts" component={Accounts} />

      <Route path="/reports" component={Reports} />
      <Route path="/team" component={TeamManagement} />

      <Route path="/profile" component={MyAccount} />
      <Route path="/activation" component={MyAccount} />
      <Route path="/addfunds" component={MyAccount} />
      <Route path="/credits" component={MyAccount} />
      <Route path="/referrals" component={MyAccount} />

      <Route path="/config" component={Settings} />
      <Route path="/keys" component={Settings} />
      <Route path="/webhooks" component={Settings} />
      <Route path="/dashboard" component={Home} />
      <Route
        path="/"
        render={() => {
          return <Redirect to="/dashboard" />;
        }}
      />
    </Switch>
  );
};

@withRouter
export default class Content extends Component {
  componentWillMount() {
    this.baseLocation = this.props.location;
  }

  render() {
    return (
      <main class="main-content">
        <Switch>
          <Route path="/payments/:id" component={PaymentsDetails} />
          <Route path="/refunds/:id" component={RefundDetails} />
          <Route path="/orders/:id" component={OrderDetails} />
          <Route path="/settlements/:id" component={SettlementDetails} />
          <Route path="/paymentlinks/:id" component={PaymentLinkDetails} />
          <Route
            render={_ => {
              this.baseLocation = this.props.location;
              return null;
            }}
          />
        </Switch>
        <Route render={_ => getBaseView(this.baseLocation)} />
      </main>
    );
  }
}
