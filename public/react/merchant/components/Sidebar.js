import React, { Component } from 'react';
import { NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

@withRouter
export default class Sidebar extends Component {
  // currently active routes in tabbed containers
  // populated with initial values
  routes = {
    transactions: '/app/payments',
    account: '/app/profile',
    settings: '/app/config',
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

    if (/^\/app\/(payments|refunds|orders|batch-refunds)/.test(pathname)) {
      routes.transactions = pathname;
    } else if (/^\/app\/(profile|activation|credits|addfunds)/.test(pathname)) {
      routes.account = pathname;
    } else if (/^\/app\/(config|webhooks|keys)/.test(pathname)) {
      routes.settings = pathname;
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
                  <NavLink exact to="/">Home</NavLink>
                </ShowWhen>

                <ShowWhen notMyRole="sellerapp">
                  <NavLink to={routes.transactions}>Transactions</NavLink>
                </ShowWhen>

                <ShowWhen notMyRole="sellerapp">
                  <NavLink to="/app/settlements">Settlements</NavLink>
                </ShowWhen>

                <NavLink to="/app/invoices">Invoices</NavLink>

                <ShowWhen notMyRole="sellerapp">
                  <NavLink to="/app/reports">Reports</NavLink>
                </ShowWhen>

                <ShowWhen myRole="owner">
                  <NavLink to="/app/team">Manage Team</NavLink>
                </ShowWhen>

                <NavLink to={routes.account}>My Account</NavLink>

                <ShowWhen myRole="owner manager admin">
                  <NavLink to={routes.settings}>Settings</NavLink>
                </ShowWhen>
              </div>}
        </nav>
      </div>
    );
  }
}
