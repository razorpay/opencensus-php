import React, { Component } from 'react';
import { withRouter, Link } from 'react-router-dom';

import ProgressBar from 'rzp/ui/ProgressBar';

import MainNavLink from 'merchant/components/MainNavLink';
import ShowWhen from 'merchant/components/ShowWhen';
import store from 'merchant/store';

const TRANSACTIONS_ROUTES_REGEX = /^\/(payments|refunds|orders|batch-refunds)/;
const ACCOUNTS_ROUTES_REGEX = /^\/(profile|activation|credits|addfunds|referrals)/;
const SETTINGS_ROUTES_REGEX = /^\/(config|webhooks|keys|applications|applications\/new)/;
const INVOICES_ROUTES_REGEX = /^\/(invoices|items)/;
const MARKETPLACE_ROUTES_REGEX = /^\/route\/(payments|transfers|reversals|accounts)/;
const PAYMENTLINKS_ROUTES_REGEX = /^\/paymentlinks(\/batchuploads.*)?/;
const SUBSCRIPTIONS_ROUTES_REGEX = /^\/(subscriptions|plans|addons)/;

const RZPLogoFullPNG = 'https://cdn.razorpay.com/logo_invert.svg';
const RZPLogoPNG = '/img/logo.png';

@withRouter
export default class Sidebar extends Component {
  constructor(props) {
    super(props);

    //reference store data to update UI of sidebar navs
    this.state = {
      reportList: store.getState().reports.currentReportList,
    };

    store.subscribe(() => {
      //update state when report list store changes
      this.setState({
        reportList: store.getState().reports.currentReportList,
      });
    });
  }
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
    const { reportList } = this.state;
    let { user, logoURL } = this.props;
    let routes = this.routes;
    let isMerchant = !!user.current;

    const remainingActivation = 100 - user.activation_progress;

    return (
      <div class="sidebar">
        <section class="brand-logo">
          <Link to="/dashboard">
            <img src={logoURL || RZPLogoFullPNG} class="hidden-xs" />
            <img src={logoURL || RZPLogoPNG} class="visible-xs-block" />
          </Link>
        </section>
        <nav>
          {do {
            if (!isMerchant) {
              null;
            } else {
              <div class="nav">
                {false && (
                  <Link className="activation-status-link" to="/activation">
                    <div className="activation-status">
                      <div className="clearfix">
                        <div className="pull-left">Activate your account</div>
                        <div className="pull-right">
                          <i className="i i-chevron-right" />
                        </div>
                      </div>
                      <div class="activation-bar-content">
                        <div className="activation-bar-text">
                          {user.activation_progress >= 70
                            ? `${remainingActivation}% Remaining`
                            : `${user.activation_progress}% Complete`}
                        </div>
                        <div className="activation-bar">
                          <ProgressBar
                            type="success"
                            max={100}
                            value={user.activation_progress}
                          />
                        </div>
                      </div>
                    </div>
                  </Link>
                )}
                <MainNavLink
                  label="Home"
                  icon="i i-chart text-info"
                  to="/dashboard"
                  exact
                  notMyRole="sellerapp support"
                />
                <MainNavLink
                  label="Transactions"
                  icon="i i-repeat text-primary"
                  to={routes.transactions}
                  notMyRole="sellerapp"
                />
                <MainNavLink
                  label="Settlements"
                  icon="i i-done-all text-success"
                  to="/settlements"
                  notMyRole="sellerapp support"
                />

                <div class="divider" />

                <MainNavLink
                  label="Invoices"
                  icon="i i-notes text-warning"
                  to={routes.invoices}
                  featureEnabled="Invoice"
                  apiFeatureEnabled="subscriptions"
                  notMyRole="sellerapp"
                />
                <MainNavLink
                  label="Payment Links"
                  icon="i i-link text-primary"
                  to={routes.paymentlinks}
                />
                <MainNavLink
                  label="Route"
                  icon="i i-store text-success"
                  to={routes.marketplace}
                  notMyRole="sellerapp support"
                  isNew={true}
                />
                <MainNavLink
                  label="Subscriptions"
                  icon="i i-refresh text-info"
                  notMyRole="sellerapp support"
                  to={routes.subscriptions}
                  isNew={true}
                />
                <MainNavLink
                  label="Smart Collect"
                  icon="i i-account-balance text-danger"
                  to="/virtualaccounts"
                  notMyRole="sellerapp support"
                  isNew={true}
                />

                <MainNavLink
                  label="Customers"
                  icon="i i-people text-warning"
                  to="/customers"
                  featureEnabled="Invoice"
                  apiFeatureEnabled={['subscriptions', 'virtual_accounts']}
                  notMyRole="sellerapp"
                />

                <div class="divider" />

                <MainNavLink
                  label="Reports"
                  icon="i i-books text-danger"
                  to="/reports"
                  notMyRole="sellerapp support"
                  isPending={Object.keys(reportList).length > 0}
                />
                <MainNavLink
                  label="My Account"
                  icon="i i-account text-primary"
                  to={routes.account}
                />
                <MainNavLink
                  label="Settings"
                  icon="i i-settings text-warning"
                  to={routes.settings}
                  myRole="owner manager admin"
                />
              </div>;
            }
          }}
        </nav>
      </div>
    );
  }
}
