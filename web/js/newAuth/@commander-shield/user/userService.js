export const transformOrgApi = (data) => ({
  data: {
    org: {
      id: data.id,
      allowSignUp: data.allow_sign_up,
      authType: data.auth_type,
      createdAt: data.created_at,
      customCode: data.custom_code,
      defaultPricingPlanId: data.default_pricing_plan_id,
      displayName: data.display_name,
      email: data.email,
      emailDomains: data.email_domains,
      fromEmail: data.from_email,
      hostname: data.hostname,
      invoiceLogoUrl: data.invoice_logo_url,
      loginLogoUrl: data.login_logo_url,
      mainLogoUrl: data.main_logo_url,
      signatureEmail: data.signature_email,
    },
  },
});

export const transformGetUserDetailsApi = (data) => ({
  data: {
    user: {
      id: data.user.id,
      mid: data.current,
      name: data.user.name,
      email: data.user.email,
      contact: data.pre_signup.contact_mobile || '', // API return null instead of ''
      isAccountLocked: data.user.account_locked,
      isConfirmed: data.user.confirmed,
      isMobileVerified: data.user.contact_mobile_verified,
      isPreSignUpComplete: data.pre_signup_complete,
      experiments: data.experiments || [],
      features: data.features || [],
      isSignupViaEmail: !!data.user.signup_via_email,
      isOauthLogin: data.user.oauth_login,
      signupCampaign: data.user.signup_campaign,
      activationFormMilestone: data.activation_form_milestone,
      kycSubmitted: data.submitted,
    },
  },
});

export const transformValidateCouponApi = (data) => ({
  data: {
    user: {
      coupon: {
        credit: data.credit_amount / 100,
        expiry: data.expire_days,
      },
      previousValidatedCoupon: {
        credit: data.credit_amount / 100,
        expiry: data.expire_days,
      },
    },
  },
});

export const transformRegisterUserApi = (data) => ({
  data: {
    user: {
      token: data.token,
      loggedInVia: data.logged_in_via,
    },
  },
});

export const transformVerifySignUpOtpApi = (data) => ({
  data: {
    user: {
      id: data.id,
      name: data.name,
      email: data.email,
      contact: data.contact_mobile,
      loggedInVia: data.logged_in_via,
    },
  },
});

export const transformVerifyUserEmailApi = (data) => ({
  data: {
    user: {
      id: data.user.id,
      email: data.user.email,
      contact: data.user.contact_mobile,
      isMobileVerified: data.user.contact_mobile_verified,
      isConfirmed: data.user.confirmed,
      isAccountLocked: data.user.account_locked,
      isSecondFactorAuth: data.user.second_factor_auth,
      isSecondFactorAuthEnforced: data.user.second_factor_auth_enforced,
      isSecondFactorAuthSetup: data.user.second_factor_auth_setup,
      isRestricted: data.user.restricted,
    },
  },
});

export const transformVerifyInvitationCodeApi = (data) => ({
  data: {
    user: {
      email: data.email,
      role: data.role,
    },
  },
});

export const transformVerifyMerchantInvitationCodeApi = (data) => ({
  data: {
    user: {
      email: data.email,
      id: data.id,
      name: data.form_data.contact_name,
    },
  },
});

export const transformResendOtpApi = (data) => ({
  data: {
    user: {
      token: data.token,
    },
  },
});

export const transformRegisterUserWithGoogleApi = (data) => ({
  data: {
    user: {
      id: data.id,
      name: data.name,
      email: data.email,
      loggedInVia: data.logged_in_via,
    },
  },
});

export const transformLoginUserApi = (data) => ({
  data: {
    user: {
      id: data.id,
      loggedInVia: data.logged_in_via,
      show_tnc_popup: data.show_tnc_popup ?? false,
    },
  },
});

export const transformResetPasswordTokenApi = (data) => ({
  data: {
    user: {
      id: data.user_id,
      success: true,
    },
  },
});

export const transformVerifyOtpApi = (data) => ({
  data: {
    user: {
      loggedInVia: data.logged_in_via,
      show_tnc_popup: data.show_tnc_popup ?? false,
    },
  },
});

export const transformVerify2FAPasswordApi = (data) => ({
  data: {
    user: {
      loggedInVia: data.logged_in_via,
      show_tnc_popup: data.show_tnc_popup ?? false,
    },
  },
});
