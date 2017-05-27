import React, { Component } from 'react';
import { NavLink, Switch, Route, withRouter, Redirect } from 'react-router-dom';

import Home from 'merchant/containers/HomeContainer';
import Transactions from 'merchant/containers/Transactions';
import Settlements from 'merchant/containers/Settlements/List';
import PaymentLinks from 'merchant/containers/PaymentLinks/List';
import InvoicingContainer from 'merchant/containers/Invoicing';
import InvoicesNew from 'merchant/containers/Invoices/New';
import Customers from 'merchant/containers/Customers/List';
import Marketplace from 'merchant/containers/Marketplace/Accounts/List';
import Reports from 'merchant/containers/Reports';
import TeamManagement from 'merchant/containers/Team';
import MyAccount from 'merchant/containers/MyAccount';
import Settings from 'merchant/containers/Settings';
import { matchDetail } from 'merchant/routes';
import Slider from 'rzp/ui/Slider';

@withRouter
export default class Content extends Component {
  getBaseView = () => {
    return (
      <Switch location={this.baseLocation}>
        <Route path="/dashboard" component={Home} />
        <Route
          path="/"
          exact
          render={() => {
            return <Redirect to="/dashboard" />;
          }}
        />

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
        <Route path="/marketplace" component={Marketplace} />

        <Route path="/accounts" component={Marketplace} />

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
      </Switch>
    );
  };

  render() {
    let location = this.props.location;
    let DetailView = matchDetail(location.pathname);

    if (!DetailView) {
      this.baseLocation = location;
    }

    var BaseView = this.baseLocation ? this.getBaseView() : null;

    if (DetailView) {
      DetailView = BaseView
        ? <Slider closeUrl={this.baseLocation}><DetailView /></Slider>
        : <DetailView />;
    }

    return (
      <main class="main-content">
        {BaseView}
        {DetailView}
      </main>
    );
  }
}
