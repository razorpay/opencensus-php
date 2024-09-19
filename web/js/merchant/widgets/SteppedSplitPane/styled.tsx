import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const StepSelectorHighlight = styled.div(
  ({ theme }: { theme: Theme }) => `
    width: ${theme.spacing[3]}px;
    background-color: ${theme.colors.surface.border.primary.normal};
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
  `,
);

interface StepSelectorProps {
  theme: Theme;
  isSelected: boolean;
  isImmediateNext: boolean;
  isLoading: boolean;
}

export const StepSelectorButton = styled.button(
  ({ theme, isSelected, isImmediateNext, isLoading }: StepSelectorProps) => {
    return `
      width: 100%;
      background-color: ${
        isSelected
          ? theme.colors.surface.background.gray.intense
          : theme.colors.surface.background.gray.moderate
      };
      border: ${
        isSelected
          ? 'none'
          : `${theme.border.width.thin}px solid ${theme.colors.surface.border.gray.muted}`
      };
      ${isImmediateNext ? '' : 'border-top: none;'}
      border-left: ${theme.border.width.thin}px solid ${theme.colors.surface.border.gray.muted};
      border-top-right-radius: ${theme.border.radius.medium}px;
      border-bottom-right-radius: ${theme.border.radius.medium}px;
      padding: ${theme.spacing[6]}px;
      display: flex;
      align-items: center;
      position: relative;
      flex: 1;

      &:hover {
        background-color: ${!isLoading ? theme.colors.surface.background.gray.subtle : ''};
      }

      &:first-child {
        border-top-left-radius: ${theme.border.radius.medium}px;
        border-top: ${
          isSelected
            ? 'none'
            : `${theme.border.width.thin}px solid ${theme.colors.surface.border.gray.muted}`
        };
      }

      &:last-child {
        border-bottom-left-radius: ${theme.border.radius.medium}px;
      }
    `;
  },
);
