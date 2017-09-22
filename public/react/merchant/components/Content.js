import React, { Component } from 'react';
import { NavLink, Switch, Route, withRouter, Redirect } from 'react-router-dom';
import { connect } from 'react-redux';

import { matchDetail } from 'merchant/routes';
import Slider from 'rzp/ui/Slider';
import ShowWhen from 'merchant/components/ShowWhen';
import Home from 'merchant/containers/Home/Index';
import Transactions from 'merchant/containers/Transactions';
import Settlements from 'merchant/containers/Settlements/List';
import PaymentLinks from 'merchant/containers/PaymentLinks/Index';
import InvoicingContainer from 'merchant/containers/Invoicing';
import InvoicesNew from 'merchant/containers/Invoices/New';
import Subscriptions from 'merchant/containers/Subscriptions/Index';
import Customers from 'merchant/containers/Customers/List';
import Marketplace from 'merchant/containers/Marketplace/Index';
import Reports from 'merchant/containers/Reports';
import TeamManagement from 'merchant/containers/Team';
import MyAccount from 'merchant/containers/MyAccount';
import Settings from 'merchant/containers/Settings';
import VirtualAccounts from 'merchant/containers/VirtualAccounts/List';

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

import {
  setBaseLocation,
  setActiveEntity,
  setSecActiveEntity,
} from 'merchant/modules/app';
import { openSlider } from 'rzp/modules/slider';

// Can be removed with old navigation removal
const TabbedContent = ({ headerId, navLabel, path, to, component }) => {
  return (
    <tabbed-container>
      <header id={headerId}>
        <NavLink to={to}>
          {navLabel}
        </NavLink>
      </header>
      <content>
        <Route path={path || to} component={component} />
      </content>
    </tabbed-container>
  );
};

// Can be removed with old navigation removal
const RefundsTabbedContainer = () => {
  return (
    <tabbed-container>
      <header id="transactions-header">
        <NavLink to="/refunds" exact>
          Refunds
        </NavLink>
        <ShowWhen
          featureEnabled="Batchrefunds"
          myRole="owner manager operations admin finance"
        >
          <NavLink
            to="/refunds/batchuploads"
            isActive={(match, { pathname }) =>
              pathname === '/refunds/batchupload' ||
              pathname === '/refunds/batchuploads'}
          >
            Batch Refunds
          </NavLink>
        </ShowWhen>
      </header>
      <content>
        <Switch>
          <Route path="/refunds/batchupload" component={BatchUpload} />
          <Route path="/refunds/batchuploads" component={BatchUploads} />
          <Route path="/refunds" component={RefundsList} />
        </Switch>
      </content>
    </tabbed-container>
  );
};

@withRouter
@connect(null, {
  setBaseLocation,
  setActiveEntity,
  setSecActiveEntity,
  openSlider,
})
export default class Content extends Component {
  setBaseLocation = location => {
    let { setBaseLocation, setActiveEntity, setSecActiveEntity } = this.props;
    var matchResult = matchDetail(location.pathname);

    if (matchResult) {
      this.detailView = matchResult.component;

      const params = matchResult.match.params;
      setActiveEntity(params.id);

      this.detailProps = params;

      setActiveEntity(matchResult.match.params.id);
      if (Object.keys(params > 1)) {
        setSecActiveEntity(params[Object.keys(params)[1]]);
      }
    } else {
      this.detailView = null;
      this.detailProps = null;
      setActiveEntity(null);
      setSecActiveEntity(null);

      this.baseLocation = location;
      setBaseLocation(location);
    }
  };

