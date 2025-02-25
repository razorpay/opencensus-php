import React from 'react';
import { useNavigate } from 'react-router-dom';
import { useFormikContext } from 'formik';
import Button from '../../shared/Button';
import captureException, { sentryFlows } from '../../shared/captureException';
import { CenteredView } from '../../shared/ScreenViews/ScreenViews';
import useSnackbar from '../../shared/Snackbar/useSnackbar';
import useLocationQuery from '../../shared/useLocationQuery';

import useUserContext from '../../user/useUserContext';
import featureFlags from '../../utils/featureFlags';
import { authMethods, authModes } from '../screenHelpers';
import signInEvents from './signInEvents';
import { handleErrorCodes, UNRECOGNIZED_ERROR } from './signInHelpers';

function SignInOptions() {
  const { state, actions } = useUserContext();
  const formikProps = useFormikContext();
  const snackbar = useSnackbar();
  const navigate = useNavigate();
  const locationQuery = useLocationQuery();

  const setAuthModePassword = () => actions.updateState({ authMode: authModes.PASSWORD });
  const setEmailOTPSignInMethod = async () => {
    try {
      const otpApiResp = await actions.sendLoginOTP({
        email: formikProps.values.email,
      });

      actions.setAuthMethodAndAuthMode({ authMode: authModes.OTP, authMethod: authMethods.EMAIL });

      actions.updateUser({
        email: formikProps.values.email,
        mobileNumber: '',
        isMobileNumberVerified: true,
        otpToken: otpApiResp.data.token,
      });
    } catch (error) {
      const errorType = handleErrorCodes({
        error,
        navigate,
        locationQuery,
        updateUser: actions.updateUser,
      });
      if (errorType === UNRECOGNIZED_ERROR) {
        signInEvents.trackSignInNativeFailure({
          error: error.message,
        });
        captureException(error, {
          flow: sentryFlows.SIGNIN_WITH_EMAIL_OTP,
        });
        snackbar.error(error.message);
      }
    }
  };

  if (featureFlags.ENABLE_EMAIL_OTP_FLOW && state.authMethod === authMethods.EMAIL) {
    if (state.authMethod === authMethods.EMAIL && state.authMode === authModes.OTP) {
      return (
        <CenteredView padding={[0, 0, 0, 1]}>
          <Button variant="tertiary" onClick={setAuthModePassword}>
            Log In with Password
          </Button>
        </CenteredView>
      );
    }

    return (
      <CenteredView padding={[0, 0, 0, 1]}>
        <Button variant="tertiary" onClick={setEmailOTPSignInMethod}>
          Log In with Email OTP
        </Button>
      </CenteredView>
    );
  }

  return null;
}

export default SignInOptions;
