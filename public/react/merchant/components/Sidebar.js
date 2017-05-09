import React, { Component } from 'react';
import { NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

@withRouter
export default class Sidebar extends Component {
  // currently active routes in tabbed containers
  // populated with initial values
  routes = {
    transactions: '/payments',
  };

  render() {
    let { user, location } = this.props;

    let { transactions } = this.routes;

    let isMerchant = !!user.current;
    let { pathname } = location;

    if (/^\/(payments|refunds|orders)/.test(pathname)) {
      transactions = pathname;
    }

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
                  <NavLink to={routes.transactions}>Transactions</NavLink>
                </ShowWhen>

                <ShowWhen notMyRole="sellerapp">
                  <NavLink to="/settlements">Settlements</NavLink>
                </ShowWhen>

                <NavLink to="/invoices">Invoices</NavLink>

                <ShowWhen notMyRole="sellerapp">
                  <NavLink to="/reports">Reports</NavLink>
                </ShowWhen>

                <ShowWhen myRole="owner">
                  <NavLink to="/team">Manage Team</NavLink>
                </ShowWhen>
              </div>}
        </nav>
      </div>
    );
  }
}
