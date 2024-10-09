import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const IconContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
 position: absolute;
  top: -9px;
  right: 0;
  background-color: ${theme.colors.surface.background.gray.intense};
  border-radius: ${theme.border.radius.round}
`,
);
