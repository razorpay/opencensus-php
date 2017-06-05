import React, { Component } from 'react';
import { NavLink, Switch, Route, withRouter, Redirect } from 'react-router-dom';
import { connect } from 'react-redux';

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

// Below will be removed with old navigation removal
import PaymentsList from 'merchant/containers/Payments/List';
import RefundsList from 'merchant/containers/Refunds/List';
import BatchUpload from 'merchant/containers/Refunds/BatchUpload';
import BatchUploads from 'merchant/containers/Refunds/BatchList';
import OrdersList from 'merchant/containers/Orders/List';
import Profile from 'merchant/containers/Profile';
import Activation from 'merchant/containers/Activation';
import AddFunds from 'merchant/containers/AddFunds';
import Credits from 'merchant/containers/Credits/List';
import Referrals from 'merchant/containers/Referrals/List';
import Configuration from 'merchant/containers/Configuration';
import ApiKeys from 'merchant/containers/Keys/List';
import Webhooks from 'merchant/containers/Webhooks/List';

import { removeActiveRow } from 'merchant/modules/app';

// Can be removed with old navigation removal
const TabbedContent = ({ headerId, navLabel, path, to, component }) => {
  return (
    <tabbed-container>
      <header id={headerId}>
        <NavLink to={to}>{navLabel}</NavLink>
      </header>
      <Route path={path || to} component={component} />
    </tabbed-container>
  );
};

@withRouter
@connect(null, { removeActiveRow })
export default class Content extends Component {
  getBaseView = () => {
    let isNewUIEnabled = this.props.user.tags.indexOf('Newui') !== -1;

    return (
      <div>
        {
          do {
            if (isNewUIEnabled) {
              <Switch location={this.baseLocation}>
                <Route path="/dashboard" component={Home} />
                <Redirect from="/" exact to="/dashboard" />

                <Route path="/payments" component={Transactions} />
                <Route path="/refunds" component={Transactions} />
                <Route path="/orders" component={Transactions} />

                <Route path="/settlements" component={Settlements} />

                <Route path="/invoices" exact component={InvoicingContainer} />
                <Route path="/invoices/:id(inv_.+)" component={InvoicesNew} />
                <Route path="/invoices/new" component={InvoicesNew} />
                <Route path="/items" component={InvoicingContainer} />
                <Route path="/paymentlinks" component={PaymentLinks} />
                <Route
                  path="/customers"
                  render={() => (
                    <TabbedContent
                      headerId="invoicing-header"
                      to="/customers"
                      navLabel="Customers"
                      component={Customers}
                    />
                  )}
                />

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

                <Redirect to="/dashboard" />
              </Switch>;
            } else {
              <Switch location={this.baseLocation}>
                <Route path="/dashboard" component={Home} />
                <Redirect from="/" exact to="/dashboard" />

                <Route
                  path="/payments"
                  render={() => (
                    <TabbedContent
                      to="/payments"
                      navLabel="Payments"
                      component={PaymentsList}
                    />
                  )}
                />

                <Route
                  path="/refunds/batchupload"
                  render={() => (
                    <TabbedContent
                      headerId="transactions-header"
                      to="/refunds"
                      path="/refunds/batchupload"
                      navLabel="Refunds"
                      component={BatchUpload}
                    />
                  )}
                />

                <Route
                  path="/refunds/batchuploads"
                  render={() => (
                    <TabbedContent
                      headerId="transactions-header"
                      to="/refunds"
                      path="/refunds/batchuploads"
                      navLabel="Refunds"
                      component={BatchUploads}
                    />
                  )}
                />

                <Route
                  path="/refunds"
                  exact
                  render={() => (
                    <TabbedContent
                      headerId="transactions-header"
                      to="/refunds"
                      navLabel="Refunds"
                      component={RefundsList}
                    />
                  )}
                />

                <Route
                  path="/orders"
                  render={() => (
                    <TabbedContent
                      to="/orders"
                      navLabel="Orders"
                      component={OrdersList}
                    />
                  )}
                />

                <Route path="/settlements" component={Settlements} />

                <Route path="/invoices" exact component={InvoicingContainer} />
                <Route path="/invoices/:id(inv_.+)" component={InvoicesNew} />
                <Route path="/invoices/new" component={InvoicesNew} />
                <Route path="/items" component={InvoicingContainer} />
                <Route path="/customers" component={InvoicingContainer} />

                <Route path="/marketplace" component={Marketplace} />
                <Route path="/accounts" component={Marketplace} />
                <Route path="/reports" component={Reports} />
                <Route path="/team" component={TeamManagement} />

                <Route
                  path="/profile"
                  render={() => (
                    <TabbedContent
                      to="/profile"
                      navLabel="Profile"
                      component={Profile}
                    />
                  )}
                />
                <Route
                  path="/activation"
                  render={() => (
                    <TabbedContent
                      to="/activation"
                      navLabel="Activation"
                      component={Activation}
                    />
                  )}
                />
                <Route
                  path="/addfunds"
                  render={() => (
                    <TabbedContent
                      to="/addfunds"
                      navLabel="Add Funds"
                      component={AddFunds}
                    />
                  )}
                />
                <Route
                  path="/credits"
                  render={() => (
                    <TabbedContent
                      to="/credits"
                      headerId="myaccount-header"
                      navLabel="Credits"
                      component={Credits}
                    />
                  )}
                />
                <Route
                  path="/referrals"
                  render={() => (
                    <TabbedContent
                      to="/referrals"
                      headerId="myaccount-header"
                      navLabel="Referrals"
                      component={Referrals}
                    />
                  )}
                />

                <Route
                  path="/config"
                  render={() => (
                    <TabbedContent
                      to="/config"
                      navLabel="Configuration"
                      component={Configuration}
                    />
                  )}
                />
                <Route
                  path="/keys"
                  render={() => (
                    <TabbedContent
                      to="/keys"
                      navLabel={`API Keys ( ${this.props.modeFormatted} Mode )`}
                      component={ApiKeys}
                    />
                  )}
                />
                <Route
                  path="/webhooks"
                  render={() => (
                    <TabbedContent
                      to="/webhooks"
                      headerId="settings-header"
                      navLabel="Webhooks"
                      component={Webhooks}
                    />
                  )}
                />

                <Redirect to="/dashboard" />
              </Switch>;
            }
          }
        }
      </div>
    );
  };

  removeActiveRow = () => {
    this.props.removeActiveRow();
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
        ? <Slider closeUrl={this.baseLocation} onClose={this.removeActiveRow}>
            <DetailView />
          </Slider>
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
