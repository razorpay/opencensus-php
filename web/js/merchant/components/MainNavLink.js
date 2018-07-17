import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import { isMobileDevice } from 'merchant/components/Home/data';
import { setActivePageName, toggleMobileMenu } from 'merchant/modules/app';

@connect(
  state => {
    return {
      baseLocation: state.app.baseLocation,
      isMobileResolution: state.app.isMobileResolution,
    };
  },
  { setActivePageName, toggleMobileMenu }
)
@withRouter
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

    return this.props.isMobileResolution && this.props.toggleMobileMenu();
  }

  isActivePath(location = this.props.location, currentLink = this.props.to) {
    return location.pathname === currentLink;
  }

  setActivePageName(match, location) {
    return this.isActivePath(location);
  }

  componentWillReceiveProps(nextProps) {
    if (
      nextProps.baseLocation &&
      this.isActivePath(nextProps.baseLocation, nextProps.to)
    ) {
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
      icon,
      label,
      isNew,
      isBeta = false,
      isPending,
      baseLocation,
      setActivePageName,
      staticContext,
      isMobileResolution,
      toggleMobileMenu,
      ...linkProps
    } = this.props;

    let tag, loader;

    if (isBeta) {
      tag = (
        <span class="badge bg-primary-fuse pull-right hidden-xs">beta</span>
      );
    } else if (isNew) {
      tag = <span class="badge bg-success pull-right hidden-xs">new</span>;
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
      >
        <NavLink
          {...linkProps}
          isActive={this.setActivePageName}
          onClick={this.handleClick}
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
