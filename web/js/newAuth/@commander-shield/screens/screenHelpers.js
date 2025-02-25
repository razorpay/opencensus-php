import setCookie from '../utils/setCookie';
import { REMOVE_PRESIGNUP_FUNCTIONALITY } from '../shared/Experiments/Experiments';
import { evaluateExperiment, experimentDataMap } from '../utils/splitz';
import getExpStatus from '../utils/getExperimentStatus/getExperimentStatus';

export const OTP_FIELD_FORMIK_KEYNAME = 'otp';

export const signUpSrc = {
  websitePaymentLink: 'website',
  websiteHomePage: 'website_homepage',
};

export const setMidCookie = (mid, userId) => {
  try {
    if (mid) {
      setCookie('midExists', true);
      setCookie('rzp_mid', mid);
    }
    if (userId) {
      setCookie('rzp_userid', userId);
    }
  } catch (e) {
    // do nothing if cookie set fails
  }
};

export const RECAPTCHA_NULL_ERROR = 'We could not process your request, Please try again.';

export const screenMap = {
  signUp: 'sign_up',
  businessType: 'business_type',
  monthlyRevenue: 'monthly_revenue',
  contactDetails: 'contact_details',
  verifyEmail: 'verify_email',
  signIn: 'sign_in',
  verifyMobile: 'verify_mobile',
  signInMobile: 'sign_in_mobile',
  signInEmail: 'sign_in_email',
  setupTwoFactorAuth: 'setup_2fa',
  twoFactorAuthPassword: '2fa_password',
  forgotPassword: 'forgot_password',
  twoFactorAuth: '2fa',
  accountBlock: 'account_block',
};

export const signUpHeaders = {
  default: 'Welcome to Razorpay',
  paymentLink: 'Start transacting now!',
};

// Below constant represents various methods via which user can signin/signup
export const authMethods = {
  GAUTH: 'google_oauth',
  EMAIL: 'email',
  PHONE_NUMBER: 'phone_number',
};

/**
 * Below constant represents various authentication modes after
 * user has given their authentication Method(authMethods)
 */
export const authModes = {
  GAUTH: 'google_oauth_token',
  OTP: 'otp',
  PASSWORD: 'password',
};

export const getSignUpHeading = (heading) => signUpHeaders[heading] || signUpHeaders.default;

export const SIGNUP = 'SIGNUP';
export const SIGNIN = 'SIGNIN';
export const RESET_PASSWORD = 'RESET_PASSWORD';

const NO_INFO = 'no_info';
export const SCREEN_TRANSITION_TIME_IN_MS = 500; //millisecond

export const getScreenIndex = (screen) =>
  Object.values(screenMap).indexOf(screen || screenMap.signUp);

export const goToScreen = ({ screen, navigate, locationQuery, state }) => {
  const searchParamsObj = new URLSearchParams(location.search);
  searchParamsObj.delete('screen');
  const searchParamsString = searchParamsObj.toString();
  const searchParamsWithoutScreen = searchParamsString ? `&${searchParamsString}` : '';

  if (locationQuery) {
    navigate(`?screen=${screen}${searchParamsWithoutScreen}`, {
      state: {
        previousScreenIndex: getScreenIndex(locationQuery.get('screen')),
        previousScreen: locationQuery.get('screen'),
        ...state,
      },
    });
  } else {
    navigate(`?screen=${screen}${searchParamsWithoutScreen}`, { state });
  }
};

export const goToDashboardSignIn = () => {
  window.location.href = '/signin';
};

export const goToOnboardingScreen = ({ user, navigate, locationQuery }) => {
  const nextScreen = getExpStatus(user, REMOVE_PRESIGNUP_FUNCTIONALITY)
    ? screenMap.contactDetails
    : screenMap.businessType;
  goToScreen({ screen: nextScreen, navigate, locationQuery });
};

// redirect away from /signup or /signin
export const redirectTo = (url) => {
  window.location.href = url;
};

export const gotoSignup = (screen) => {
  window.location.href = `/signup${screen ? `?screen=${screen}` : ''}`;
};

export const setSignupExpData = () => {
  try {
    /* Using 'sign_up_exp_status' in dashboard to know that sign up is completed
     and user has logged in for the first time. Updating the value of 
     'sign_up_exp_status' once user opens the kyc form */
    window?.localStorage.setItem('sign_up_exp_status', 'sign_up_completed');
  } catch (error) {
    // do nothing
  }
};

