import { analyticsTrack } from 'common/utils/analytics';

/**
 * Checks the eligibility of a user for fee-based gating.
 *
 * @param {object} user - The user object containing information about the user.
 * @return {boolean} Returns true if the user is eligible for fee-based gating, false otherwise.
 */
export const checkEligibilityForFeeBasedGating = (user) => {
  return (
    Boolean(user?.fee_based_gating?.is_eligible) &&
    user?.activation_status === null &&
    user?.activation_form_milestone === 'L2'
  );
};

/**
 * Handles fee-based gating navigation.
 *
 * @return {undefined} No return value.
 */
export const handleFeeBasedGatingNavigation = (trackProps = {}) => {
  analyticsTrack({
    objectName: 'Fee Based Gating',
    actionName: 'Redirect',
    screen: 'home page',
    properties: {
      ctaClicked: 'Get KYC Verified',
      ...trackProps,
    },
  });
  const feeBasedGatingOnEasyUrl = `${window.EASY_ONBOARDING_URL}/onboarding/fee-payment`;
  window.open(feeBasedGatingOnEasyUrl, '_self', 'noopener');
};
