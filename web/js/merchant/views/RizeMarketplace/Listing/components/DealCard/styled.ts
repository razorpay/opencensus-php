import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const DashedBox = styled.div(
  ({ theme }: { theme: Theme }) => `
  position: absolute;
  inset: ${theme.spacing[0]}px;
  border-width: ${theme.border.width.thin}px;
  border-style: dashed;
  border-color: ${theme.colors.brand.primary[600]};
  border-radius: ${theme.border.radius.medium}px;
  pointer-events: none;
`,
);
