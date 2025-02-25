import { captureSigninException } from '../../../shared/captureException';
import { screenMap, goToScreen } from '../../../screens/screenHelpers';
import { signInUrlHelpers } from './urlHelpers';
import handleDroppedOffUser from './commonWrapperHelpers';

export const readUrlParams = async (navigate, locationQuery, actions, handleSignInRoute) => {
  try {
    actions.showLoader();
    const userDetailsRes = await actions.getUserDetails();
    // if user details exists, registration was initiated
    handleDroppedOffUser(userDetailsRes.user, actions, navigate, locationQuery, handleSignInRoute);
  } catch (error) {
    captureSigninException(error);
    goToScreen({ screen: screenMap.signIn, navigate, locationQuery });
  }

  signInUrlHelpers(locationQuery, actions);
};
