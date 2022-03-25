export const getKycAnalyticsProperties = () => {
  const {
    user: { signup_via_email },
    merchant: { activated: vaKycActivated },
    ca_activation_status,
    activated: pgKycActivated,
  } = window.rzp_user;

  return {
    VA_KYC_Activated: !!vaKycActivated,
    CA_Activated: ca_activation_status === 'activated',
    PG_KYC_Activated: !!pgKycActivated,
    signupMethod: signup_via_email ? 'email' : 'mobile',
  };
};
