import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const OrderFooterWrapper = styled.div`
  position: fixed;
  background-color: white;
  bottom: 0;
  right: 0;
  left: 248px;
  z-index: 999999;

  @media (max-width: 1200px) {
    max-width: 1160px;
  }
  @media (max-width: 767px) {
    width: 100%;
    left: 0;
  }
`;

export const OrderCardItemContainer = styled.div`
  display: flex;
  flex-direction: column;
  justify-content: center;
  background-color: #f5f8fe;
  border-radius: 5px;
  padding: 8px 8px 8px 8px;
  margin: 8px 12px 8px 12px;
`;

export const StyledImg = styled.img`
  height: 40px;
  width: 40px;
  border-radius: 5px;
`;

export const OrderDetailsDeliverySectionDistributionContainer = styled.div`
  display: flex;
  flex-direction: column;
  justify-content: center;
  background-color: #f5f8fe;
  padding: 15px;
`;

export const IconContainer = styled.div(
  ({
    theme,
    withNoPadding,
    isOrderProcessed,
  }: {
    theme: Theme;
    withNoPadding?: boolean;
    isOrderProcessed?: boolean;
  }) => `
    height: ${theme.spacing[5]}px;
    width: ${theme.spacing[5]}px;
    margin-right: ${theme.spacing[4]}px;
    border-radius: ${theme.border.radius.round};
    margin: ${theme.spacing[1]}px 0;
    background-color: ${theme.colors.feedback.background.positive.intense};
    padding: ${withNoPadding ? theme.spacing[0] : theme.spacing[2]}px; 
    background-color:  ${
      isOrderProcessed
        ? theme.colors.feedback.background.positive.intense
        : theme.colors.interactive.border.gray.faded
    };
`,
);

export const CustomDivider = styled.div(
  ({ theme, isOrderProcessed }: { theme: Theme; isOrderProcessed?: boolean }) => `
    flex-grow: 1;
    width: 1px;
    margin-right: ${theme.spacing[6]}px;
    margin-left: ${theme.spacing[3]}px;
    border-radius: ${theme.border.radius.round};
    margin-bottom: ${theme.spacing[3]}px;
    margin-top: ${theme.spacing[2]}px;
    background-color: ${
      isOrderProcessed
        ? theme.colors.feedback.background.positive.intense
        : theme.colors.interactive.border.gray.faded
    };
`,
);
