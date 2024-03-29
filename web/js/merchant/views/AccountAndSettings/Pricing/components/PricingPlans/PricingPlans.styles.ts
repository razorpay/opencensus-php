import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

const Flex = styled.div<any>`
  display: flex;
  gap: ${({ gap, theme }) => `${theme.spacing[gap]}px`};
  flex-direction: ${({ flexDirection }) => flexDirection || 'row'};
  justify-content: ${({ justifyContent, flexDirection }) =>
    justifyContent ? justifyContent : flexDirection === 'column' ? 'center' : 'flex-start'};
  align-items: ${({ alignItems, flexDirection }) =>
    alignItems ? alignItems : flexDirection === 'column' ? 'flex-start' : 'center'};

  ${({ width }) => width && `width: ${width}`}
`;

const HeaderBodyDivider = styled.div(
  ({ theme }: { theme: Theme }) => `
  width: 100%;
  height: 0;
  border: ${theme.border.width.thin}px solid ${theme.colors.surface.border.gray.muted};
`,
);

const StyledPricingPlans = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  gap: ${theme.spacing[7]}px;
  flex-direction: column;
  justify-content: center;
  align-items: flex-start;
  padding: ${theme.spacing[6]}px;
  background-color: ${theme.colors.surface.background.gray.intense};
  border: 1px solid #E2E8EA;
`,
);

const LoaderContainer = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  height: 50vh;
  width: 100%;
`;

export { Flex, HeaderBodyDivider, StyledPricingPlans, LoaderContainer };
