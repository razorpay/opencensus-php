import { Flexbox } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/common/styled';
import styled from 'styled-components';

export const StyledPrompt = styled(Flexbox.Row)`
  background: rgba(223, 135, 0, 0.09);
  border-radius: 8px 8px 8px 0px;
  width: 100%;
  padding: 8px 8px 12px 8px;
  gap: 8px;
  align-items: flex-start;
`;

export const DescriptionContent = styled(Flexbox.Column)`
  gap: 4px;
`;

export const StyledMessageIcon = styled.div`
  padding: 0 4px;
  color: #536582;
  i {
    font-size: 125%;
  }
`;
