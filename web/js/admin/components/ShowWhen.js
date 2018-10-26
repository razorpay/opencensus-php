import { Component } from 'react';
import user from 'admin/user';

export default props => {
  return showWhenUtil(props) ? props.children : null;
};

export function showWhenUtil(props) {
  var permission = props.permission;
  var permissions = user.permissions;

  if (
    !permissions ||
    !permission ||
    permissions.find(perm => permission === perm)
  ) {
    return true;
  }

  return false;
}
