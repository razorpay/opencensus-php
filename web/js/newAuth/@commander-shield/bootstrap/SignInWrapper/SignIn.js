import React, { useState } from 'react';
import { useLocation } from 'react-router-dom';
import PropTypes from 'prop-types';
import { screenMap } from '../../screens/screenHelpers';
import SignInScreen from '../../screens/SignIn/SignIn';
import ForgotPassword from '../../screens/ForgotPassword/ForgotPassword';
import TwoFactorAuth from '../../screens/TwoFactorAuth/TwoFactorAuth';
import SignInGoogle from '../../screens/SignInGoogle/SignInGoogle';
import VerifyMobileNumber from '../../screens/VerifyMobileNumber';
import useLocationQuery from '../../shared/useLocationQuery';
import AnimationScreen from '../Wrapper/AnimationScreen';
import AccountBlock from '../../screens/AccountBlock/AccountBlock';
import SetupTwoFactorAuth from '../../screens/SetupTwoFactorAuth/SetupTwoFactorAuth';
import { getSignUpScreen } from '../SignUpWrapper/getSignUpScreen';
import TwoFactorAuthPassword from '../../screens/TwoFactorAuthPassword/TwoFactorAuthPassword';

const shouldDisableTransition = (newScreen, previousScreen) => {
  /**
   * Transition is disabled for following flows-
   *
   * sign_in -> sign_in_email
   * sign_in -> sign_in_mobile
   * sign_in <- sign_in_email
   * sign_in <- sign_in_mobile
   *
   */

  // if new screen is sign_in_email or sign_in_mobile
  if (newScreen === screenMap.signInEmail || newScreen === screenMap.signInMobile) {
    return true;
  }

  // if new screen is `sign_in` and previous screen was sign_in_email or sign_in_mobile
  if (
    newScreen === screenMap.signIn &&
    (previousScreen === screenMap.signInMobile || previousScreen === screenMap.signInEmail)
  ) {
    return true;
  }

  return false;
};

// eslint-disable-next-line complexity
const SignIn = (props) => {
  const [isFocusOnField, setIsFocusOnField] = useState(false);
  const location = useLocation();
  const locationQuery = useLocationQuery();
  let screenQuery = locationQuery.get('screen');

  /*
  If screen param is present in the url for e.g. signin/screen=sign_in
  or signin/screen=verify_email, then give preference to that
  Why? Bcz it means user is within the flow and changing screens
  or user refreshed on a particular screen
  
  else if props.route is present that is decided when user lands on
  login screen (based on user api), the user can be routed to
  business type screen or verify email screen.
  Why? props.route is passed to this component when user lands on the
  login page and is already a signed up user(session is maintained)
  whose pre signup question is not done or email is not verified.
  
  This is added to handle routing the user to intermediate screens
  like business screen, verify email screen in 2 cases
  1. user lands on signin
  2. user completes signin
  All the logic is based on `/user` api
   */
  if (props.route) {
    screenQuery = screenQuery ? screenQuery : props.route;
  }

  let ScreenComponent;
  // eslint-disable-next-line no-unused-vars
  const defaultScreen = (
    <SignInGoogle
      authClientId={props.authClientId}
      isOneTapOn={props.oneTapInfo?.isExpOn}
      isOneTapScriptFailed={props.oneTapInfo?.isScriptFailed}
      isFocusOnField={isFocusOnField}
      isGoogleOauthEnabled={props.isGoogleOauthEnabled}
      orgName={props?.orgData?.orgName}
    />
  );

  switch (screenQuery) {
    case screenMap.signInEmail:
    case screenMap.signInMobile:
      ScreenComponent = (
        <SignInScreen
          skipCaptcha={props.skipCaptcha}
          handleFieldFocus={setIsFocusOnField}
          isGoogleOauthEnabled={props.isGoogleOauthEnabled}
          orgName={props?.orgData?.orgName}
        />
      );
      break;
    case screenMap.verifyMobile:
      ScreenComponent = <VerifyMobileNumber />;
      break;
    case screenMap.forgotPassword:
      ScreenComponent = <ForgotPassword />;
      break;
    case screenMap.twoFactorAuth:
      ScreenComponent = <TwoFactorAuth />;
      break;
    case screenMap.twoFactorAuthPassword:
      ScreenComponent = <TwoFactorAuthPassword isGoogleOauthEnabled={props.isGoogleOauthEnabled} />;
      break;
    case screenMap.accountBlock:
      ScreenComponent = <AccountBlock orgName={props?.orgData?.orgName} />;
      break;
    case screenMap.setupTwoFactorAuth:
      ScreenComponent = <SetupTwoFactorAuth />;
      break;
    default:
      ScreenComponent = defaultScreen;
  }

  const previousScreen = location.state?.previousScreen;

  /*
  These screens are part of signup process
   */
  if (
    screenQuery === screenMap.businessType ||
    screenQuery === screenMap.verifyEmail ||
    screenQuery === screenMap.monthlyRevenue ||
    screenQuery === screenMap.contactDetails
  ) {
    return getSignUpScreen({ screenQuery, ScreenComponent });
  } else {
    return (
      <AnimationScreen disableTransition={shouldDisableTransition(screenQuery, previousScreen)}>
        {ScreenComponent}
      </AnimationScreen>
    );
  }
};

SignIn.propTypes = {
  authClientId: PropTypes.string,
  oneTapInfo: PropTypes.object,
  isGoogleOauthEnabled: PropTypes.bool,
  orgData: PropTypes.object,
};

export default SignIn;
