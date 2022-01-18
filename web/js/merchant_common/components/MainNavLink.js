import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import {
  setActivePageName as fnsetActivePageName,
  toggleMobileMenu as fntoggleMobileMenu,
} from 'merchant/reducers/app';
import RTracking from 'react-tracking';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { compose, bindActionCreators } from 'redux';
import * as LocalStorageService from 'common/utils/localStorage';

class MainNavLink extends Component {
  constructor(props) {
    super(props);

    this.setActivePageName = this.setActivePageName.bind(this);
    this.handleClick = this.handleClick.bind(this);
  }

  /**
   * Method that sends analytics regarding navigation.
   */
  sendAnalytics = () => {
    if (this.props.label && window.rzpAnalytics) {
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Side Nav',
        eventAction: `Go To - ${this.props.label}`,
      });
    }

    const { user, label, tracking } = this.props;
    if (label === 'Affiliate Accounts') {
      const userId = user.id ? user.id : '';
      tracking.trackEvent(
        window.rzpQ.onbr().interaction('partnerships.dashboard.affiliate_account', {
          partnerID: userId,
        }),
      );
    }
  };

  handleClick() {
    this.sendAnalytics();
    this.props.setActivePageName(this.props.label);
    const getRecommendedProduct =
      LocalStorageService.getItem('merchant_landing_page') ||
      LocalStorageService.getItem('default_product_page');

    const trackingRequired = ['Transactions', 'Settlements', 'Payment Pages'];
    const tracking = this.props.tracking;

    if (trackingRequired.includes(this.props.label)) {
      tracking.trackEvent(
        window.rzpQ.merchantActions().initiated(`${this.props.label}.click.initiated`),
      );
    }

    if (window.rzpQ.merchantActions() && window.rzpQ.merchantActions().clicked) {
      tracking.trackEvent(
        window.rzpQ.merchantActions().clicked(`dashboard.leftnav`, {
          menu_label: this.props.label,
          session_id: window.session_id,
        }),
      );
    }

    analyticsTrack({
      objectName: 'sidebar',
      actionName: 'clicked',
      screen: 'home page',
      toCleverTap: true,
      properties: {
        clickedElement: this.props.label,
        clickType: this.props.type,
        product_name: getRecommendedProduct,
        location: 'sidebar',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    if (
      this.props.label === 'App Store' &&
      window.rzpQ &&
      window.rzpQ.onbr().clicked &&
      window.rzp_user.merchant
    ) {
      tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.appstore', {
          merchantId: window.rzp_user.merchant.id,
        }),
      );
    }

    if (this.props.onClick) {
      this.props.onClick();
    }

    return this.props.isMobileResolution && this.props.toggleMobileMenu();
  }

  isActivePath(location = this.props.location, currentLink = this.props.to) {
    return location.pathname === currentLink;
  }

  setActivePageName(location, label) {
    return this.props.activePageName === label || this.isActivePath(location);
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.baseLocation && this.isActivePath(nextProps.baseLocation, nextProps.to)) {
      this.props.setActivePageName(nextProps.label);
    }
  }

  componentWillMount() {
    if (this.isActivePath()) {
      this.props.setActivePageName(this.props.label);
    }
  }

  render() {
    const {
      myRole,
      notMyRole,
      featureEnabled,
      apiFeatureEnabled,
      additionalCondition,
      icon,
      image,
      label,
      type,
      isNew,
      customBadge,
      isBeta = false,
      isPending,
      baseLocation,
      isCurrent,
      staticContext,
      isMobileResolution,
      isSettlementEnabled,
      isComingSoon,
      ...linkProps
    } = this.props;

    let tag, loader, logo;

    if (isBeta) {
      tag = <span class="badge bg-primary-fuse pull-right hidden-xs">beta</span>;
    } else if (isSettlementEnabled) {
      tag = (
        <span>
          <i className="i i-early-settlement settle-icon pull-right temp-icon-2" />
        </span>
      );
    } else if (isNew) {
      tag = <span class="badge bg-success pull-right hidden-xs">new</span>;
    } else if (!!customBadge) {
      tag = <span class="badge bg-success pull-right hidden-xs">{customBadge}</span>;
    } else if (isComingSoon) {
      tag = <span class="badge pull-right hidden-xs coming-soon-badge">Coming Soon!</span>;
    }
    if (isPending) {
      //show infinite spin loader if there are some pending items in that section of the app
      loader = <span class="spin-loader pull-right  hidden-xs" />;
    }

    if (image) {
      logo = <img src={image} alt={`${label} icon`} />;
    } else if (icon) {
      logo = <i class={icon} />;
    }

    return (
      <ShowWhen
        notMyRole={notMyRole}
        myRole={myRole}
        featureEnabled={featureEnabled}
        apiFeatureEnabled={apiFeatureEnabled}
        additionalCondition={additionalCondition}
      >
        <NavLink
          {...linkProps}
          isActive={() => this.setActivePageName(location, label)}
          onClick={this.handleClick}
          class="NavLink"
        >
          {logo}
          {label}
          {tag}
          {loader}
        </NavLink>
      </ShowWhen>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    baseLocation: state.app.baseLocation,
    isMobileResolution: state.app.isMobileResolution,
    activePageName: state.app.activePageName,
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    { setActivePageName: fnsetActivePageName, toggleMobileMenu: fntoggleMobileMenu },
    dispatch,
  );

export default compose(
  withRouter,
  connect(mapStateToProps, mapDispatchToProps),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('MainNavLink')),
)(MainNavLink);
