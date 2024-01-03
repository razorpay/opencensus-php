import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const PosDeviceCollapsedContent = styled.div(
  ({ theme, isOpen }: { theme: Theme; isOpen: boolean }) => `
    overflow: hidden;
    max-height: ${isOpen ? '100vh' : 0};
    transition: max-height 0.4s ${theme.motion.easing.standard.attentive} 0s;
  `,
);
