import React, { Component } from 'react';
import { withRouter, Link } from 'react-router-dom';
import MainNavLink from 'merchant/components/MainNavLink';
import ShowWhen from 'merchant/components/ShowWhen';

const TRANSACTIONS_ROUTES_REGEX = /^\/(payments|refunds|orders|batch-refunds)/;
const ACCOUNTS_ROUTES_REGEX = /^\/(profile|activation|credits|addfunds|referrals)/;
const SETTINGS_ROUTES_REGEX = /^\/(config|webhooks|keys)/;
const INVOICES_ROUTES_REGEX = /^\/(invoices|items)/;

@withRouter
export default class Sidebar extends Component {
  // currently active routes in tabbed containers
  // populated with initial values
  routes = {
    transactions: '/payments',
    account: '/activation',
    settings: '/config',
    invoices: '/invoices',
  };

  componentWillMount() {
    this.initializeRoutes(this.props.location);
  }

  componentWillReceiveProps(nextProps) {
    this.initializeRoutes(nextProps.location);
  }

  initializeRoutes(location) {
    let pathname = location.pathname;
    let routes = this.routes;

    if (TRANSACTIONS_ROUTES_REGEX.test(pathname)) {
      routes.transactions = pathname.match(TRANSACTIONS_ROUTES_REGEX)[0];
    } else if (ACCOUNTS_ROUTES_REGEX.test(pathname)) {
      routes.account = pathname.match(ACCOUNTS_ROUTES_REGEX)[0];
    } else if (SETTINGS_ROUTES_REGEX.test(pathname)) {
      routes.settings = pathname.match(SETTINGS_ROUTES_REGEX)[0];
    } else if (INVOICES_ROUTES_REGEX.test(pathname)) {
      routes.invoices = pathname.match(INVOICES_ROUTES_REGEX)[0];
    }
  }

