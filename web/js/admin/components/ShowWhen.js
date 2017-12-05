import { Component, PropTypes } from 'react';
import user from 'admin/user';

export default class ShowWhen extends Component {
  render() {
    var permission = this.props.permission;
    var tag = this.props.tag;

    var permissions = user.permissions;
    var tags = user.tags;

    if (
      !(permissions && tags) ||
      (permissions && permissions.find(perm => permission === perm)) ||
      (tags && tags.find(t => tag === t))
    ) {
      return this.props.children;
    }

    return null;
  }
}
