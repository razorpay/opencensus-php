import React, { Component } from 'react';
import { NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

const TRANSACTIONS_ROUTES_REGEX = /^\/app\/(payments|refunds|orders|batch-refunds)/;
const ACCOUNTS_ROUTES_REGEX = /^\/app\/(profile|activation|credits|addfunds)/;
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
                <ShowWhen notMyRole="sellerapp">
                  <NavLink exact to="/">
                    <i class="icon icon-chart" />
                    Home
                  </NavLink>
                </ShowWhen>

                <ShowWhen notMyRole="sellerapp">
                  <NavLink to={routes.transactions}>
                    <i class="icon icon-transactions" />
                    Transactions
                  </NavLink>
                </ShowWhen>

                <ShowWhen notMyRole="sellerapp">
                  <NavLink to="/app/settlements">
                    <i class="icon icon-done-all" />
                    Settlements
                  </NavLink>
                </ShowWhen>

                <div class="divider" />

                <NavLink to={routes.invoices}>
                  <i class="icon icon-invoices" />
                  Invoices
                </NavLink>

                <NavLink to="/app/customers">
                  <i class="icon icon-people" />
                  Customers
                </NavLink>

                <div class="divider" />

                <ShowWhen notMyRole="sellerapp">
                  <NavLink to="/app/reports">
                    <i class="icon icon-reports" />
                    Reports
                  </NavLink>
                </ShowWhen>

                <ShowWhen myRole="owner">
                  <NavLink to="/app/team">
                    <i class="icon icon-team" />
                    Manage Team
                  </NavLink>
                </ShowWhen>

                <NavLink to={routes.account}>
                  <i class="icon icon-account" />
                  My Account
                </NavLink>

                <ShowWhen myRole="owner manager admin">
                  <NavLink to={routes.settings}>
                    <i class="icon icon-settings" />
                    Settings
                  </NavLink>
                </ShowWhen>
              </div>}
        </nav>
      </div>
    );
  }
}
