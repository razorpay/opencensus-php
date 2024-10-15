import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const StyledInvoiceStatsCard = styled.div(
  ({ theme }: { theme: Theme }) => `
      display: flex;
      flex: 1;
      flex-direction: column;
      padding: ${theme.spacing[5]}px ${theme.spacing[0]}px ${theme.spacing[8]}px ${theme.spacing[6]}px;
      background-color: ${theme.colors.surface.background.gray.intense};
      border-right-color: ${theme.colors.surface.border.gray.subtle};
      border-right-width: ${theme.border.width.thinner}px;
      border-right-style: solid;
      cursor: pointer;
      pointer-events: all;
      transition: transform ${theme.motion.duration.quick}ms ease;
      &:hover {
        transform: scale(1.02)
      }

      @media screen and (max-width: ${theme.breakpoints.m}px) {
        padding: ${theme.spacing[3]}px ${theme.spacing[0]}px ${theme.spacing[6]}px ${theme.spacing[4]}px;
        min-width: 80%;
      }
    `,
);
