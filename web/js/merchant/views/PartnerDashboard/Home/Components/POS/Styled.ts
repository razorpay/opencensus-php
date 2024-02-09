import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const ColoredLine = styled.div(
  ({ theme }: { theme: Theme }) => `
  background-color: #6988F5;
  width: ${theme.spacing[8]}px;
  height: ${theme.spacing[2]}px;
  `,
);
