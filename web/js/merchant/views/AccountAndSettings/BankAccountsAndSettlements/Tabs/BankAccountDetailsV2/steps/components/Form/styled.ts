import { Alert } from '@razorpay/blade/components';
import { Flexbox } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/common/styled';
import styled from 'styled-components';

export const StyledAlert = styled(Alert)`
  width: 100%;
`;

export const StyledForm = styled.form`
  display: flex;
  flex-direction: column;
  gap: 24px;
  width: 468px;
  @media screen and (max-width: 768px) {
    width: 100%;
  }
`;

export const FormInputWrapper = styled(Flexbox.Column)`
  gap: 16px;
  @media screen and (min-width: 768px) {
    min-height: 384px;
  }
`;

export const BeneficiaryAlertWrapper = styled(Flexbox.Row)`
  gap: 4px;
  align-items: center;
`;
