import React, { Component } from 'react';
import { withRouter, Link } from 'react-router-dom';
import MainNavLink from 'merchant/components/MainNavLink';
import ShowWhen from 'merchant/components/ShowWhen';
import RZPLogoFullPNG from 'styles/assets/logo_full.png';
import RZPLogoPNG from 'styles/assets/logo.png';

const TRANSACTIONS_ROUTES_REGEX = /^\/(payments|refunds|orders|batch-refunds)/;
const ACCOUNTS_ROUTES_REGEX = /^\/(profile|activation|credits|addfunds|referrals)/;
const SETTINGS_ROUTES_REGEX = /^\/(config|webhooks|keys|applications|applications\/new)/;
const INVOICES_ROUTES_REGEX = /^\/(invoices|items)/;
const INVOICES_ROUTES_OLD_REGEX = /^\/(invoices|items|customers)/;
const MARKETPLACE_ROUTES_REGEX = /^\/route\/(payments|transfers|reversals|accounts)/;
const PAYMENTLINKS_ROUTES_REGEX = /^\/paymentlinks(\/batchuploads.*)?/;
const SUBSCRIPTIONS_ROUTES_REGEX = /^\/(subscriptions|plans|addons)/;

@withRouter
export default class Sidebar extends Component {
  // currently active routes in tabbed containers
  // populated with initial values
  routes = {
    transactions: '/payments',
    account: '/profile',
    settings: '/config',
    invoices: '/invoices',
    marketplace: '/route/payments',
    paymentlinks: '/paymentlinks',
    subscriptions: '/subscriptions',
  };

  componentWillReceiveProps(nextProps) {
    this.initializeRoutes(nextProps.location);
  }

  initializeRoutes(location) {
    let pathname = location.pathname;
    let routes = this.routes;
    let isOldUIEnabled = this.props.user.isOldUIEnabled;
    let invoicesRegex = isOldUIEnabled
      ? INVOICES_ROUTES_OLD_REGEX
      : INVOICES_ROUTES_REGEX;

    if (TRANSACTIONS_ROUTES_REGEX.test(pathname)) {
      routes.transactions = pathname.match(TRANSACTIONS_ROUTES_REGEX)[0];
    } else if (ACCOUNTS_ROUTES_REGEX.test(pathname)) {
      routes.account = pathname.match(ACCOUNTS_ROUTES_REGEX)[0];
    } else if (SETTINGS_ROUTES_REGEX.test(pathname)) {
      routes.settings = pathname.match(SETTINGS_ROUTES_REGEX)[0];
    } else if (invoicesRegex.test(pathname)) {
      routes.invoices = pathname.match(invoicesRegex)[0];
    } else if (MARKETPLACE_ROUTES_REGEX.test(pathname)) {
      routes.marketplace = pathname.match(MARKETPLACE_ROUTES_REGEX)[0];
    } else if (PAYMENTLINKS_ROUTES_REGEX.test(pathname)) {
      routes.paymentlinks = pathname.match(PAYMENTLINKS_ROUTES_REGEX)[0];
    } else if (SUBSCRIPTIONS_ROUTES_REGEX.test(pathname)) {
      routes.subscriptions = pathname.match(SUBSCRIPTIONS_ROUTES_REGEX)[0];
    }
  }

  render() {
    let { user, logoURL } = this.props;
    let routes = this.routes;
    let isMerchant = !!user.current;
    let isOldUIEnabled = user.isOldUIEnabled;

    return (
      <div class="sidebar">
        <section class="brand-logo">
          <Link to="/dashboard">
            <img src={logoURL || RZPLogoFullPNG} class="hidden-xs" />
            <img src={logoURL || RZPLogoPNG} class="visible-xs-block" />
          </Link>
        </section>
        <nav>
          {
            do {
              if (!isMerchant) {
                null;
              } else if (isOldUIEnabled) {
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
                  />

                  <MainNavLink
                    label="Subscriptions"
                    icon="icon icon-refresh text-warning"
                    notMyRole="sellerapp support"
                    to={routes.subscriptions}
                    isNew={true}
                  />

                  <MainNavLink
                    label="Route"
                    icon="icon icon-store text-primary"
                    to={routes.marketplace}
                    notMyRole="sellerapp support"
                    isNew={true}
                  />

                  <MainNavLink
                    label="Smart Collect"
                    icon="icon icon-account-balance text-success"
                    to="/virtualaccounts"
                    notMyRole="sellerapp support"
                    isNew={true}
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
                    featureEnabled="Referral"
                  />

                  <div class="divider-old" data-label="Settings" />

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
                    id="profile-nav"
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
              } else {
                <div class="nav">
                  <MainNavLink
                    label="Home"
                    icon="icon icon-chart text-info"
                    to="/dashboard"
                    exact
                    notMyRole="sellerapp support"
                  />
                  <MainNavLink
                    label="Transactions"
                    id="transactions-nav"
                    icon="icon icon-repeat text-primary"
                    to={routes.transactions}
                    notMyRole="sellerapp"
                  />
                  <MainNavLink
                    label="Settlements"
                    icon="icon icon-done-all text-success"
                    to="/settlements"
                    notMyRole="sellerapp support"
                  />

                  <div class="divider" />

                  <MainNavLink
                    label="Invoices"
                    icon="icon icon-notes text-warning"
                    to={routes.invoices}
                    featureEnabled="Invoice"
                    notMyRole="sellerapp"
                  />
                  <MainNavLink
                    label="Payment Links"
                    icon="icon icon-link text-primary"
                    to={routes.paymentlinks}
                  />
                  <MainNavLink
                    label="Subscriptions"
                    icon="icon icon-refresh text-info"
                    notMyRole="sellerapp support"
                    to={routes.subscriptions}
                    isNew={true}
                  />
                  <MainNavLink
                    label="Customers"
                    icon="icon icon-people text-warning"
                    to="/customers"
                    featureEnabled="Invoice"
                    notMyRole="sellerapp"
                  />
                  <MainNavLink
                    label="Route"
                    icon="icon icon-store text-success"
                    to={routes.marketplace}
                    notMyRole="sellerapp support"
                    isNew={true}
                  />
                  <MainNavLink
                    label="Smart Collect"
                    icon="icon icon-account-balance text-primary"
                    to="/virtualaccounts"
                    notMyRole="sellerapp support"
                    isNew={true}
                  />

                  <div class="divider" />

                  <MainNavLink
                    label="Reports"
                    icon="icon icon-books text-danger"
                    to="/reports"
                    notMyRole="sellerapp support"
                  />
                  <MainNavLink
                    label="Manage Team"
                    icon="icon icon-city text-info"
                    to="/team"
                    myRole="owner"
                  />
                  <MainNavLink
                    label="My Account"
                    id="myaccount-nav"
                    icon="icon icon-account text-primary"
                    to={routes.account}
                  />
                  <MainNavLink
                    label="Settings"
                    id="settings-nav"
                    icon="icon icon-settings text-warning"
                    to={routes.settings}
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
