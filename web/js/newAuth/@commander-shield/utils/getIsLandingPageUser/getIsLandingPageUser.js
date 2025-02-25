import readCookie from '../readCookie';
import setCookie from '../setCookie';
import { getAttribUtmData } from '../getUtmData/getUtmData';

const getIsLandingPageUser = () => {
  /*
   check is the current page is the first ever page visited by the
   user on razorpay.com. Populate the same in the cookie `isLandingPageUser`
   isLandingPageUser will be true only for the first ever page visit of the user
   the subsequent visit on the same page, isLandingPageUser will be false
   isLandingPageUser will only be true for new users
   For existing users it will be false
   This logic covers all the cases of different urls like
   dashboard.razorpay.com/signup/, dashboard.razorpay.com/signup,
   dashboard.razorpay.com/signup//, dashboard.razorpay.com/signup?,
   dashboard.razorpay.com/signup/? etc.
  */
  let isLandingPageUser;
  const currentPage =
    window.location.hostname.toLowerCase() + (window.location.pathname || '').toLowerCase();

  const rzpUtmCookie = getAttribUtmData();
  const isLandingPageUserInCookie = readCookie('isLandingPageUser');
  const signupUrl = 'dashboard.razorpay.com/signup';

  if (
    rzpUtmCookie.isNewUser &&
    currentPage.includes(signupUrl) &&
    rzpUtmCookie.firstPage.includes(signupUrl) &&
    !isLandingPageUserInCookie
  ) {
    setCookie('isLandingPageUser', true);
    isLandingPageUser = true;
  } else {
    isLandingPageUser = false;
  }

  return isLandingPageUser;
};

export default getIsLandingPageUser;
