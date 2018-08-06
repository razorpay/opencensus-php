import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter, Link } from 'react-router-dom';

import { toggleMobileMenu } from 'merchantLA/modules/app';
import MainNavLink from 'merchant/components/MainNavLink';
import { areReportsStillDownloading } from 'merchantLA/modules/reports';

const RZPLogoFullPNG = 'https://cdn.razorpay.com/logo_invert.svg';

@withRouter
@connect(
  state => ({
    showMobileMenu: state.app.showMobileMenu,
    currentReportList: state.reports.currentReportList,
  }),
  { toggleMobileMenu }
)
export default class Sidebar extends Component {
  constructor(props) {
    super(props);

    //reference store data to update UI of sidebar navs
    this.state = {
      isReportsPending: areReportsStillDownloading(props.currentReportList),
    };
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

  componentWillReceiveProps(nextProps) {
    if (this.props.currentReportList !== nextProps.currentReportList) {
      this.setState({
        isReportsPending: areReportsStillDownloading(
          nextProps.currentReportList
        ),
      });
    }
  }

  hideSidebar() {
    return this.props.showMobileMenu && this.props.toggleMobileMenu();
  }

  render() {
    const { isReportsPending } = this.state;
    let { user, logoURL, showMobileMenu } = this.props;
    let routes = this.routes;
    let isMerchant = !!user.current;

    return (
      <React.Fragment>
        <div class={`sidebar${showMobileMenu ? ' show-mobile-menu' : ''}`}>
          <section class="brand-logo">
            <Link to="/dashboard" onClick={this.hideSidebar}>
              <img src={logoURL || RZPLogoFullPNG} />
            </Link>
          </section>
          <nav>
            {do {
              if (!isMerchant) {
                null;
              } else {
                <div class="nav">
                  <MainNavLink
                    label="Home"
                    icon="i i-chart text-info"
                    to="/dashboard"
                    exact
                    notMyRole="sellerapp support"
                  />
                  <MainNavLink
                    label="Transfers"
                    icon="i i-transfers text-primary"
                    to={routes.transfers}
                    notMyRole="sellerapp"
                  />
                  <MainNavLink
                    label="Reversals"
                    icon="i i-reversal text-warm"
                    to={routes.reversals}
                    notMyRole="sellerapp"
                  />
                  <MainNavLink
                    label="Settlements"
                    icon="i i-done-all text-success"
                    to="/settlements"
                    notMyRole="sellerapp support"
                  />
                  {/* <MainNavLink
                    label="Reports"
                    icon="i i-books text-danger"
                    to="/reports"
                    notMyRole="sellerapp support"
                    isPending={isReportsPending}
                  /> */}
                  <MainNavLink
                    label="Account Settings"
                    icon="i i-account text-primary"
                    to={routes.account}
                  />
                </div>;
              }
            }}
          </nav>
        </div>
        {showMobileMenu && (
          <div className="sidebar-bg-overlay" onClick={this.hideSidebar} />
        )}
      </React.Fragment>
    );
  }
}
