import React from 'react';
import user from 'razorx/user';
import { Navigate } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';

export default (props) => {
  return showWhenUtil(props) ? props.children : null;
};

export function showWhenUtil(props) {
  const permission = props.permission;
  const permissions = user.permissions;
  let isContentVisible = false;

  if (!permissions || !permission || permissions.find((perm) => permission === perm)) {
    isContentVisible = true;
  }

  if (isContentVisible && props.additionalCondition) {
    isContentVisible = props.additionalCondition(user);
  }

  return isContentVisible;
}

export const RouteGuard = withRouter(
  ({
    defaultPath = '/admin',
    customLoader,
    children,
    location,
    params,
    navigate,
    history,
    ...rest
  }) => {
    const showWhenUtilResult = showWhenUtil(rest);
    if (showWhenUtilResult) {
      return React.cloneElement(children, { location, params, navigate, history });
    } else {
      return <Navigate to={defaultPath} state={{ from: location, was404: true }} replace />;
    }
  },
);