  render() {
    let { user } = this.props;
    let routes = this.routes;
    let isMerchant = !!user.current;
    let isNewUIEnabled = user.tags.indexOf('Newui') !== -1;

    return (
      <div class="sidebar">
        <section class="brand-logo">
          <Link to="/dashboard">
            <img src="img/logo_full.png" class="hidden-xs" />
            <img src="img/logo.png" class="visible-xs-block" />
          </Link>
        </section>
        <nav>
          {
            do {
              if (!isMerchant) {
                null;
              } else if (isNewUIEnabled) {
                <div class="nav">
                  <MainNavLink
                    label="Home"
                    icon="icon icon-chart"
                    to="/dashboard"
                    exact
                    notMyRole="sellerapp support"
                  />
                  <MainNavLink
                    label="Transactions"
                    id="transactions-nav"
                    icon="icon icon-transactions"
                    to={routes.transactions}
                    notMyRole="sellerapp"
                  />
                  <MainNavLink
                    label="Settlements"
                    icon="icon icon-done-all"
                    to="/settlements"
                    notMyRole="sellerapp support"
                  />

                  <div class="divider" />

                  <MainNavLink
                    label="Invoices"
                    icon="icon icon-invoices"
                    to={routes.invoices}
                    featureEnabled="Invoice"
                    notMyRole="sellerapp"
                  />
                  <MainNavLink
                    label="Payment Links"
                    icon="icon icon-link"
                    to="/paymentlinks"
                  />

                  <MainNavLink
                    label="Customers"
                    icon="icon icon-people"
                    to="/customers"
                    featureEnabled="Invoice"
                    notMyRole="sellerapp"
                  />

                  <ShowWhen
                    notMyRole="sellerapp support"
                    featureEnabled="Marketplace"
                  >
                    <div class="divider" />
                  </ShowWhen>

                  <MainNavLink
                    label="Marketplace"
                    icon="icon icon-store"
                    to="/accounts"
                    notMyRole="sellerapp support"
                    featureEnabled="Marketplace"
                    beta={true}
                  />

                  <div class="divider" />

                  <MainNavLink
                    label="Reports"
                    icon="icon icon-reports"
                    to="/reports"
                    notMyRole="sellerapp support"
                  />
                  <MainNavLink
                    label="Manage Team"
                    icon="icon icon-team"
                    to="/team"
                    myRole="owner"
                    beta={true}
                  />
                  <MainNavLink
                    label="My Account"
                    icon="icon icon-account"
                    to={routes.account}
                  />
                  <MainNavLink
                    label="Settings"
                    id="settings-nav"
                    icon="icon icon-settings"
                    to={routes.settings}
                    myRole="owner admin"
                  />
                </div>;
              } else {
                <div class="nav">
                  <MainNavLink
                    label="Home"
                    icon="fa fa-area-chart text-info"
                    to="/dashboard"
                    exact
                    notMyRole="sellerapp support"
                  />

                  <MainNavLink
                    label="Payments"
                    icon="fa fa-inr text-primary"
                    to="/payments"
                    notMyRole="sellerapp"
                  />

                  <MainNavLink
                    label="Orders"
                    icon="fa fa-archive text-success"
                    to="/orders"
                    notMyRole="sellerapp"
                  />

                  <MainNavLink
                    label="Refunds"
                    icon="fa fa-mail-reply text-warning"
                    to="/refunds"
                    notMyRole="sellerapp"
                  />

                  <MainNavLink
                    label="Settlements"
                    icon="fa fa-check-square-o text-success"
                    to="/settlements"
                    notMyRole="sellerapp support"
                  />

                  <MainNavLink
                    label="Invoices"
                    icon="fa fa-money text-primary"
                    to={routes.invoices}
                    featureEnabled="Invoice"
                    notMyRole="sellerapp"
                  />

                  <MainNavLink
                    label="Payment Links"
                    icon="icon icon-link text-warning"
                    to="/paymentlinks"
                  />

                  <MainNavLink
                    label="Customers"
                    icon="icon icon-people text-info"
                    to="/customers"
                    featureEnabled="Invoice"
                    notMyRole="sellerapp"
                  />

                  <MainNavLink
                    label="Marketplace"
                    icon="icon icon-store text-primary"
                    to="/accounts"
                    notMyRole="sellerapp support"
                    featureEnabled="Marketplace"
                    beta={true}
                  />

                  <MainNavLink
                    label="Add Funds"
                    icon="icon icon-wallet text-info"
                    to="/addfunds"
                    notMyRole="sellerapp"
                  />

                  <MainNavLink
                    label="Reports"
                    icon="fa fa-file-excel-o text-danger"
                    to="/reports"
                    notMyRole="sellerapp support"
                  />

                  <MainNavLink
                    label="Manage Team"
                    icon="fa fa-users text-info"
                    to="/team"
                    myRole="owner"
                    beta={true}
                  />

                  <MainNavLink
                    label="Credits"
                    icon="fa fa-credit-card text-warning"
                    to="/credits"
                    notMyRole="sellerapp support"
                  />

                  <MainNavLink
                    label="Referrals"
                    icon="fa fa-gift text-danger"
                    to="/referrals"
                    notMyRole="sellerapp support"
                  />

                  <div
                    class="divider-old"
                    class="hidden-xs"
                    data-label="Settings"
                  />

                  <MainNavLink
                    label="API Keys"
                    icon="fa fa-key text-warning"
                    to="/keys"
                    myRole="owner admin"
                  />

                  <MainNavLink
                    label="Activation"
                    icon="fa fa-question-circle-o text-success"
                    to="/activation"
                    myRole="owner manager admin"
                  />

                  <MainNavLink
                    label="Webhooks"
                    icon="fa fa-share-alt text-warning"
                    to="/webhooks"
                    myRole="owner manager admin"
                  />

                  <MainNavLink
                    label="Profile"
                    icon="fa fa-user-o text-info"
                    to="/profile"
                  />

                  <MainNavLink
                    label="Configuration"
                    icon="fa fa-cog text-warning"
                    to="/config"
                    myRole="owner manager admin"
                  />
                </div>;
              }
            }
          }
        </nav>
      </div>
    );
  }
}
