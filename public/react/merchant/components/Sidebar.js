import React, { Component } from 'react';
import { withRouter, Link } from 'react-router-dom';
import MainNavLink from 'merchant/components/MainNavLink';

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

    return (
      <div class="sidebar">
        <section class="brand-logo">
          <Link to="/dashboard">
            <img src="img/logo_full.png" />
          </Link>
        </section>
        <nav>
          {!isMerchant
            ? null
            : <div class="nav">
                <MainNavLink
                  label="Home"
                  icon="icon-chart"
                  to="/dashboard"
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
                  to="/settlements"
                  notMyRole="sellerapp support"
                />

                <div class="divider" />

                <MainNavLink
                  label="Invoices"
                  icon="icon-invoices"
                  to={routes.invoices}
                  featureEnabled="Invoice"
                  notMyRole="sellerapp"
                />
                <MainNavLink
                  label="Payment Links"
                  icon="icon-link"
                  to="/paymentlinks"
                />

                <MainNavLink
                  label="Customers"
                  icon="icon-people"
                  to="/customers"
                  featureEnabled="Invoice"
                  notMyRole="sellerapp"
                />

                <div class="divider" />

                <MainNavLink
                  label="Marketplace"
                  icon="icon-store"
                  to="/accounts"
                  notMyRole="sellerapp support"
                  featureEnabled="Marketplace"
                  beta={true}
                />

                <div class="divider" />

                <MainNavLink
                  label="Reports"
                  icon="icon-reports"
                  to="/reports"
                  notMyRole="sellerapp support"
                />
                <MainNavLink
                  label="Manage Team"
                  icon="icon-team"
                  to="/team"
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
