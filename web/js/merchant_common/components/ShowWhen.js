import React from 'react';
import { Route, Redirect } from 'react-router-dom';

function convertToArray(arrayOrString) {
  if (arrayOrString) {
    return arrayOrString instanceof Array ? arrayOrString : [arrayOrString];
  }

  return arrayOrString;
}

export default (store) => (props) => {
  return showWhenUtil(store)(props) ? props.children : null;
};

export function showWhenUtil(store) {
  return (props) => {
    const { notMyRole = '', myRole = '', additionalCondition } = props;

    let { apiFeatureEnabled, featureEnabled } = props;

    const user = store && store.getState().session.user;

    if (!user) {
      return false;
    }

    if (myRole && notMyRole) {
      throw new Error("myRole and notMyRole can't coexist for component ShowWhen");
    }

    const myRoles = myRole.split(' ');
    const notMyRoles = notMyRole.split(' ');
    let tags = (user.isAuthenticated && user.tags) || [];
    // const features = (user.isAuthenticated && user.features) || [];
    tags = tags.map((tag) => tag.toLowerCase());
    let userRole;

    let isContentVisible = false;

    if (user.isAuthenticated) {
      userRole = user.userRole;
    }

    /*
     * (Greater the no., higher the priority)
     * Show content when
     * - both feature or apiFeature does not exist (0)
     * - feature or apiFeature exists and is enabled for merchabt (1)
     * - user role has access to component (2)
     *
     * Don't show when
     * - feature or apiFeature key exists but is not enabled for merchant (3)
     * - user role does not have access to component (4)
     *
     * Numbers after the item represent the priority of the item
     */

    apiFeatureEnabled = convertToArray(apiFeatureEnabled);
    featureEnabled = convertToArray(featureEnabled);

    if (!apiFeatureEnabled && !featureEnabled) {
      isContentVisible = true;
    } else if (apiFeatureEnabled && apiFeatureEnabled.some((r) => user.isFeatureEnabled(r))) {
      isContentVisible = true;
    } else if (featureEnabled && featureEnabled.some((r) => tags.includes(r.toLowerCase()))) {
      isContentVisible = true;
    }

    if (isContentVisible && additionalCondition) {
      isContentVisible = additionalCondition(user);
    }

    if (
      (myRole && myRoles.indexOf(userRole) === -1) ||
      (notMyRole && notMyRoles.indexOf(userRole) !== -1)
    ) {
      isContentVisible = false;
    }

    return isContentVisible;
  };
}

export function ShowWhenRoute(store, defaultPath = '/dashboard') {
  return ({ component: Component, ...rest }) => (
    <Route
      {...rest}
      render={() =>
        showWhenUtil(store)(rest) ? (
          <Component {...rest} />
        ) : (
          <Redirect
            to={{
              pathname: defaultPath,
              state: { from: rest.location, was404: true },
            }}
          />
        )
      }
    />
  );
}
