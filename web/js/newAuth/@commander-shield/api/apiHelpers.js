export const monthlyRevenueCodeMap = {
  notProcessing: 1,
  lessThanFiveLakhs: 2,
  fiveLakhsToTwentyFiveLakhs: 3,
  twentyFiveLakhsToFiftyLakhs: 4,
  fiftyLakhsToOneCrore: 5,
  moreThanOneCrore: 6,
};

export const authApiResponse = {
  twoFactorRequired: 'BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED',
  twoFactorPasswordRequired: 'BAD_REQUEST_USER_2FA_LOGIN_PASSWORD_REQUIRED',
  twoFactorSetupRequired: 'BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED',
  twoFactorOtpIncorrect: 'BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP',
  accountBlocked: 'BAD_REQUEST_LOCKED_USER_LOGIN',
  accountNotFound: 'Razorpay Account Not Found.',
  contactNotVerified: 'BAD_REQUEST_CONTACT_MOBILE_NOT_VERIFIED',
  otpLoginLocked: 'BAD_REQUEST_OTP_LOGIN_LOCKED',
  signinIncorrectOTP: 'BAD_REQUEST_INCORRECT_OTP',
};

export const ACCOUNT_ALREADY_EXISTS =
  'Account already exists. Please click on Login to access your account.';

export const COMMON_ENDPOINTS = {
  ORG: '/org',
  USER: '/user?experiments=0&splitz_experiments=0',
  USER_REGISTER_GOOGLE: '/user/oauth-register',
  USER_LOGIN_GOOGLE: '/user/oauth-signin',
  UPDATE_TERMS_AND_CONDITIONS: '/merchant/api/live/merchant/activation',
};

export const twoFaErrors = [
  'BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED',
  'BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED',
  'BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP',
  'BAD_REQUEST_LOCKED_USER_LOGIN',
  'BAD_REQUEST_OTP_LOGIN_LOCKED',
  'BAD_REQUEST_USER_2FA_LOGIN_PASSWORD_REQUIRED',
];

export const IGNORE_MESSAGES = [
  'email has already been taken',
  'email must be a valid email address',
  'The password must', // must include x character, must be between x characters
  'captcha failed', // happen when captcha is expired
  'coupon code not found',
  'coupon code is expired',
  'coupon code may not be greater than 10 characters',
  'verification failed', // because of incorrect otp, because attempt threshold has been reached, because of incorrect password
  'incorrect email/contact mobile or password',
  'error connecting to google',
  'could not complete that action due to slow network',
  'popup_closed_by_user',
  'account already exists',
  'contact mobile has already been taken',
  'email or password you have entered is incorrect',
  'id provided does not exist',
  'sessionStorage is not available',
  'quota has been exceeded',
  'password you have entered is incorrect',
  'mobile number is associated with multiple accounts',
  'customer with this contact number already exists',
  'sms delivery failed',
  'verification attempt limit reached',
  'non-error promise rejection captured with value',
  'phone number is already taken',
  'user not authenticated',
  'BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS',
];

export const IGNORE_MESSAGE_ONLY_FOR_USER_API = 'Request failed with status code 401';
