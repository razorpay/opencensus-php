import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const BannerContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  cursor: pointer;
  padding: ${theme.spacing[6]}px ${theme.spacing[6]}px ${theme.spacing[0]}px ${theme.spacing[6]}px;
`,
);
