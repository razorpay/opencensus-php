import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const StyledInfo = styled.div(
  ({ theme }: { theme: Theme }) => `
    display: flex;
    justify-content: space-between; 
    gap: ${theme.spacing[2]}px;
    @media screen and(min-width: 768px) {
      align-items: center;
      justify-content: initial;
    }
    p {
      word-break: break-all;
    }
  `,
);

export const TooltipContainer = styled.div<any>(
  ({ theme, isTooltipAction }: { theme: Theme; isTooltipAction: boolean | undefined }) => `
  color: #324664;
  .rzp-tooltip {
    width: 242px !important;
    .rzp-tooltip-inner {
      padding: 10px;
      box-shadow: 0px 3px 8px rgba(21, 45, 75, 0.1), 0px 0px 1px rgba(21, 45, 75, 0.1);
      border-radius: ${theme.spacing[2]}px;
      .rzp-popover-content {
        .rzp-popover-body {
          div {
            white-space: pre-line;
          }
        }
      }
    }
  }
  button > div > div {
    color: ${theme.colors.interactive.text.primary.subtle};
  }
  &:hover {
    ${
      isTooltipAction
        ? `
          button > div > div {
            color: ${theme.colors.interactive.text.primary.disabled};
          }
        `
        : ''
    }
  }
  `,
);
