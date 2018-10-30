import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter, Link } from 'react-router-dom';

import ProgressBar from 'rzp/ui/ProgressBar';
import { classList } from 'common/util';

import { toggleMobileMenu } from 'merchant/modules/app';
import MainNavLink from 'merchant/components/MainNavLink';
import ShowWhen from 'merchant/components/ShowWhen';
import { areReportsStillDownloading } from 'merchant/modules/reports';

import { trackGoToActivation, trackGoToConfig } from './ga';

const TRANSACTIONS_ROUTES_REGEX = /^\/(payments|refunds|orders|batch-refunds)/;
const ACCOUNTS_ROUTES_REGEX = /^\/(profile|credits|addfunds|referrals)/;
const SETTINGS_ROUTES_REGEX = /^\/(config|webhooks|keys|applications)/;
const INVOICES_ROUTES_REGEX = /^\/(invoices|items)/;
const MARKETPLACE_ROUTES_REGEX = /^\/route\/(payments|transfers|reversals|accounts)/;
const PAYMENTLINKS_ROUTES_REGEX = /^\/paymentlinks(\/batchuploads)?/;
const SUBSCRIPTIONS_ROUTES_REGEX = /^\/(subscriptions(\/batchuploads)?|plans|addons|recurring_payments|tokens|authlinks)/;

const RZPLogoFullPNG = 'https://cdn.razorpay.com/logo_invert.svg';

