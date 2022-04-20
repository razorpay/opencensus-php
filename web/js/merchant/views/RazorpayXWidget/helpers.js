export const getKycAnalyticsProperties = () => {
  const {
    user: { signup_via_email },
    activation_status: vaActivationStatus,
    ca_activation_status,
    activated: pgKycActivated,
  } = window.rzp_user;

  return {
    VA_KYC_Activated: vaActivationStatus === 'activated',
    CA_Activated: ca_activation_status === 'activated',
    PG_KYC_Activated: !!pgKycActivated,
    signupMethod: signup_via_email ? 'email' : 'mobile',
  };
};
