import { useNavigate } from 'react-router-dom';
import {
  redirectTo,
  goToDashboard,
  goToScreen,
  screenMap,
  goToOnboardingScreen,
  setLoggedInViaInStorage,
  authMethods,
  setMidCookie,
} from '../screenHelpers';
import useUserContext from '../../user/useUserContext';
import useLocationQuery from '../../shared/useLocationQuery';
import { authApiResponse } from '../../api/apiHelpers';
import captureException, { sentryFlows } from '../../shared/captureException';
import useSnackbar from '../../shared/Snackbar/useSnackbar';
import signInEvents from '../SignIn/signInEvents';
import accountBlockEvents from '../AccountBlock/accountBlockEvents';
import twoFactorPasswordAuthEvents from './twoFactorAuthPasswordEvents';

const useTwoFAPasswordActions = () => {
  const { state, actions } = useUserContext();
  const locationQuery = useLocationQuery();
  const snackbar = useSnackbar();
  const navigate = useNavigate();

  const moveToForgotPasswordScreen = () => {
    signInEvents.trackNonSignInActionsInitiate({
      action: 'click Forgot Password',
      method: authMethods.PHONE_NUMBER,
    });
    goToScreen({ screen: screenMap.forgotPassword, navigate, locationQuery });
  };

  const goToLoginOptionsScreen = () => {
    signInEvents.trackSignInWithAnotherOptionInitiate({ method: authMethods.PHONE_NUMBER });
    goToScreen({ screen: screenMap.signIn, navigate, locationQuery });
  };

  const handleError = (error) => {
    const { message, internalData } = error;
    const { user } = state;
    const { email } = user;
    if (message === authApiResponse.accountBlocked) {
      accountBlockEvents.trackInitiated({
        email,
        method: authMethods.PHONE_NUMBER,
        flow: `${authMethods.PHONE_NUMBER}_2fa`,
      });
      actions.updateUser({
        isOwner: Boolean(internalData?.is_owner),
      });
      goToScreen({ screen: screenMap.accountBlock, navigate, locationQuery });
    } else {
      twoFactorPasswordAuthEvents.trackFailure(message);
      captureException(error, {
        flow: sentryFlows.TWO_FACTOR_PASSWORD,
      });
      snackbar.error(message);
    }
  };

  const proceedWithSignIn = async () => {
    try {
      const { user } = state;
      const { loggedInVia, redirectUrl } = user;
      setLoggedInViaInStorage(loggedInVia);
      const response = await actions.getUserDetails();
      setMidCookie(response?.user?.mid, response?.user?.id);
      twoFactorPasswordAuthEvents.trackSuccess();
      signInEvents.trackSignInSuccess({
        method: authMethods.PHONE_NUMBER,
        userId: response.user.id,
        mid: response.user.mid,
      });

      if (response.user.isConfirmed) {
        actions.showLoader();
        if (redirectUrl) {
          redirectTo(redirectUrl);
        } else {
          goToDashboard();
        }
      } else {
        goToOnboardingScreen({ user: response.user, navigate, locationQuery });
      }
    } catch (error) {
      handleError(error);
    }
  };

  const handleTncModalClose = () => {
    actions.updateUser({ show_tnc_popup: false });
  };

  const handleAcceptTnC = async () => {
    try {
      actions.updateUser({ show_tnc_popup: false });
      await actions.updateTermsAndConditions(true);
      proceedWithSignIn();
    } catch (error) {
      snackbar.error(error?.message);
    }
  };

  const on2FASubmit = async (values) => {
    try {
      twoFactorPasswordAuthEvents.trackInitiate();
      const verify2FAPasswordResponse = await actions.verify2FAPassword(values.password);
      const { user } = verify2FAPasswordResponse;
      // is show_tnc_popup is false then continue with signin else show the Terms And Conditions(TnC) popup & proceed once user accept the TnC
      if (!user?.show_tnc_popup) {
        proceedWithSignIn();
      }
    } catch (error) {
      handleError(error);
    }
  };

  return {
    on2FASubmit,
    moveToForgotPasswordScreen,
    goToLoginOptionsScreen,
    censoredEmail: state.user.censoredEmail,
    handleTncModalClose,
    handleAcceptTnC,
  };
};

export default useTwoFAPasswordActions;
