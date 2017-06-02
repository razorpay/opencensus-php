import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

@connect(state => state.app)
export default class MainNavLink extends Component {
  render() {
    let {
      base,
      dispatch,
      myRole,
      notMyRole,
      featureEnabled,
      icon,
      label,
      activeRowId,
      luminateRowId,
      beta = false,
      ...linkProps
    } = this.props;

    return (
      <ShowWhen
        notMyRole={notMyRole}
        myRole={myRole}
        featureEnabled={featureEnabled}
      >
        <NavLink
          {...linkProps}
          isActive={(match, location) => {
            return (base || location).pathname === linkProps.to;
          }}
        >
          <i class={icon} />
          {label}
          {beta
            ? <span class="badge bg-success pull-right hidden-xs">beta</span>
            : null}
        </NavLink>
      </ShowWhen>
    );
  }
}
