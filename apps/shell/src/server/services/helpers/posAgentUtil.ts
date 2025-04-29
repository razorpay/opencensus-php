interface User {
  role?: string;
  current?: string;
}

interface CheckIfPosSalesAgentResponse {
  isPosSalesAgent: boolean;
  isPosEkycAgent: boolean;
}

interface CheckIfPosSalesAgentArgs {
  user: User;
  serverEvaluatedExperiments: Record<string, any>;
}

export const checkIfPosSalesAgent = ({
  user,
  serverEvaluatedExperiments,
}: CheckIfPosSalesAgentArgs): CheckIfPosSalesAgentResponse => {
  const defaultResponse = {
    isPosSalesAgent: false,
    isPosEkycAgent: false,
  };

  const posSalesAgentExp = serverEvaluatedExperiments?.['pos_sales_agent'];
  if (!posSalesAgentExp) return defaultResponse;

  const isEnabled = posSalesAgentExp?.enabled;

  const userRole = user?.role;
  const isPartnerAgentRole = userRole === 'partner_agent';

  const ezetapMidsSplitz = posSalesAgentExp?.ezetapMids;
  const ezetapMids = (typeof ezetapMidsSplitz === 'string' ? ezetapMidsSplitz : '').split(',');

  const isPosSalesAgent = !!(
    isEnabled &&
    isPartnerAgentRole &&
    ezetapMids.includes(user?.current as string)
  );

  const isPosEkycAgent = isPartnerAgentRole && !isPosSalesAgent;

  return {
    ...defaultResponse,
    isPosSalesAgent,
    isPosEkycAgent,
  };
};
