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
    font-size: ${theme.typography.fonts.size[1000]}px;
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
  border: 1px solid ${theme.colors.surface.border.normal.lowContrast};
`,
);

export const ViewDetailsPrefix = styled.div(
  ({ theme }: { theme: Theme }) => `
  button {
    cursor: pointer;
    color: ${theme.colors.action.text.link.default};
    position: relative;
    @media (max-width: ${theme.breakpoints.s}px) {
      &::before {
        content: 'View All';
        font-weight: bold;
        color: ${theme.colors.action.text.link.default};
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
          font-weight: bold;
          color: ${theme.colors.action.text.link.default};
        }
      }
    }
    [data-blade-component='heading']{
      font-size: ${theme.typography.fonts.size[600]}px;
    }
`,
);
