import { Component } from 'react';
import user from 'razorx/user';
import { Route, Redirect } from 'react-router-dom';

export default props => {
  return showWhenUtil(props) ? props.children : null;
};

export function showWhenUtil(props) {
  var permission = props.permission;
  var permissions = user.permissions;
  var isContentVisible = false;

  if (
    !permissions ||
    !permission ||
    permissions.find(perm => permission === perm)
  ) {
    isContentVisible = true;
  }

  if (isContentVisible && props.additionalCondition) {
    isContentVisible = props.additionalCondition(user);
  }

  return isContentVisible;
}

export function ShowWhenRoute({ component: Component, ...rest }) {
  return (
    <Route
      {...rest}
      render={props =>
        showWhenUtil(rest) ? (
          <Component {...rest} />
        ) : (
          <Redirect
            to={{
              pathname: '/admin',
              state: { from: rest.location, was404: true },
            }}
          />
        )
      }
    />
  );
}
