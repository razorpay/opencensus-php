import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

export const IntermediateInsName = styled.p(
  ({ theme }: { theme: Theme }) => `
    font-weight: ${theme.typography.fonts.weight.regular} ;
    font-size: ${theme.typography.fonts.size[100]}px !important;
    color: ${theme.colors.interactive.background.neutral.default} !important;
  `,
);
