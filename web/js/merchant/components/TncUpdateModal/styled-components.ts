import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

export const TermsLink = styled.a(
  ({ theme }: { theme: Theme }) => `
    font-weight: ${theme.typography.fonts.weight.medium};
    color: ${theme.colors.interactive.text.primary.normal};
    font-size: ${theme.typography.fonts.size[100]}px;
  `,
);
