import { Flexbox } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/common/styled';
import styled from 'styled-components';

export const StyledDescriptionContent = styled(Flexbox.Column)`
  gap: 8px;
  text-align: center;
  align-items: center;
`;

export const StyledPartition = styled(Flexbox.Row)`
  display: flex;
  gap: 24px;
  align-items: center;
  width: 100%;
`;

export const StyledLayout = styled(Flexbox.Column)`
  gap: 24px;
  align-items: center;
  justify-content: center;
`;

export const RetryContainer = styled(Flexbox.Column)`
  gap: 32px;
  align-items: center;
  max-width: 468px;
  padding: 24px;
  @media screen and (max-width: 768px) {
    padding: 44px 24px;
  }
`;
