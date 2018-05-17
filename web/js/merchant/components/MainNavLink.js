import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

@connect(state => {
  return {
    baseLocation: state.app.baseLocation,
  };
}, {})
export default class MainNavLink extends Component {
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
    console.log();
    //show infinite spin loader if there are some pending items in that section of the app
    if (isPending) {
      loader = <span class="spin-loader" />;
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
          isActive={(match, location) => {
            return (baseLocation || location).pathname === linkProps.to;
          }}
          onClick={this.sendAnalytics}
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
