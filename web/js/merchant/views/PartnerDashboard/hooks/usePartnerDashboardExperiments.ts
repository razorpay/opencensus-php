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

const isPartnerPlaybookEnabled = ({ variant, user }) => {
  return user.isOrgRZP && isExperimentEnabled(variant);
};

const isPartnershipCapitalBureauLinkEnabled = ({ variant }) => {
  return isExperimentEnabled(variant);
};

const isPartnershipsForPosEnabled = ({ variant, user }) => {
  return user.isPartner('reseller') && user.isOrgRZP && isExperimentEnabled(variant);
};

/**
 * A custom hook for consuming partner dashboard's specific experiments
 *
 */
const usePartnerDashboardExperiments = (): {
  isEasierAccessToSubmerchantKycEnabled: boolean;
  isPlatformPartnerInviteFlowEnabled: boolean;
  isPartnerPlaybookEnabled: boolean;
  isPartnershipCapitalBureauLinkEnabled: boolean;
  isPartnershipsForPosEnabled: boolean;
} => {
  const user = getUser();
  const {
    abExperiments: {
      // List of partner dashboard specific experiment labels here:
      partnerships_easier_access_to_submerchant_kyc,
      partnerships_oauth_phantom,
      partnerships_partner_playbook,
      partnership_capital_bureau_link,
      partnerships_for_pos,
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
    isPartnerPlaybookEnabled: isPartnerPlaybookEnabled({
      variant: partnerships_partner_playbook,
      user,
    }),
    isPartnershipCapitalBureauLinkEnabled: isPartnershipCapitalBureauLinkEnabled({
      variant: partnership_capital_bureau_link,
    }),
    isPartnershipsForPosEnabled: isPartnershipsForPosEnabled({
      variant: partnerships_for_pos,
      user,
    }),
  };
};

export default usePartnerDashboardExperiments;
