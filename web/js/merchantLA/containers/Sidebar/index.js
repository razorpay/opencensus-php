import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';

import { toggleMobileMenu } from 'merchantLA/reducers/app';
import MainNavLink from 'merchant_common/components/MainNavLink';

const RZPLogoFullPNG = 'https://cdn.razorpay.com/logo_invert.svg';
@connect(
  (state) => ({
    showMobileMenu: state.app.showMobileMenu,
  }),
  { toggleMobileMenu },
)
class Sidebar extends Component {
  constructor(props) {
    super(props);
    this.hideSidebar = this.hideSidebar.bind(this);
  }
  // currently active routes in tabbed containers
  // populated with initial values
  routes = {
    transfers: '/transfers',
    reversals: '/reversals',
    settlements: '/settlements',
    reports: '/reports',
    account: '/profile',
  };

  hideSidebar() {
    return this.props.showMobileMenu && this.props.toggleMobileMenu();
  }

  render() {
    const { user, logoURL, showMobileMenu } = this.props;
    const routes = this.routes;
    const isMerchant = !!user.current;

    return (
      <React.Fragment>
        <div class={`sidebar${showMobileMenu ? ' show-mobile-menu' : ''}`}>
          <section class="brand-logo">
            <Link
              to="/dashboard"
              onClick={this.hideSidebar}
              aria-label="brand-logo link for home page"
            >
              <img
                src={logoURL || RZPLogoFullPNG}
                width="145"
                height="35"
                alt="brand-logo"
                role="img"
                aria-label="brand-logo"
              />
            </Link>
          </section>
          <nav>
            {!isMerchant ? null : (
              <div class="nav">
                <MainNavLink
                  label="Transfers"
                  icon="i i-transfers text-primary"
                  to={routes.transfers}
                />
                <MainNavLink label="Reversals" icon="i i-undo text-warm" to={routes.reversals} />
                <MainNavLink
                  label="Settlements"
                  icon="i i-done-all text-success"
                  to="/settlements"
                />
                <MainNavLink label="Reports" icon="i i-books text-danger" to="/reports" />
                <MainNavLink
                  label="Account Settings"
                  icon="i i-account text-primary"
                  to={routes.account}
                />
              </div>
            )}
          </nav>
        </div>
        {showMobileMenu && <div className="sidebar-bg-overlay" onClick={this.hideSidebar} />}
      </React.Fragment>
    );
  }
}

export default withRouter(Sidebar);
