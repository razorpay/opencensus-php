import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter, Link } from 'react-router-dom';
import RTracking from 'react-tracking';

import AcceptPaymentsModal from 'merchant/containers/Home/OnboardingCard/Instant/AcceptPaymentsModal';

import { toggleMobileMenu } from 'merchant/reducers/app';
import * as EventsActions from 'merchant/reducers/trackEvents';
import { showAcceptPaymentsModal, hideAcceptPaymentsModal } from 'merchant/reducers/home';

import ActivationProgress from './ActivationProgress';
import { isMobileDevice } from 'merchant/components/Home/data';
import MainNavLink from 'merchant_common/components/MainNavLink';
import MainNavLinkGroup from './MainNavLinkGroup';
import MerchantNavLinks from './MerchantNavLinks';
import PartnerNavLinks from './PartnerNavLinks';
import ShowWhen from 'merchant/components/ShowWhen';
import { isOrgFeatureExist } from 'merchant/models/User';

const TRANSACTIONS_ROUTES_REGEX = /^\/(payments|refunds|orders|batch-refunds)/;
const ACCOUNTS_ROUTES_REGEX = /^\/(trustedbadge|profile|credits|addfunds|referrals)/;
const SETTINGS_ROUTES_REGEX = /^\/(config|webhooks|keys|applications)/;
const INVOICES_ROUTES_REGEX = /^\/(invoices|items)/;
const MARKETPLACE_ROUTES_REGEX = /^\/route\/(payments|transfers|reversals|accounts)/;
const PAYMENTLINKS_ROUTES_REGEX = /^\/paymentlinks(\/batchuploads)?/;
const PAYMENTBUTTON_ROUTES_REGEX = /^\/paymentbuttons(\/subscription_buttons)?/;
const SUBSCRIPTIONS_ROUTES_REGEX = /^\/(subscriptions(\/batchuploads)?|plans|addons|recurring_payments|tokens|authlinks|registration_links)/;
const PARTNER_DASHBOARD_REGEX = /^\/(submerchants(\/(applications|settings))?|commissions)/;
const MAGIC_CHECKOUT_REGEX = /^\/(magic)/;

const RZPLogoFullPNG = 'https://cdn.razorpay.com/logo_invert.svg';

const BASE_ROUTES = {
  qrCodes: '/qr_codes',
  transactions: '/payments',
  account: '/profile',
  settings: '/config',
  invoices: '/invoices',
  marketplace: '/route/payments',
  paymentlinks: '/paymentlinks',
  paymentpages: '/paymentpages',
  paymentbuttons: '/paymentbuttons',
  subscription_buttons: '/subscription_buttons',
  subscriptions: '/subscriptions',
  chargeAtWill: '/recurring_payments',
  partnerDashboard: '/submerchants',
  smartCollect: '/smartcollect/virtualaccounts',
  bbps: '/bbps',
  magicCheckout: '/magic',
  stores: '/stores/products',
  developersApis: '/developers/apis',
};

@withRouter
@connect(
  (state) => ({
    showMobileMenu: state.app.showMobileMenu,
    showAcceptPayments: state.home.instantActivations.showAcceptPayments,
    org: state.session.org,
  }),
  { toggleMobileMenu, showAcceptPaymentsModal, hideAcceptPaymentsModal, ...EventsActions },
)
@RTracking(() => window.rzpQ.component('Sidebar'))
export default class Sidebar extends Component {
  constructor(props) {
    super(props);
    this.hideSidebar = this.hideSidebar.bind(this);
  }

  // currently active routes in tabbed containers
  // populated with initial values
  routes = { ...BASE_ROUTES };

  UNSAFE_componentWillReceiveProps(nextProps) {
    this.initializeRoutes(nextProps.location);
  }

  componentDidMount() {
    if (window.rzpQ && window.rzpQ.merchantActions) {
      this.props.tracking.trackEvent(window.rzpQ.merchantActions().success('Merchant_Logged_In'));
    }

    if (window.rzpQ && window.rzpQ.onbr) {
      this.props.tracking.trackEvent(window.rzpQ.onbr().initiated('dashboard.loaded'));
    }
  }

  initializeRoutes(location) {
    const pathname = location.pathname;
    const routes = this.routes;
    const user = this.props.user;

    // Selecting next route if default route is not available
    if (!user.isAllowedView('configuration')) {
      routes.settings = '/webhooks';
    }

    if (user.isOrgAxis) {
      routes.account = '/profile';
    }

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
    } else if (PAYMENTBUTTON_ROUTES_REGEX.test(pathname)) {
      routes.paymentbuttons = pathname.match(PAYMENTBUTTON_ROUTES_REGEX)[0];
      this.prevRoute = 'paymentbuttons';
    } else if (PARTNER_DASHBOARD_REGEX.test(pathname)) {
      routes.partnerDashboard = pathname.match(PARTNER_DASHBOARD_REGEX)[0];
      this.prevRoute = 'partnerDashboard';
    } else if (SUBSCRIPTIONS_ROUTES_REGEX.test(pathname)) {
      routes[user.isChargeAtWillEnabled ? 'chargeAtWill' : 'subscriptions'] = pathname.match(
        SUBSCRIPTIONS_ROUTES_REGEX,
      )[0];
      this.prevRoute = user.isChargeAtWillEnabled ? 'recurring_payments' : 'subscriptions';

      this.prevRoute = user.isRegistrationLinkBasedRole ? 'registration_links' : this.prevRoute;
    } else if (MAGIC_CHECKOUT_REGEX.test(pathname)) {
      routes.magicCheckout = pathname.match(MAGIC_CHECKOUT_REGEX)[0];
      this.prevRoute = 'magicCheckout';
    }