const BASE_ROUTES = {
  transactions: '/payments',
  account: '/profile',
  settings: '/config',
  invoices: '/invoices',
  marketplace: '/route/payments',
  paymentlinks: '/paymentlinks',
  paymentpages: '/paymentpages',
  subscriptions: '/subscriptions',
  request: '#request',
};

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

    this.onSidebarBannerClick = this.onSidebarBannerClick.bind(this);
    this.hideSidebar = this.hideSidebar.bind(this);
  }

  // currently active routes in tabbed containers
  // populated with initial values
  routes = { ...BASE_ROUTES };

  componentWillReceiveProps(nextProps) {
    this.initializeRoutes(nextProps.location);

    if (this.props.currentReportList !== nextProps.currentReportList) {
      this.setState({
        isReportsPending: areReportsStillDownloading(
          nextProps.currentReportList
        ),
      });
    }
  }

  initializeRoutes(location) {
    let pathname = location.pathname;
    let routes = this.routes;

    if (location.state && location.state.was404) {
      routes[this.prevRoute] = BASE_ROUTES[this.prevRoute]; // Assumption that these routes are always valid for any given role
    }

    if (TRANSACTIONS_ROUTES_REGEX.test(pathname)) {
      routes.transactions = pathname.match(TRANSACTIONS_ROUTES_REGEX)[0];
      this.prevRoute = 'transactions';
    } else if (ACCOUNTS_ROUTES_REGEX.test(pathname)) {
      routes.account = pathname.match(ACCOUNTS_ROUTES_REGEX)[0];
      this.prevRoute = 'account';
    } else if (SETTINGS_ROUTES_REGEX.test(pathname)) {
      routes.settings = pathname.match(SETTINGS_ROUTES_REGEX)[0];
      this.prevRoute = 'settings';
    } else if (INVOICES_ROUTES_REGEX.test(pathname)) {
      routes.invoices = pathname.match(INVOICES_ROUTES_REGEX)[0];
      this.prevRoute = 'invoices';
    } else if (MARKETPLACE_ROUTES_REGEX.test(pathname)) {
      routes.marketplace = pathname.match(MARKETPLACE_ROUTES_REGEX)[0];
      this.prevRoute = 'marketplace';
    } else if (PAYMENTLINKS_ROUTES_REGEX.test(pathname)) {
      routes.paymentlinks = pathname.match(PAYMENTLINKS_ROUTES_REGEX)[0];
      this.prevRoute = 'paymentlinks';
    } else if (SUBSCRIPTIONS_ROUTES_REGEX.test(pathname)) {
      routes.subscriptions = pathname.match(SUBSCRIPTIONS_ROUTES_REGEX)[0];
      this.prevRoute = 'subscriptions';
    }
  }

  onSidebarBannerClick() {
    if (this.props.showMobileMenu) {
      this.props.toggleMobileMenu();
    }

    return (this.props.user.isSubmitted
      ? trackGoToConfig
      : trackGoToActivation)();
  }

  hideSidebar() {
    return this.props.showMobileMenu && this.props.toggleMobileMenu();
  }

  render() {
    const { isReportsPending } = this.state;
    let { user, config, logoURL, showMobileMenu } = this.props;
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
                let actionCopy;

                if (user.activation_progress < 100) {
                  // If user form is still unfilled
                  actionCopy = 'Activate your account';
                } else if (user.isSubmitted) {
                  actionCopy = 'Form submitted';
                } else if (user.activation_progress == 100) {
                  // Form is unfilled and Not submitted
                  actionCopy = 'Submit Form';
                } else if (user.isActivated) {
                  actionCopy = 'Account Activated';
                }

                <div class="nav">
                  <ShowWhen
                    additionalCondition={user =>
                      user.isAllowedEdit('activation') &&
                      !user.isPartner() &&
                      (!user.isSubmitted || !config.hasPersonalised)
                    }
                  >
                    <Link
                      className="activation-status-link"
                      to={!user.isSubmitted ? '/activation' : '/config'}
                      onClick={this.onSidebarBannerClick}
                    >
                      <div
                        className={classList(
                          'activation-status',
                          user.isSubmitted && !config.hasPersonalised
                            ? 'not-personalised'
                            : ''
                        )}
                      >
                        <div className="clearfix">
                          <div className="pull-left">{actionCopy}</div>
                          <div className="pull-right">
                            <i className="i i-chevron-right" />
                          </div>
                        </div>
                        {!user.isSubmitted ? (
                          <div className="activation-bar-content activation-status-secondary">
                            <div className="activation-bar-text">
                              {user.activation_progress}% Complete
                            </div>
                            <div className="activation-bar">
                              <ProgressBar
                                type="success"
                                max={100}
                                value={user.activation_progress}
                              />
                            </div>
                          </div>
                        ) : (
                          <div className="activation-status-secondary">
                            Personalise your Account
                          </div>
                        )}
                      </div>
                    </Link>
                  </ShowWhen>

                  <MainNavLink
                    label="Partner Dashboard"
                    icon="i i-partner text-success"
                    to="/submerchants"
                    additionalCondition={user => user.isPartner()}
                    exact
                  />

                  <div class="divider" />

                  <MainNavLink
                    label="Home"
                    icon="i i-chart text-info"
                    to="/dashboard"
                    exact
                    additionalCondition={user => user.isAllowedView('home')}
                  />
                  <MainNavLink
                    label="Transactions"
                    icon="i i-repeat text-primary"
                    to={routes.transactions}
                    additionalCondition={user =>
                      user.isAllowedMultiple('payments orders refunds')
                    }
                  />
                  <MainNavLink
                    label="Settlements"
                    icon="i i-done-all text-success"
                    to="/settlements"
                    additionalCondition={user =>
                      user.isAllowedView('settlements')
                    }
                  />

                  <div class="divider" />

                  <MainNavLink
                    label="Invoices"
                    icon="i i-notes text-warning"
                    to={routes.invoices}
                    additionalCondition={user => user.isAllowedView('invoices')}
                    isNew
                  />
                  <MainNavLink
                    label="Payment Links"
                    icon="i i-link text-primary"
                    to={routes.paymentlinks}
                    additionalCondition={user =>
                      user.isAllowedView('payment_links')
                    }
                  />
                  <MainNavLink
                    label="Payment Pages"
                    icon="i i-payment-pages text-warm temp-icon-style"
                    to={routes.paymentpages}
                    featureEnabled="paymentpages"
                    additionalCondition={user =>
                      user.isAllowedView('payment_pages')
                    }
                    isNew
                  />
                  <MainNavLink
                    label="Route"
                    icon="i i-store text-success"
                    to={routes.marketplace}
                    additionalCondition={user =>
                      user.isAllowedView('marketplace')
                    }
                  />
                  <MainNavLink
                    label="Subscriptions"
                    icon="i i-refresh text-info"
                    additionalCondition={user =>
                      user.isAllowedView('subscriptions')
                    }
                    to={routes.subscriptions}
                  />
                  <MainNavLink
                    label="Smart Collect"
                    icon="i i-account-balance text-danger"
                    to="/virtualaccounts"
                    additionalCondition={user =>
                      user.isAllowedView('virtual_accounts')
                    }
                  />

                  <MainNavLink
                    label="Customers"
                    icon="i i-people text-warning"
                    to="/customers"
                    additionalCondition={user =>
                      user.isAllowedView('customers')
                    }
                  />

                  <div class="divider" />

                  <MainNavLink
                    label="Reports"
                    icon="i i-books text-danger"
                    to="/reports"
                    additionalCondition={user => user.isAllowedView('reports')}
                    isPending={isReportsPending}
                  />
                  <MainNavLink
                    label="My Account"
                    icon="i i-account text-primary"
                    additionalCondition={user =>
                      user.isAllowedMultiple(
                        'profile credits add_funds team referrals'
                      )
                    }
                    to={routes.account}
                  />
                  <MainNavLink
                    label="Settings"
                    icon="i i-settings text-warning"
                    to={routes.settings}
                    additionalCondition={user =>
                      user.isAllowedMultiple(
                        'webhooks applications configuration api_keys'
                      )
                    }
                  />
                  <MainNavLink
                    label="Contact Support"
                    icon="i i-support text-warning"
                    to={routes.request}
                    id="nav-contact-support"
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
