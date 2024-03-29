import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const OverviewWrapper = styled.div`
  margin: 8px 0 12px 0;
`;

export const LegendDot = styled.div<{ color: string }>`
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: ${({ color }) => color};
`;

export const StyledAmount = styled.div(
  ({ theme }: { theme: Theme }) => `
  [data-blade-component='amount'] div,
  span {
    font-size: ${theme.typography.fonts.size[700]}px;
  }
`,
);

export const BoxWithWordBreak = styled.div`
  white-space: normal;
`;

export const BorderWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  border-radius: 2px;
  margin-top:  ${theme.spacing[5]}px;
  border: 1px solid ${theme.colors.surface.border.gray.muted};
`,
);

export const ViewDetailsPrefix = styled.div(
  ({ theme }: { theme: Theme }) => `
  button {
    cursor: pointer;
    color: ${theme.colors.interactive.text.primary.subtle};
    position: relative;
    @media (max-width: ${theme.breakpoints.s}px) {
      &::before {
        content: 'View All';
        font-weight: 600;
        color: ${theme.colors.interactive.text.primary.subtle};
      }
    }
  }
`,
);

export const BottomCardWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
    flex: 1;
    &:hover {
      cursor: pointer;
      button{
        &::before {
          content: 'View All';
          font-weight: 600;
          color: ${theme.colors.interactive.text.primary.subtle};
        }
      }
    }
    [data-blade-component='heading']{
      font-size: ${theme.typography.fonts.size[500]}px;
    }
`,
);