    if (user.isRegistrationLinkBasedRole) {
      this.routes.chargeAtWill = 'registration_links';
    }
  }

  onSidebarBannerClick = () => {
    const { user } = this.props;
    const isL1Submitted = user?.instantActivation?.isL1Submitted;

    const objectName = `${isL1Submitted ? 'L2' : 'L1'} Form`;

    this.props.trackEvents({
      objectName,
      actionName: 'initiated',
      screen: 'home page',
      properties: {
        ctaLabel: 'Account Activation',
        ctaLocation: 'LHS_Nav_Bar',
      },
    });

    if (user.isOnboardingV2Enabled && isMobileDevice()) {
      this.props.history.push('/onboarding/steps');
    } else if (user.isActivationFormFullView) {
      this.props.history.push('/kyc');
    } else {
      this.props.history.push('/activation');
    }
  };

  hideSidebar() {
    return this.props.showMobileMenu && this.props.toggleMobileMenu();
  }

  render() {
    const { user, config, logoURL, showMobileMenu, org } = this.props;
    const routes = this.routes;
    const isMerchant = !!user.current;
    const showExternalRedirect =
      isOrgFeatureExist('enable_external_redirect') &&
      org?.external_redirect_url_text &&
      org?.external_redirect_url;

    const merchantNavLinkProps = {
      routes,
      isChargeAtWillEnabled: user.isChargeAtWillEnabled,
      isSettlementEnabled: user.isOndemandSettlementEnabled || user.isAutomaticSettlementEnabled,
    };
    return (
      <>
        <div className={`sidebar${showMobileMenu ? ' show-mobile-menu' : ''}`}>
          <section className="brand-logo">
            <Link to="/dashboard" onClick={this.hideSidebar}>
              <img src={logoURL || RZPLogoFullPNG} />
            </Link>
          </section>
          <nav>
            {isMerchant && (
              <div className="nav">
                {!user.isOrgAxis ? (
                  <ActivationProgress
                    onSidebarBannerClick={this.onSidebarBannerClick}
                    user={user}
                    config={config}
                  />
                ) : null}

                {user.isPartner() ? (
                  <PartnerSidebar merchantNavLinkProps={merchantNavLinkProps} user={user} />
                ) : (
                  <MerchantNavLinks {...merchantNavLinkProps} user={user} />
                )}
                <ShowWhen additionalCondition={() => !isOrgFeatureExist('hide_razorpay_text_link')}>
                  <div className="open">
                    <MainNavLink
                      label="App Store"
                      icon="i i-app-store text-primary"
                      to="/app-store"
                      customBadge="NEW"
                    />
                  </div>
                </ShowWhen>
                <ShowWhen additionalCondition={() => showExternalRedirect}>
                  <div className="external-link-container">
                    <a
                      className="NavLink"
                      href={org?.external_redirect_url}
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      <i className="i i-external-link text-primary" />
                      &nbsp;
                      <span>{org?.external_redirect_url_text}</span>
                    </a>
                  </div>
                </ShowWhen>
              </div>
            )}
          </nav>
        </div>
        {showMobileMenu && <div className="sidebar-bg-overlay" onClick={this.hideSidebar} />}
        {/* `Accept modal` for universal access */}
        <AcceptPaymentsModal
          isKLA={user.has_key_access}
          shouldShow={this.props.showAcceptPayments}
          onClose={this.props.hideAcceptPaymentsModal}
        />
      </>
    );
  }
}

@withRouter
class PartnerSidebar extends Component {
  constructor(props) {
    super(props);
    const isPartnerRoute = this.isPartnerRoute(props);
    this.state = {
      partnerOpen: isPartnerRoute,
      merchantOpen: !isPartnerRoute,
    };
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    const isPartnerRoute = this.isPartnerRoute(nextProps);
    if (isPartnerRoute !== this.state.partnerOpen) {
      this.setState({
        partnerOpen: isPartnerRoute,
        merchantOpen: !isPartnerRoute,
      });
    }
  }

  isPartnerRoute = (props) =>
    props.location.pathname === '/' || props.location.pathname.includes('partners');

  toggle = (type) => () => {
    this.setState(
      (prevState) => ({
        [type]: !prevState[type],
        [this.getCounterType(type)]: prevState[type],
      }),
      () => {
        setTimeout(() => {
          this.props.history.push(this.getDefaultRoute(type));
        }, 600);
      },
    );
  };

  getCounterType = (type) => {
    return type === 'partnerOpen' ? 'merchantOpen' : 'partnerOpen';
  };

  getDefaultRoute = () => {
    const activeType = this.state.partnerOpen ? 'partnerOpen' : 'merchantOpen';
    return activeType === 'partnerOpen' ? '/partners' : '/dashboard';
  };

  render() {
    const props = this.props;
    const isPartnershipFUX = props?.user?.isPartnershipFUX || false;
    const fuxEnabledClass = isPartnershipFUX ? 'fux-enabled' : '';
    return (
      <div className={`nav-group ${fuxEnabledClass}`}>
        <MainNavLinkGroup
          title={<>{isPartnershipFUX ? null : <i className="i i-partner text-primary" />}Partner</>}
          onToggleClick={this.toggle('partnerOpen')}
          value={this.state.partnerOpen}
        >
          <PartnerNavLinks />
        </MainNavLinkGroup>

        <MainNavLinkGroup
          title={
            <>{isPartnershipFUX ? null : <i className="i i-products text-success" />}Products</>
          }
          onToggleClick={this.toggle('merchantOpen')}
          value={this.state.merchantOpen}
        >
          <MerchantNavLinks {...props.merchantNavLinkProps} user={props.user} />
        </MainNavLinkGroup>
      </div>
    );
  }
}
