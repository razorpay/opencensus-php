import Shimmer from 'common/components/Shimmer';
import {
  HeaderTopBar,
  StyledAccountSectionContainer,
  StyledAccountSectionContent,
  StyledAccountSectionHeader,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/AccountSection/styled';
import {
  AccountDetails,
  StyledBankDetailsContainer,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/BankDetails/styled';
import { Device } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import React from 'react';

const ShimmerConfig = ['112px', '112px', '112px', '112px', '67px'];

const HomeShimmer = ({ isMobile }: Device): JSX.Element => {
  return (
    <StyledAccountSectionContainer data-testid="bankAccount-shimmer">
      <StyledAccountSectionHeader>
        <HeaderTopBar>
          <Shimmer width="40%" height="22px" variant="rounded" borderRadius="8px" />
        </HeaderTopBar>
        {isMobile ? (
          <>
            <Shimmer width="100%" height="18px" variant="rounded" borderRadius="4px" />
            <Shimmer width="85%" height="18px" variant="rounded" borderRadius="4px" />
            <Shimmer width="30%" height="18px" variant="rounded" borderRadius="4px" />
          </>
        ) : (
          <Shimmer width="60%" height="18px" variant="rounded" borderRadius="4px" />
        )}
      </StyledAccountSectionHeader>
      <StyledAccountSectionContent isSections={true}>
        <StyledBankDetailsContainer>
          <Shimmer width="57px" height="50px" variant="rounded" borderRadius="4px" />
          {ShimmerConfig.map(
            (each, index): JSX.Element => (
              <AccountDetails key={index}>
                <Shimmer width="83px" height="15px" variant="rounded" borderRadius="4px" />
                <Shimmer width={each} height="15px" variant="rounded" borderRadius="4px" />
              </AccountDetails>
            ),
          )}
        </StyledBankDetailsContainer>
      </StyledAccountSectionContent>
    </StyledAccountSectionContainer>
  );
};

export default HomeShimmer;
