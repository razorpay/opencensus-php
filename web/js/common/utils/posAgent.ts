import { ExperimentInfoType } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import RolesList from 'merchant/helpers/permissions/roles-list';

interface CheckIfPosSalesAgentArgs {
  user: User;
  abExperiments: ExperimentInfoType;
}

interface CheckIfPosSalesAgentResponse {
  isEnabled: boolean;
  isPosSalesAgent: boolean;
  isOwner: boolean;
  isSalesAgentActingAsMerchant: boolean;
  isRzpSalesToPosAgentSwitchEnabled: boolean;
}

const defaultResponse = {
  isEnabled: false,
  isPosSalesAgent: false,
  isOwner: false,
  isSalesAgentActingAsMerchant: false,
  isRzpSalesToPosAgentSwitchEnabled: false,
};

export const checkIfPosSalesAgent = ({
  user,
  abExperiments,
}: CheckIfPosSalesAgentArgs): CheckIfPosSalesAgentResponse => {
  const posSalesAgentExp = abExperiments?.pos_sales_agent;

  if (!posSalesAgentExp) return defaultResponse;

  const isEnabled = isExperimentEnabled(posSalesAgentExp);
  const ezetapMidsSplitz = posSalesAgentExp?.variables?.ezetapMids;
  const isRzpSalesToPosAgentSwitchEnabled =
    posSalesAgentExp?.variables?.isRzpSalesToPosAgentSwitchEnabled === 'on';
  const ezetapMids = (typeof ezetapMidsSplitz === 'string' ? ezetapMidsSplitz : '').split(',');
  const isPosSalesAgent = !!(
    isEnabled &&
    user.isPartnerAgentRole &&
    ezetapMids.includes(user.current as string)
  );

  const isOwner = !!(isEnabled && ezetapMids.includes(user.current as string));
  const isSalesAgentActingAsMerchant = user.role === RolesList.RAZORPAY_SALES;
  return {
    ...defaultResponse,
    isEnabled,
    isPosSalesAgent,
    isOwner,
    isSalesAgentActingAsMerchant,
    isRzpSalesToPosAgentSwitchEnabled,
  };
};
