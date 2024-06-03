import { ExperimentInfoType } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';

interface CheckIfPosSalesAgentArgs {
  user: User;
  abExperiments: ExperimentInfoType;
}

interface CheckIfPosSalesAgentResponse {
  isEnabled: boolean;
  isPosSalesAgent: boolean;
  isOwner: boolean;
}

const defaultResponse = {
  isEnabled: false,
  isPosSalesAgent: false,
  isOwner: false,
};

export const checkIfPosSalesAgent = ({
  user,
  abExperiments,
}: CheckIfPosSalesAgentArgs): CheckIfPosSalesAgentResponse => {
  const posSalesAgentExp = abExperiments?.pos_sales_agent;

  if (!posSalesAgentExp) return defaultResponse;

  const isEnabled = isExperimentEnabled(posSalesAgentExp);
  const ezetapMidsSplitz = posSalesAgentExp?.variables?.ezetapMids;
  const ezetapMids = (typeof ezetapMidsSplitz === 'string' ? ezetapMidsSplitz : '').split(',');
  const isPosSalesAgent = !!(
    isEnabled &&
    user.isPartnerAgentRole &&
    ezetapMids.includes(user.current as string)
  );

  const isOwner = !!(isEnabled && ezetapMids.includes(user.current as string));

  return {
    ...defaultResponse,
    isEnabled,
    isPosSalesAgent,
    isOwner,
  };
};
