import { useNavigate } from 'react-router-dom';
import useSnackbar from '../../shared/Snackbar/useSnackbar';
import useLocationQuery from '../../shared/useLocationQuery';
import useUserContext from '../../user/useUserContext';
import { goToScreen, screenMap, authMethods, authModes } from '../screenHelpers';
import signInEvents from '../SignIn/signInEvents';
import captureException, { sentryFlows } from '../../shared/captureException';

const useVerificationHelpers = () => {
  const { actions } = useUserContext();
  const locationQuery = useLocationQuery();
  const navigate = useNavigate();
  const snackbar = useSnackbar();

  const onVerificationSubmit = async (values) => {
    /** @type {{data: {token: string}}} sendOTPResponse */
    let sendOTPResponse;
    try {
      signInEvents.trackGetOtpInitiated();
      sendOTPResponse = await actions.userVerificationSendOTP({
        contact: values.contact,
        password: values.password,
      });
      signInEvents.trackGetOtpSuccess();
    } catch (err) {
      // @TODO: handle errors.
      captureException(err, {
        flow: sentryFlows.SIGNIN_WITH_MOBILE_OTP_VERIFY_USER,
      });
      signInEvents.trackGetOtpFailure();
      if (err?.message) {
        snackbar.error(err.message);
      }
      return;
    }

    actions.updateUser({
      email: '',
      mobileNumber: values.contact,
      password: values.password,
      otpToken: sendOTPResponse.data.token,
    });
    actions.setAuthMethodAndAuthMode({
      authMethod: authMethods.PHONE_NUMBER,
      authMode: authModes.OTP,
    });

    goToScreen({ screen: screenMap.signInMobile, navigate, locationQuery });
  };

  // Navigations
  const moveToPreviousScreen = () => {
    actions.resetAuthMethodAndAuthMode();
    goToScreen({ screen: screenMap.signIn, navigate, locationQuery });
  };

  const moveToForgotPasswordScreen = () => {
    goToScreen({ screen: screenMap.forgotPassword, navigate, locationQuery });
  };

  const handleOnClickChange = () => {
    signInEvents.trackChangeSignInMethodInitiate({ mode: authMethods.PHONE_NUMBER });
    moveToPreviousScreen();
  };

  const handleUseAnotherLogInOption = () => {
    signInEvents.trackSignInWithAnotherOptionInitiate({ method: authMethods.PHONE_NUMBER });
    moveToPreviousScreen();
  };

  const handleOnClickForgotPassword = () => {
    signInEvents.trackNonSignInActionsInitiate({
      action: 'click Forgot Password',
      method: authMethods.PHONE_NUMBER,
    });
    moveToForgotPasswordScreen();
  };

  return {
    onVerificationSubmit,
    handleOnClickChange,
    handleUseAnotherLogInOption,
    handleOnClickForgotPassword,
  };
};

export default useVerificationHelpers;
