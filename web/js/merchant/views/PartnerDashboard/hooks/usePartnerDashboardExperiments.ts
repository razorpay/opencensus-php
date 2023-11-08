import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { getUser } from 'merchant/store';

/**
 * Accepts `user` to access any getters such as partner_type, isOrgRzp, older experiments, etc.
 */
const isEasierAccessToSubmerchantKycEnabled = ({ variant, user }) => {
  return user.isPartnershipsInviteFlowEnabled && isExperimentEnabled(variant);
};

const isPlatformPartnerInviteFlowEnabled = ({ variant, user }) => {
  return user.isPartner('pure_platform') && user.isOrgRZP && isExperimentEnabled(variant);
};

const isPartnershipCapitalBureauLinkEnabled = ({ variant }) => {
  return isExperimentEnabled(variant);
};

/**
 * A custom hook for consuming partner dashboard's specific experiments
 *
 */
const usePartnerDashboardExperiments = (): {
  isEasierAccessToSubmerchantKycEnabled: boolean;
  isPlatformPartnerInviteFlowEnabled: boolean;
  isPartnershipCapitalBureauLinkEnabled: boolean;
} => {
  const user = getUser();
  const {
    abExperiments: {
      // List of partner dashboard specific experiment labels here:
      partnerships_easier_access_to_submerchant_kyc,
      partnerships_oauth_phantom,
      partnership_capital_bureau_link,
    } = {},
  } = useSplitzService();
  return {
    isEasierAccessToSubmerchantKycEnabled: isEasierAccessToSubmerchantKycEnabled({
      variant: partnerships_easier_access_to_submerchant_kyc,
      user,
    }),
    isPlatformPartnerInviteFlowEnabled: isPlatformPartnerInviteFlowEnabled({
      variant: partnerships_oauth_phantom,
      user,
    }),
    isPartnershipCapitalBureauLinkEnabled: isPartnershipCapitalBureauLinkEnabled({
      variant: partnership_capital_bureau_link,
    }),
  };
};

export default usePartnerDashboardExperiments;
