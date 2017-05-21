import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import MainNavLink from 'merchant/components/MainNavLink';

const TRANSACTIONS_ROUTES_REGEX = /^\/app\/(payments|refunds|orders|batch-refunds)/;
const ACCOUNTS_ROUTES_REGEX = /^\/app\/(profile|activation|credits|addfunds|referrals)/;
const SETTINGS_ROUTES_REGEX = /^\/app\/(config|webhooks|keys)/;
const INVOICES_ROUTES_REGEX = /^\/app\/(invoices|items)/;

@withRouter
export default class Sidebar extends Component {
  // currently active routes in tabbed containers
  // populated with initial values
  routes = {
    transactions: '/app/payments',
    account: '/app/activation',
    settings: '/app/config',
    invoices: '/app/invoices',
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

    return (
      <div class="sidebar">
        <section class="brand-logo">
          <a href="#/">
            <img src="img/logo_full.png" />
          </a>
        </section>
        <nav>
          {!isMerchant
            ? null
            : <div class="nav">
                <MainNavLink
                  label="Home"
                  icon="icon-chart"
                  to="/"
                  exact
                  notMyRole="sellerapp support"
                />
                <MainNavLink
                  label="Transactions"
                  icon="icon-transactions"
                  to={routes.transactions}
                  notMyRole="sellerapp"
                />
                <MainNavLink
                  label="Settlements"
                  icon="icon-done-all"
                  to="/app/settlements"
                  notMyRole="sellerapp support"
                />

                <div class="divider" />

                <MainNavLink
                  label="Invoices"
                  icon="icon-invoices"
                  to={routes.invoices}
                />
                <MainNavLink
                  label="Payment Links"
                  icon="icon-link"
                  to="/app/paymentlinks"
                />

                <MainNavLink
                  label="Customers"
                  icon="icon-people"
                  to="/app/customers"
                  notMyRole="sellerapp"
                />

                <div class="divider" />

                <MainNavLink
                  label="Marketplace"
                  icon="icon-team"
                  to="/app/accounts"
                  notMyRole="sellerapp support"
                  featureEnabled="Marketplace"
                  beta={true}
                />

                <div class="divider" />

                <MainNavLink
                  label="Reports"
                  icon="icon-reports"
                  to="/app/reports"
                  notMyRole="sellerapp support"
                />
                <MainNavLink
                  label="Manage Team"
                  icon="icon-team"
                  to="/app/team"
                  myRole="owner"
                  beta={true}
                />
                <MainNavLink
                  label="My Account"
                  icon="icon-account"
                  to={routes.account}
                />
                <MainNavLink
                  label="Settings"
                  icon="icon-settings"
                  to={routes.settings}
                  myRole="owner admin"
                />
              </div>}
        </nav>
      </div>
    );
  }
}
