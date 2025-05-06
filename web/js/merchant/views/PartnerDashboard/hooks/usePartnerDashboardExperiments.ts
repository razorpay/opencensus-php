import { useMemo } from 'react';

import { useSplitzService } from 'common/splitz';
import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import { checkIfPosSalesAgent } from 'common/utils/posAgent';
import { filterBy, is2FaExperimentEnabled } from 'common/utils/rzp-utils';
import { getUser } from 'merchant/store';

import { PARTNER_TYPE } from '../constants';

/**
 * Accepts `user` to access any getters such as partner_type, isOrgRzp, older experiments, etc.
 */

const isPartnerInviteFlowEnabledForUser = ({ abExperiments, user }) => {
  if (user.isPartner(PARTNER_TYPE.RESELLER, PARTNER_TYPE.PURE_PLATFORM)) return true;

  /*
    Enable invite flow if it is present in the experiment variable for aggregator,
    TODO: Remove this explicit check once API changes are completed for aggregator
  */
  return user.isPartner(PARTNER_TYPE.AGGREGATOR)
    ? isExperimentEnabled(abExperiments?.partnerships_mkyc_aggregator)
    : false;
};

const isPartnershipsInviteFlowEnabled = ({ abExperiments, user }) => {
  // Note: this experiment is ramped 100% but the user checks are still needed.
  return user.isOrgRZP && isPartnerInviteFlowEnabledForUser({ abExperiments, user });
};

const isPlatformPartnerInviteFlowEnabled = ({ abExperiments, user }) => {
  return (
    user.isPartner('pure_platform') &&
    user.isOrgRZP &&
    isExperimentEnabled(abExperiments.partnerships_oauth_phantom)
  );
};

export const isPartnerPlaybookEnabled = ({ abExperiments, user }) => {
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
    user.isOrgRZP &&
    user.isPartner(PARTNER_TYPE.RESELLER) &&
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
    isPartnerInviteFlowEnabledForUser({ abExperiments, user })
  );
};
const isPartnershipCapitalBureauLinkEnabled = ({ abExperiments }) => {
  return isExperimentEnabled(abExperiments.partnership_capital_bureau_link);
};

const isFeatureEnabled = (feature, enabledFeatures) => {
  return enabledFeatures.some((item) => item.feature === feature);
};

export const getIsPosKycEnabled = ({ user }) => {
  const enabledFeatures = filterBy(user.features ?? [], 'value', true);
  return isFeatureEnabled('pos_channel_partnership', enabledFeatures);
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
  is2FaEnabled: boolean;
};
const usePartnerDashboardExperiments = (): PartnerDashboardExperiments => {
  const user = getUser();
  const { abExperiments = {} } = useSplitzService();
  return useMemo(
    () => ({
      isPartnershipsInviteFlowEnabled: isPartnershipsInviteFlowEnabled({
        abExperiments,
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
      isPosEkycEnabled: getIsPosKycEnabled({ user }),
      is2FaEnabled: is2FaExperimentEnabled(abExperiments),
    }),
    [user, abExperiments],
  );
};

export default usePartnerDashboardExperiments;
