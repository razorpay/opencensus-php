import { Component } from 'react';
import { NavLink, withRouter } from 'react-router-dom';
import ShowWhen from './ShowWhen';

export default class MainNavLink extends Component {
  render() {
    let {
      icon,
      children,
      isNew,
      isBeta = false,
      baseLocation,
      permission,
      ...linkProps
    } = this.props;

    let tag;

    if (isBeta) {
      tag = (
        <span class="badge bg-primary-fuse pull-right hidden-xs">beta</span>
      );
    } else if (isNew) {
      tag = <span class="badge bg-success pull-right hidden-xs">new</span>;
    }

    return (
      <ShowWhen permission={permission}>
        <NavLink
          {...linkProps}
          isActive={(match, location) => {
            return (baseLocation || location).pathname === linkProps.to;
          }}
        >
          <i class={icon} />
          {children}
          {tag}
        </NavLink>
      </ShowWhen>
    );
  }
}
