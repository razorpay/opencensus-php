import styled, { css, keyframes } from 'styled-components';
import { Theme } from '@razorpay/blade/components';

const pulse = keyframes`
  from { opacity: 0.3; }
  to { opacity: 1; }
`;
const animation = css`
  animation: ${pulse} 0.5s infinite alternate
`;
export const AnimatedBrandLogo = styled.img(
  ({ theme }: { theme: Theme }) => css`
  width: ${theme.spacing[8]}px;
  height: ${theme.spacing[8]}px;
  margin-top: ${theme.spacing[10]}px;
  ${animation};
`
);
