import { Component } from 'react';
import { NavLink } from 'react-router-dom';
import ShowWhen from './ShowWhen';

export default class MainNavLink extends Component {
  render() {
    const { icon, children, isNew, isBeta = false, permission, ...linkProps } = this.props;

    let tag;

    if (isBeta) {
      tag = <badge>beta</badge>;
    } else if (isNew) {
      tag = <badge>new</badge>;
    }

    return (
      <ShowWhen permission={permission}>
        <NavLink class="main-nav" {...linkProps}>
          <i class={icon} />
          {children}
          {tag}
        </NavLink>
      </ShowWhen>
    );
  }
}
