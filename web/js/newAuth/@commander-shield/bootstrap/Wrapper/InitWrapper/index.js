import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import analyticsService from '@razorpay/universe-utils/analytics';
import PropTypes from 'prop-types';
import useUserContext from '../../../user/useUserContext';
import captureException from '../../../shared/captureException';
import useLocationQuery from '../../../shared/useLocationQuery';
import ErrorScreen from '../../../shared/ErrorScreen';
import { SEGMENT_KEYS } from '../../../js/analytics';
import { SIGNUP, SIGNIN } from '../../../screens/screenHelpers';
import initWrapperEvents from './initWrapperEvents';
import { readUrlParams as readSignUpUrlParams } from './signUpWrapperHelpers';
import { readUrlParams as readSignInUrlParams } from './signInWrapperHelpers';

const InitWrapper = (props) => {
  const { state, actions } = useUserContext();
  const locationQuery = useLocationQuery();
  const navigate = useNavigate();
  const [isAppInitialized, setIsAppInitialized] = useState(false);

  const handleSignInRoute = (route) => {
    props.handleSignInRoute(route);
  };

  const initialize = async () => {
    try {
      initWrapperEvents.trackShieldLoad();

      if (props.initializeOrg) await actions.getOrgDetails();

      if (props.route === SIGNUP) {
        await readSignUpUrlParams(
          navigate,
          locationQuery,
          actions,
          props.showMobileSignup,
          props.defaultCoupon,
          props.orgName,
        );
      } else if (props.route === SIGNIN) {
        await readSignInUrlParams(navigate, locationQuery, actions, handleSignInRoute);
      }
      analyticsService.init({
        lumberjackAppName: props.lumberjackAppName,
        lumberjackApiKey: SEGMENT_KEYS.lumberjackApiKey,
        lumberjackApiUrl: SEGMENT_KEYS.lumberjackApiUrl,
        segmentApiKey: SEGMENT_KEYS.segmentApiKey,
      });
    } catch (error) {
      console.log(error);
      captureException(error);
      actions.showErrorScreen();
    } finally {
      setIsAppInitialized(true);
    }
  };

  useEffect(() => {
    initialize();
  }, []);

  if (!isAppInitialized) {
    return null;
  }

  if (state.hasError) {
    return <ErrorScreen />;
  }

  return props.children;
};

InitWrapper.propTypes = {
  lumberjackAppName: PropTypes.string,
  children: PropTypes.node,
  route: PropTypes.string,
  showMobileSignup: PropTypes.bool,
  defaultCoupon: PropTypes.string,
  initializeOrg: PropTypes.bool,
};

export default InitWrapper;