export const goToDashboard = (signUpSource) => {
  if (
    signUpSource === signUpSrc.websitePaymentLink ||
    signUpSource === signUpSrc.websiteHomePage ||
    process.env.UNIVERSE_PUBLIC_SHIELD_APP_REDIRECT_URL // Allow override functionality only if the config is present
  ) {
    parent.location.href =
      process.env.UNIVERSE_PUBLIC_SHIELD_APP_REDIRECT_URL || // Use UNIVERSE_PUBLIC_SHIELD_APP_REDIRECT_URL in env file to redirect to a different url
      'https://dashboard.razorpay.com/app/dashboard';
  } else {
    window.location.href = '/app/dashboard';
  }
};

/** check user is eligible to redirect to easy onboarding */
export const canRedirectToEasyDashboard = (user, orgName = '') => {
  const isEasyOnboardingMerchant = user.signupCampaign === 'easy_onboarding';
  const isOauthLogin = user.isOauthLogin;
  const isRzpOrg = orgName === 'rzp';
  const isExpEnabled = evaluateExperiment(experimentDataMap.oauth_easy_onboarding);

  // if user has not submitted kyc or not filled L1 form then redirect to easy onboarding
  // kycSubmitted && activationFormMilestone ==> Boolean
  //   true       &&   true                  ==> true      // handle new merchant
  //   true       &&   false                 ==> false     ............. L1 merchant
  //   false      &&   false                 ==> false     ............. L2 merchant
  //   false      &&   true                  ==> false     // handle all old merchant
  const isKYCNotStated = !user.kycSubmitted && !user.activationFormMilestone;

  return isOauthLogin && (isEasyOnboardingMerchant || isExpEnabled) && isKYCNotStated && isRzpOrg;
};

export const goToActivation = (signUpSource) => {
  setSignupExpData();
  if (signUpSource === signUpSrc.websitePaymentLink || signUpSource === signUpSrc.websiteHomePage) {
    parent.location.href = 'https://dashboard.razorpay.com/app/activation';
  } else {
    window.location.href = 'app/activation';
  }
};

export const businessTypeOptions = {
  registered: [
    { value: 'proprietorship', title: 'Proprietorship' },
    { value: 'privateLimited', title: 'Private Limited' },
    { value: 'partnership', title: 'Partnership' },
    { value: 'publicLimited', title: 'Public Limited' },
    { value: 'llp', title: 'LLP' },
    { value: 'trust', title: 'Trust' },
    { value: 'society', title: 'Society' },
    { value: 'ngo', title: 'NGO' },
  ],
  unregistered: [{ value: 'unregistered', title: 'Unregistered' }],
};

export const monthlyRevenueOptions = [
  { value: 'notProcessing', title: 'Haven’t started processing yet' },
  { value: 'lessThanFiveLakhs', title: 'Less than 5 Lakhs' },
  { value: 'fiveLakhsToTwentyFiveLakhs', title: '5 Lakhs to 25 Lakhs' },
  { value: 'twentyFiveLakhsToFiftyLakhs', title: '25 Lakhs to 50 Lakhs' },
  { value: 'fiftyLakhsToOneCrore', title: '50 Lakhs to 1 Crore' },
  { value: 'moreThanOneCrore', title: 'More than 1 Crore' },
];

export const getOptionTitle = (list, value) => {
  let title = '';
  list.some((option) => {
    if (option.value === value) {
      title = option.title;
    }
    return option.value === value;
  });
  return title;
};

export const setLocalStorage = (name, value) => {
  try {
    window?.localStorage.setItem(name, value);
  } catch (error) {
    console.error('Local storage is not supported in the browser.');
  }
};

export const setLoggedInViaInStorage = (value = NO_INFO, email) => {
  /*
  If value is present, store that in loggedInVia
  If value is empty
   - check if email is present -> store 'email' as loggedInVia
   - else store NO_INFO as loggedInVia
   */
  if (!value && email) {
    value = authMethods.EMAIL;
  }
  setLocalStorage('loggedInVia', value);
};

export const getOptionLabel = (list, value) => {
  let label = '';
  list.some((option) => {
    if (option.id === value) {
      label = option.label;
    }
    return option.id === value;
  });
  return label;
};

export const UNREGISTERED_BUSINESS_ID = '11';

const JK_ORG_CODE = 'jkb';
const OMNI_ENABLED_FLAG = 'omni_enabled';

export const isJKOmniEnabled = (features, orgName) => {
  return features?.includes(OMNI_ENABLED_FLAG) && orgName === JK_ORG_CODE;
};
