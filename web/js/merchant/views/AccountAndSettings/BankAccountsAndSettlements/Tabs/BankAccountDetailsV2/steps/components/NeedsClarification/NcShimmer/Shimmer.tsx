import Shimmer from 'common/components/Shimmer';
import { StyledDivider } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/common/styled';
import { StyledNeedsClarification } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/NeedsClarification/styled';
import React from 'react';

const NcShimmer = (): JSX.Element => {
  return (
    <StyledNeedsClarification data-testid="nc-shimmer">
      <Shimmer height="60px" width="100%" variant="rounded" />
      <StyledDivider />
      <Shimmer height="162px" width="100%" variant="rounded" />
      <Shimmer height="122px" width="100%" variant="rounded" />
      <StyledDivider />
      <Shimmer height="36px" width="100%" variant="rounded" />
    </StyledNeedsClarification>
  );
};

export default NcShimmer;
