import { useMemo } from 'react';

import { useSplitzService } from 'common/splitz';
import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import { checkIfPosSalesAgent } from 'common/utils/posAgent';
import { getUser } from 'merchant/store';

/**
 * Accepts `user` to access any getters such as partner_type, isOrgRzp, older experiments, etc.
 */
const isPartnershipsInviteFlowEnabled = ({ user }) => {
  // Note: this experiment is ramped 100% but the user checks are still needed.
  return user.isPartner('reseller') && user.isOrgRZP;
};

const isPlatformPartnerInviteFlowEnabled = ({ abExperiments, user }) => {
  return (
    user.isPartner('pure_platform') &&
    user.isOrgRZP &&
    isExperimentEnabled(abExperiments.partnerships_oauth_phantom)
  );
};

const isPartnerPlaybookEnabled = ({ abExperiments, user }) => {
  return (
    !user.isPartnerAgentRole &&
    user.isOrgRZP &&
    isExperimentEnabled(abExperiments.partnerships_partner_playbook)
  );
};

type isPartnershipsForPosEnabledArgs = {
  abExperiments: SpiltzContextState['abExperiments'];
  user: User;
};
export const isPartnershipsForPosEnabled = ({
  abExperiments,
  user,
}: isPartnershipsForPosEnabledArgs): boolean => {
  return (
    user.isPartner('reseller') &&
    user.isOrgRZP &&
    isExperimentEnabled(abExperiments?.partnerships_accounts_list_revamp) &&
    (user.isPartnerAgentRole || isExperimentEnabled(abExperiments?.partnerships_for_pos))
  );
};

const isAccountsListRevampEnabled = ({ abExperiments, user }) => {
  const { partnerships_accounts_list_revamp } = abExperiments;
  return (
    // TODO v2: test for curlec and remove the user.isOrgRZP check
    user.isOrgRZP &&
    isExperimentEnabled(partnerships_accounts_list_revamp) &&
    (partnerships_accounts_list_revamp.variables?.skip_pos_check === 'on' ||
      isPartnershipsForPosEnabled({ abExperiments, user }))
  );
};
const isPartnershipCapitalBureauLinkEnabled = ({ abExperiments }) => {
  return isExperimentEnabled(abExperiments.partnership_capital_bureau_link);
};

/**
 * A custom hook for consuming partner dashboard's specific experiments
 *
 */
export type PartnerDashboardExperiments = {
  isPartnershipsInviteFlowEnabled: boolean;
  isPlatformPartnerInviteFlowEnabled: boolean;
  isPartnerPlaybookEnabled: boolean;
  isAccountsListRevampEnabled: boolean;
  isPartnershipsForPosEnabled: boolean;
  isPartnershipCapitalBureauLinkEnabled: boolean;
  isPosPartnerOwnerAccount: boolean;
};
const usePartnerDashboardExperiments = (): PartnerDashboardExperiments => {
  const user = getUser();
  const { abExperiments = {} } = useSplitzService();
  return useMemo(
    () => ({
      isPartnershipsInviteFlowEnabled: isPartnershipsInviteFlowEnabled({
        user,
      }),
      isPlatformPartnerInviteFlowEnabled: isPlatformPartnerInviteFlowEnabled({
        abExperiments,
        user,
      }),
      isPartnerPlaybookEnabled: isPartnerPlaybookEnabled({
        abExperiments,
        user,
      }),
      isPartnershipsForPosEnabled: isPartnershipsForPosEnabled({
        abExperiments,
        user,
      }),
      isAccountsListRevampEnabled: isAccountsListRevampEnabled({
        abExperiments,
        user,
      }),
      isPartnershipCapitalBureauLinkEnabled: isPartnershipCapitalBureauLinkEnabled({
        abExperiments,
      }),
      isPosPartnerOwnerAccount: !!checkIfPosSalesAgent({ user, abExperiments })?.isOwner,
    }),
    [user, abExperiments],
  );
};

export default usePartnerDashboardExperiments;
