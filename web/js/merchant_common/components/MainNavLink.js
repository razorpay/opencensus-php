import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import { isMobileDevice } from 'merchant/components/Home/data';
import { setActivePageName, toggleMobileMenu } from 'merchant/reducers/app';
import RTracking from 'react-tracking';
import analyticsService from '@commander/services/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

@connect(
  (state) => {
    return {
      baseLocation: state.app.baseLocation,
      isMobileResolution: state.app.isMobileResolution,
    };
  },
  { setActivePageName, toggleMobileMenu },
)
@withRouter
@RTracking(() => window.rzpQ.component('MainNavLink'))
export default class MainNavLink extends Component {
  constructor(props) {
    super(props);

    this.setActivePageName = this.setActivePageName.bind(this);
    this.handleClick = this.handleClick.bind(this);
  }

  /**
   * Method that sends analytics regarding navigation.
   */
  sendAnalytics = () => {
    this.props.label &&
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Side Nav',
        eventAction: `Go To - ${this.props.label}`,
      });
  };

  handleClick() {
    this.sendAnalytics();
    this.props.setActivePageName(this.props.label);

    const trackingRequired = ['Transactions', 'Settlements', 'Payment Pages'];
    const tracking = this.props.tracking;

    if (trackingRequired.includes(this.props.label)) {
      tracking.trackEvent(
        window.rzpQ.merchantActions().initiated(`${this.props.label}.click.initiated`),
      );
    }
    tracking.trackEvent(
      window.rzpQ.merchantActions().clicked(`dashboard.leftnav`, {
        menu_label: this.props.label,
        session_id: window.session_id,
      }),
    );

    analyticsService.track({
      objectName: 'sidebar',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        clickedElement: this.props.label,
        clickType: this.props.type,
        location: 'sidebar',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    if (this.props.label === 'Transactions') {
      analyticsService.track({
        objectName: 'transactions tab',
        actionName: 'clicked',
        screen: 'transactions',
        properties: {
          tabName: 'transactions',
          location: 'sidebar',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }

    return this.props.isMobileResolution && this.props.toggleMobileMenu();
  }

  isActivePath(location = this.props.location, currentLink = this.props.to) {
    return location.pathname === currentLink;
  }

  setActivePageName(match, location) {
    return this.isActivePath(location);
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
    let {
      myRole,
      notMyRole,
      featureEnabled,
      apiFeatureEnabled,
      additionalCondition,
      icon,
      label,
      type,
      isNew,
      customBadge,
      isBeta = false,
      isPending,
      baseLocation,
      isCurrent,
      setActivePageName,
      staticContext,
      isMobileResolution,
      toggleMobileMenu,
      isSettlementEnabled,
      ...linkProps
    } = this.props;

    let tag, loader;

    if (isBeta) {
      tag = <span class="badge bg-primary-fuse pull-right hidden-xs">beta</span>;
    } else if (isNew) {
      tag = <span class="badge bg-success pull-right hidden-xs">new</span>;
    } else if (customBadge) {
      tag = <span class="badge bg-success pull-right hidden-xs">{customBadge}</span>;
    } else if (isSettlementEnabled) {
      tag = <i className="i i-early-settlement settle-icon pull-right temp-icon-2" />;
    }
    //show infinite spin loader if there are some pending items in that section of the app
    if (isPending) {
      loader = <span class="spin-loader pull-right  hidden-xs" />;
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
          isActive={this.setActivePageName}
          onClick={this.handleClick}
          class="NavLink"
        >
          <i class={icon} />
          {label}
          {tag}
          {loader}
        </NavLink>
      </ShowWhen>
    );
  }
}
