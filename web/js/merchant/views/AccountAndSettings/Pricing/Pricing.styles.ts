import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

const StyledNavLink = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: ${theme.spacing[3]}px;
  margin: ${theme.spacing[0]} ${theme.spacing[5]}px;
  width: fit-content;

  > a.pricing-plan-link {
    margin: 0;
  }
`,
);

export { StyledNavLink };
