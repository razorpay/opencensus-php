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
    let invoicesRegex = INVOICES_ROUTES_REGEX;

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
                    apiFeatureEnabled="subscriptions"
                    notMyRole="sellerapp"
                  />
                  <MainNavLink
                    label="Payment Links"
                    icon="icon icon-link text-primary"
                    to={routes.paymentlinks}
                  />
                  <MainNavLink
                    label="Route"
                    icon="icon icon-store text-success"
                    to={routes.marketplace}
                    notMyRole="sellerapp support"
                    isNew={true}
                  />
                  <MainNavLink
                    label="Subscriptions"
                    icon="icon icon-refresh text-info"
                    notMyRole="sellerapp support"
                    to={routes.subscriptions}
                    isNew={true}
                  />
                  <MainNavLink
                    label="Smart Collect"
                    icon="icon icon-account-balance text-danger"
                    to="/virtualaccounts"
                    notMyRole="sellerapp support"
                    isNew={true}
                  />

                  <MainNavLink
                    label="Customers"
                    icon="icon icon-people text-warning"
                    to="/customers"
                    featureEnabled="Invoice"
                    apiFeatureEnabled={['subscriptions', 'virtual_accounts']}
                    notMyRole="sellerapp"
                  />

                  <div class="divider" />

                  <MainNavLink
                    label="Reports"
                    icon="icon icon-books text-danger"
                    to="/reports"
                    notMyRole="sellerapp support"
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
