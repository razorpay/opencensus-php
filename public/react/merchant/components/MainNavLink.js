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
  render() {
    let {
      myRole,
      notMyRole,
      featureEnabled,
      apiFeatureEnabled,
      icon,
      label,
      beta = false,
      baseLocation,
      ...linkProps
    } = this.props;

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
        >
          <i class={icon} />
          {label}
          {beta
            ? <span class="badge bg-primary-fuse pull-right hidden-xs">
                beta
              </span>
            : null}
        </NavLink>
      </ShowWhen>
    );
  }
}
