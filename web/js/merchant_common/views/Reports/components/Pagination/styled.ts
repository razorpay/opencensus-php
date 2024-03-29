import styled from 'styled-components';
import { ClickableButton, flexCentered } from 'merchant_common/views/Reports/components/styled';
import { BaseValidationStyledProps } from 'merchant_common/views/Reports/components/types';

export const PaginationWrapper = styled.div(
  ({ theme }) => `
    ${flexCentered};
    flex-direction: column;
    padding-bottom: ${theme.spacing[3]}px;
  `,
);

export const PaginationDiv = styled.div(
  ({ theme }) => `
    ${flexCentered};
    padding: ${theme.spacing[2]}px;
    margin-top: ${theme.spacing[2]}px;
  `,
);

export const PageButton = styled(ClickableButton)<BaseValidationStyledProps>(
  ({ theme, focused, disabled }) => `
  ${flexCentered};
  width: 28px;
  height: 28px;
  margin: 0 ${theme.spacing[3]}px;
  cursor: pointer;
  border: none;
  border-radius: ${theme.border.radius.round};
  background-color: ${focused ? 'transparent' : 'transparent'};
  p {
    color: ${focused ? '#ffffff' : 'black'} !important;
  }
  ${
    !disabled &&
    `
    &:hover {
      background-color: transparent;
      p {
        color: #ffffff !important;
      }
    }
    `
  }

`,
);