  getBaseView = () => {
    let isOldUIEnabled = this.props.user.isOldUIEnabled;

    return (
      <div>
        {
          do {
            if (isOldUIEnabled) {
              <Switch location={this.baseLocation}>
                <Route path="/dashboard" component={Home} />
                <Redirect from="/" exact to="/dashboard" />

                <Route
                  path="/payments"
                  render={() =>
                    <TabbedContent
                      to="/payments"
                      navLabel="Payments"
                      component={PaymentsList}
                    />}
                />

                <Route path="/refunds" component={RefundsTabbedContainer} />

                <Route
                  path="/orders"
                  render={() =>
                    <TabbedContent
                      to="/orders"
                      navLabel="Orders"
                      component={OrdersList}
                    />}
                />

                <Route path="/settlements" component={Settlements} />

                <Route path="/invoices" exact component={InvoicingContainer} />
                <Route path="/invoices/:id(inv_.+)" component={InvoicesNew} />
                <Route path="/invoices/new" component={InvoicesNew} />
                <Route path="/items" component={InvoicingContainer} />
                <Route path="/customers" component={InvoicingContainer} />
                <Route path="/subscriptions" component={Subscriptions} />
                <Route path="/plans" component={Subscriptions} />
                {/*<Route path="/addons" component={Subscriptions} />*/}
                <Route path="/route" component={Marketplace} />
                <Route path="/virtualaccounts" component={VirtualAccounts} />
                <Route path="/reports" component={Reports} />
                <Route path="/team" component={TeamManagement} />

                <Route
                  path="/profile"
                  render={() =>
                    <TabbedContent
                      to="/profile"
                      navLabel="Profile"
                      component={Profile}
                    />}
                />
                <Route
                  path="/activation"
                  render={() =>
                    <TabbedContent
                      to="/activation"
                      navLabel="Activation"
                      component={Activation}
                    />}
                />
                <Route
                  path="/addfunds"
                  render={() =>
                    <TabbedContent
                      to="/addfunds"
                      navLabel="Add Funds"
                      component={AddFunds}
                    />}
                />
                <Route
                  path="/credits"
                  render={() =>
                    <TabbedContent
                      to="/credits"
                      headerId="myaccount-header"
                      navLabel="Credits"
                      component={Credits}
                    />}
                />
                <Route
                  path="/referrals"
                  render={() =>
                    <TabbedContent
                      to="/referrals"
                      headerId="myaccount-header"
                      navLabel="Referrals"
                      component={Referrals}
                    />}
                />

                <Route
                  path="/config"
                  render={() =>
                    <TabbedContent
                      to="/config"
                      navLabel="Configuration"
                      component={Configuration}
                    />}
                />
                <Route
                  path="/keys"
                  render={() =>
                    <TabbedContent
                      to="/keys"
                      navLabel={`API Keys ( ${this.props.modeFormatted} Mode )`}
                      component={ApiKeys}
                    />}
                />
                <Route
                  path="/webhooks"
                  render={() =>
                    <TabbedContent
                      to="/webhooks"
                      headerId="settings-header"
                      navLabel="Webhooks"
                      component={Webhooks}
                    />}
                />

                <Redirect to="/dashboard" />
              </Switch>;
            } else {
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
                <Route path="/subscriptions" component={Subscriptions} />
                <Route path="/plans" component={Subscriptions} />
                {/*<Route path="/addons" component={Subscriptions} />*/}
                <Route
                  path="/customers"
                  render={() =>
                    <TabbedContent
                      headerId="invoicing-header"
                      to="/customers"
                      navLabel="Customers"
                      component={Customers}
                    />}
                />

                <Route path="/route" component={Marketplace} />
                <Route path="/virtualaccounts" component={VirtualAccounts} />

                <Route path="/reports" component={Reports} />

                <Route path="/profile" component={MyAccount} />
                <Route path="/activation" component={MyAccount} />
                <Route path="/addfunds" component={MyAccount} />
                <Route path="/credits" component={MyAccount} />
                <Route path="/referrals" component={MyAccount} />
                <Route path="/team" component={MyAccount} />

                <Route path="/config" component={Settings} />
                <Route path="/keys" component={Settings} />
                <Route path="/webhooks" component={Settings} />
                <Route path="/applications" component={Settings} />

                <Redirect to="/dashboard" />
              </Switch>;
            }
          }
        }
      </div>
    );
  };

  componentWillMount() {
    this.setBaseLocation(this.props.location);
  }

  componentWillReceiveProps(props) {
    this.setBaseLocation(props.location);
    this.showSliderView();
  }

  showSliderView() {
    if (this.detailView && this.baseLocation) {
      this.props.openSlider();
    }
  }

  render() {
    var DetailView = this.detailView;
    var BaseView = this.baseLocation ? this.getBaseView() : null;

    if (DetailView) {
      DetailView = BaseView
        ? <Slider closeUrl={this.baseLocation}>
            {' '}<DetailView {...this.detailProps} />{' '}
          </Slider>
        : <DetailView {...this.detailProps} />;
    }

    return (
      <main class="main-content">
        {BaseView}
        {DetailView}
      </main>
    );
  }
}
