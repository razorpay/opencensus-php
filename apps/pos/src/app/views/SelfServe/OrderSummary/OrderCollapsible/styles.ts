import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

export const OrderCollapsibleHeader = styled.div(
  ({ theme, isExpanded }: { theme: Theme; isExpanded: boolean }) => `
    padding: ${theme.spacing[5]}px;
    cursor: pointer;
    background-color: ${isExpanded ? theme.colors.surface.background.primary.subtle : 'none'};
    border-bottom: ${isExpanded ? theme.border.width.thick : '0'}px solid ${
    theme.colors.surface.border.gray.muted
  };
`,
);

export const OrderCollapsibleIconContainer = styled.div(
  ({ theme, isExpanded }: { theme: Theme; isExpanded: boolean }) => `
      padding: ${theme.spacing[3]}px;
      border-radius: ${theme.border.radius.large}px;
      background-color: ${
        isExpanded
          ? theme.colors.surface.background.primary.subtle
          : theme.colors.surface.background.gray.subtle
      };
      margin-right:${theme.spacing[4]}px;
      display: flex;
      align-items: center;
      justify-content: center;
  `,
);

export const StyledCollapsible = styled.div(
  ({ theme, isOpen }: { theme: Theme; isOpen: boolean }) => `
  display: ${isOpen ? 'block' : 'none'};
  max-height: ${isOpen ? '100%' : '0'};
  overflow: hidden;
  transition: all  0.1s ${theme.motion.easing.overshoot};
  `,
);
