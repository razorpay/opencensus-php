import styled from 'styled-components';
import { StyledTabContentContainer } from 'merchant/views/AccountAndSettings/styled';
import { Theme } from '@razorpay/blade/components';

export const PaymentMethodsStyledTabContentContainer = styled(StyledTabContentContainer)(
  ({ theme }: { theme: Theme }) => `
  && {
    border-radius: ${theme.spacing[2]}px ${theme.spacing[2]}px 0 0;
    box-shadow: 0px 1px 2px rgba(21, 45, 75, 0.2), 0px 0px 1px rgba(21, 45, 75, 0.2);
    padding-top: 0;
    border: none;
    margin-top: ${theme.spacing[7]}px;
  }
`,
);

export const SectionHeader = styled.div(
  ({ theme }: { theme: Theme }) => `
  padding: ${theme.spacing[7]}px 0;
  border-bottom: 1px solid rgba(121, 135, 156, 0.18);
  border-radius: ${theme.spacing[2]}px ${theme.spacing[2]}px 0 0;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: top;
  background: #ffffff;

  h3 {
    font-weight: 600;
    color: ${theme.colors.surface.text.gray.normal};
    font-size: 18px;
    line-height: 28px;
    margin-bottom: ${theme.spacing[3]}px ;
  }

  p {
    font-weight: 400;
    font-size: 14px;
    line-height: ${theme.spacing[6]}px ;
    color: ${theme.colors.surface.text.gray.subtle};
  }

  a {
    line-height: 28px;
    font-weight: 600;
    font-size: 14px;
    line-height: ${theme.spacing[6]}px ;
    height: max-content;
  }

  @media screen and (max-width: 768px) {
    flex-direction: column;
    row-gap: ${theme.spacing[5]}px ;
    margin: 0 ${theme.spacing[5]}px ;
    padding: ${theme.spacing[5]}px  0 ${theme.spacing[7]}px;
  }
`,
);

export const SectionContent = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin-top: ${theme.spacing[5]}px ;
  height: auto !important;
  background: #ffffff !important;
  border: none !important;
  filter: drop-shadow(0px 1px 2px rgba(21, 45, 75, 0.2))
    drop-shadow(0px 0px 1px rgba(21, 45, 75, 0.2)) !important;
  width: max-content !important;

  .level-3 {
    padding: ${theme.spacing[5]}px  !important;
    width: auto !important;
  }
`,
);
