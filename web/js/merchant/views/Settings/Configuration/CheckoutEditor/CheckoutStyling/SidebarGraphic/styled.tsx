import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const GraphicIconWrapper = styled.div(
  ({ theme, isSelected }: { theme: Theme; isSelected: boolean }) => `
    display: flex;
    width: ${theme.spacing[10]}px;
    height: ${theme.spacing[10]}px;
    padding: ${theme.spacing[4]}px;
    justify-content: center;
    align-items: center;
    border-radius: ${theme.spacing[2]}px;
    background: ${
      isSelected
        ? theme.colors.surface.background.primary.subtle
        : theme.colors.surface.background.gray.subtle
    };
    border: ${
      isSelected ? `1.5px solid ${theme.colors.interactive.border.primary.default}` : 'none'
    };
    cursor: pointer;
  `,
);
