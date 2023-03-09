import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

const StyledCardBody = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: grid;
  gap: ${theme.spacing[9]}px;
  grid-template-columns: repeat(4, auto);
  align-items: baseline;
  
  @media screen and (max-width: ${theme.breakpoints.l}px) {
    grid-template-columns: repeat(3, auto);
  }

  @media screen and (max-width: ${theme.breakpoints.m}px) {
    grid-template-columns: repeat(2, auto);
    gap: ${theme.spacing[4]}px;
    align-items: baseline;
  }
`,
);

const UnorderedList = styled.ul`
  margin: 0;
  padding-left: 1em;
`;

export { StyledCardBody, UnorderedList };
