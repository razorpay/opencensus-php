import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

export const RightChildrenWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
    display: flex;
    justify-content: center;
    align-items: center;
    gap: ${theme.spacing[3]}px;
  `,
);

export const SingleChildren = styled.div(
  ({ theme, isSelected }: { theme: Theme; isSelected: boolean }) => `
    display: flex;
    padding: 4px ${theme.spacing[4]}px;
    align-items: center;
    gap: ${theme.spacing[2]}px;
    border-radius: ${theme.border.radius.max}px;
    border: 1.5px solid ${
      isSelected
        ? theme.colors.interactive.border.primary.default
        : theme.colors.interactive.border.gray.faded
    };
    cursor: pointer;
    background:   ${isSelected ? theme.colors.interactive.background.primary.faded : ''};
    `,
);
