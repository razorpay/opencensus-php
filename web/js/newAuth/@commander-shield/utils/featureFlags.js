const flags = {
  // window.isMobileOTPLogin is set from optimize
  ENABLE_MOBILE_OTP_FLOW: true,
  ENABLE_EMAIL_OTP_FLOW: window.isEmailOTPLogin || window.location.href.includes('email_otp=true'),
};

export default flags;
