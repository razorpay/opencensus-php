import { captureSignupException } from '../../../shared/captureException';
import { screenMap, goToScreen } from '../../../screens/screenHelpers';
import urlHelpers from './urlHelpers';
import handleDroppedOffUser from './commonWrapperHelpers';

export const readUrlParams = async (
  navigate,
  locationQuery,
  actions,
  showMobileSignup,
  defaultCoupon,
  orgName,
) => {
  try {
    let userDetailsRes;
    const screen = locationQuery.get('screen');
    if (screen) {
      try {
        userDetailsRes = await actions.getUserDetails();
        // if userDetailsRes does not returns 401 unauthorized(account wasn't created)
        handleDroppedOffUser(userDetailsRes.user, actions, navigate, locationQuery);
      } catch (error) {
        goToScreen({ screen: screenMap.signUp, navigate, locationQuery });
      }
    }
    urlHelpers(locationQuery, actions, showMobileSignup, defaultCoupon, orgName);
  } catch (err) {
    captureSignupException(err);
  }
};
