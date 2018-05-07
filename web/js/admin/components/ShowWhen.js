import { Component } from 'react';
import user from 'admin/user';

export default class ShowWhen extends Component {
  render() {
    var permission = this.props.permission;
    var permissions = user.permissions;

    if (
      !permissions ||
      !permission ||
      permissions.find(perm => permission === perm)
    ) {
      return this.props.children;
    }

    return null;
  }
}
