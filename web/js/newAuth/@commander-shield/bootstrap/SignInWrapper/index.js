import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { lightTheme } from '@razorpay/blade-old/src/tokens/theme';
import Wrapper from '../Wrapper';
import InitWrapper from '../Wrapper/InitWrapper';
import { SIGNIN } from '../../screens/screenHelpers';
import SignIn from './SignIn';

const SignInWrapper = ({ theme, orgData, ...props }) => {
  const customTheme = theme ? theme : lightTheme;
  const [route, setRoute] = useState();

  /*
  This route is used as the intermediate state
  where the user should be taken after landing on
  login screen
   */
  const handleSignInRoute = (customRoute) => {
    setRoute(customRoute);
  };

  return (
    <Wrapper theme={customTheme}>
      <InitWrapper
        lumberjackAppName="dashboard-signin"
        route={SIGNIN}
        handleSignInRoute={handleSignInRoute}
      >
        <SignIn {...props} orgData={orgData} route={route} />
      </InitWrapper>
    </Wrapper>
  );
};

SignInWrapper.propTypes = {
  theme: PropTypes.object,
  orgData: PropTypes.object,
  authClientId: PropTypes.string,
  oneTapInfo: PropTypes.object,
  isGoogleOauthEnabled: PropTypes.bool,
};

export default SignInWrapper;
