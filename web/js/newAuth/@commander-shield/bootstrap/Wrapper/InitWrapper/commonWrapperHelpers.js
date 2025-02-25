import { REMOVE_PRESIGNUP_FUNCTIONALITY } from '../../../shared/Experiments/Experiments';
import getExpStatus from '../../../utils/getExperimentStatus';
import { screenMap, goToScreen, goToDashboard } from '../../../screens/screenHelpers';

const handleGoToScreen = (redirectScreen, navigate, locationQuery, handleSignInRoute) => {
  if (handleSignInRoute) {
    handleSignInRoute(redirectScreen);
  } else {
    goToScreen({ screen: redirectScreen, navigate, locationQuery });
  }
};

/**
 * This method decides where to direct the user from below options:
 * 1. presignup questions
 * 2. dashboard
 * 3. verify email screen
 */
const handleDroppedOffUser = (user, actions, navigate, locationQuery, handleSignInRoute) => {
  const { isSignupViaEmail, isPreSignUpComplete, isConfirmed: isEmailConfirmed } = user;
  const redirectScreen = getExpStatus(user, REMOVE_PRESIGNUP_FUNCTIONALITY)
    ? screenMap.contactDetails
    : screenMap.businessType;

  actions.updateUser({ isSignupViaEmail });

  if (isPreSignUpComplete) {
    if (!isSignupViaEmail || isEmailConfirmed) {
      goToDashboard();
    } else {
      handleGoToScreen(screenMap.verifyEmail, navigate, locationQuery, handleSignInRoute);
    }
  } else {
    handleGoToScreen(redirectScreen, navigate, locationQuery, handleSignInRoute);
  }
};

export default handleDroppedOffUser;
