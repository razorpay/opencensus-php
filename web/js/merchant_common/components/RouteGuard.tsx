import React from 'react';
import { connect } from 'react-redux';
import { Navigate } from 'react-router-dom';

import { withRouter } from 'common/deprecated/withRouter';
import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';
import Loader from 'common/ui/Loader';
import { checkIfPosSalesAgent } from 'common/utils/posAgent';

import type { WithRouterProps } from 'common/deprecated/RouteComponentProps';
import type { Store } from 'common/typings';

interface RouteGuardProps extends WithRouterProps {
  defaultPath?: string;
  customLoader?: JSX.Element;
  children: JSX.Element;
  session: Store['session'];
}

const TAGS_API_NOT_RESOLVED_YET = 'TAGS_API_NOT_RESOLVED_YET';

function convertToArray(arrayOrString) {
  if (arrayOrString) {
    return arrayOrString instanceof Array ? arrayOrString : [arrayOrString];
  }

  return arrayOrString;
}

export const validateUtil = (
  { options, session },
  extraConfig?: {
    i18: any;
    splitz: any;
  },
) => {
  const { notMyRole = '', myRole = '', additionalCondition } = options;
  const { i18, splitz } = extraConfig ?? {};
  let { apiFeatureEnabled, featureEnabled } = options;

  const user = session?.user;

  const isTagsDependent = options?.isTagDependent;
  const isTagsLoaded = session?.isTagsLoaded;

  if (!user) {
    return false;
  }

  if (isTagsDependent && !isTagsLoaded) {
    return TAGS_API_NOT_RESOLVED_YET;
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
    isContentVisible = additionalCondition(user, {
      session,
      i18,
      splitz,
    });
  }

  if (
    (myRole && myRoles.indexOf(userRole) === -1) ||
    (notMyRole && notMyRoles.indexOf(userRole) !== -1)
  ) {
    isContentVisible = false;
  }

  return isContentVisible;
};

export function showWhenUtil(store) {
  return (props, extraConfig?) => {
    const session = store.getState().session;
    return validateUtil({ session, options: props }, extraConfig);
  };
}

export const RouteGuardComponent = withRouter((props: RouteGuardProps) => {
  const {
    defaultPath,
    session,
    customLoader,
    children,
    location,
    params,
    navigate,
    history,
    match,
    ...rest
  } = props;
  const i18 = useI18Service();
  let fallbackPath = '';
  // creating an abstraction of just consuming splitz experiment, rest service should not be accessed via RouteGuard
  const splitz = useSplitzService();
  const { abExperiments } = splitz;

  const showWhenUtilResult = validateUtil(
    {
      options: rest,
      session,
    },
    {
      i18,
      splitz: { abExperiments },
    },
  );

  const user = session?.user;
  const { isPosSalesAgent, isPosEkycAgent } = checkIfPosSalesAgent({ user, abExperiments });

  if (!defaultPath && (isPosSalesAgent || isPosEkycAgent)) {
    //default path should be always pos-sales for POS sales agent
    fallbackPath = '/pos-sales';
  } else {
    fallbackPath = defaultPath ?? '/dashboard';
  }

  if (showWhenUtilResult === TAGS_API_NOT_RESOLVED_YET) {
    return customLoader || <Loader />;
  } else if (showWhenUtilResult) {
    return React.cloneElement(children, { location, params, navigate, history, match });
  } else {
    return <Navigate to={fallbackPath} state={{ from: location, was404: true }} replace />;
  }
});

export const ShowWhenComponent = (props) => {
  const i18 = useI18Service();
  // creating an abstraction of just consuming splitz experiment, rest service should not be accessed via RouteGuard
  const { abExperiments } = useSplitzService();

  const { loader, children, session, ...rest } = props;

  const showWhenUtilResult = validateUtil(
    {
      options: rest,
      session,
    },
    {
      i18,
      splitz: { abExperiments },
    },
  );

  if (showWhenUtilResult === TAGS_API_NOT_RESOLVED_YET) {
    return loader || <Loader />;
  } else if (showWhenUtilResult) {
    return children;
  }
  return null;
};

export const RouteGuard = connect(({ session }) => ({ session }))(RouteGuardComponent);
export const ShowWhen = connect(({ session }) => ({ session }))(ShowWhenComponent);
