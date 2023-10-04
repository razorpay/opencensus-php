import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { getUser } from 'merchant/store';

/**
 * Accepts `user` to access any getters such as partner_type, isOrgRzp, older experiments, etc.
 */
const isEasierAccessToSubmerchantKycEnabled = ({ variant, user }) => {
  return user.isPartnershipsInviteFlowEnabled && isExperimentEnabled(variant);
};

/**
 * A custom hook for consuming partner dashboard's specific experiments
 *
 */
const usePartnerDashboardExperiments = (): Record<string, boolean> => {
  const user = getUser();
  const {
    abExperiments: {
      // List of partner dashboard specific experiment labels here:
      partnerships_easier_access_to_submerchant_kyc,
    } = {},
  } = useSplitzService();
  return {
    isEasierAccessToSubmerchantKycEnabled: isEasierAccessToSubmerchantKycEnabled({
      variant: partnerships_easier_access_to_submerchant_kyc,
      user,
    }),
  };
};

export default usePartnerDashboardExperiments;
