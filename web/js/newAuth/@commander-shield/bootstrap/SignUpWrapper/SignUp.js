import React, { useState } from 'react';
import PropTypes from 'prop-types';
import useUserContext from '../../user/useUserContext';
import useLocationQuery from '../../shared/useLocationQuery';
import SignUpScreen from '../../screens/SignUp/SignUp';
import SignUpWithGoogle from '../../screens/SignUpGoogle/SignUpGoogle';
import { authModes } from '../../screens/screenHelpers';
import { setReferralParams } from '../../utils/referralParams';
import { getSignUpScreen } from './getSignUpScreen';

const SignUp = (props) => {
  const [isFocusOnField, setIsFocusOnField] = useState(false);
  const locationQuery = useLocationQuery();
  const { state } = useUserContext();
  const screenQuery = locationQuery.get('screen');
  setReferralParams(locationQuery);

  const {
    onRouteChange,
    authClientId,
    oneTapInfo,
    showPasswordRules,
    skipCaptcha,
    autoReadOtpSignup,
    header,
    orgName,
  } = props;

  if (onRouteChange) {
    onRouteChange(screenQuery);
  }

  const SignUpGauth = (
    <SignUpWithGoogle
      isFocusOnField={isFocusOnField}
      authClientId={authClientId}
      isOneTapOn={oneTapInfo?.isExpOn}
      isOneTapScriptFailed={oneTapInfo?.isScriptFailed}
      isMobileNumberSignupEnabled={state.isMobileNumberSignupEnabled}
    />
  );

  const getSignupBasedOnAuthMode = (authMode) => {
    switch (authMode) {
      case authModes.PASSWORD:
      case authModes.OTP:
        return (
          <SignUpScreen
            handleFieldFocus={setIsFocusOnField}
            showPasswordRules={showPasswordRules} // [AB] Set Password UX - test
            skipCaptcha={skipCaptcha}
            autoReadOtpSignup={autoReadOtpSignup}
            orgName={orgName}
          />
        );
      case authModes.GAUTH:
        return SignUpGauth;
      default:
        return <div />;
    }
  };

  const SignupScreen = getSignupBasedOnAuthMode(state.authMode);

  return getSignUpScreen({
    screenQuery,
    defaultScreen: SignupScreen,
    header,
  });
};

SignUp.propTypes = {
  header: PropTypes.node,
  onRouteChange: PropTypes.func,
  authClientId: PropTypes.string,
  oneTapInfo: PropTypes.object,
  showPasswordRules: PropTypes.bool,
  autoReadOtpSignup: PropTypes.bool,
  orgName: PropTypes.string,
};

SignUp.defaultProps = {
  setIsFocusOnField: () => {},
};

export default SignUp;
